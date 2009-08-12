<?php

function twitter_request($url, $method, $params = false) {
  // Automagically sign pages if necessary
  oauth_sign($url, $params, $method);
  
  // Set up some options on the request
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  //~ curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  
  if($method == 'POST') {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
  }
  
  // Perform the request
  $response = curl_exec($ch);
  $response_info = curl_getinfo($ch);
  
  // Do some basic error handling
  if ($response_info['http_code'] == 401) {
    user_logout();
    page_oauth();
  }
  if ($response_info['http_code'] != 200) {
    echo "<hr /><h3>Error {$response_info['http_code']}</h3><p>$url</p><hr /><pre>";
    die($response);
  }
  
  // Close off the connection
  curl_close($ch);
  
  // Try to decode any JSON we get back automatically
  // This should probably look at mime type, not the string
  if (in_array(substr($response, 0, 1), array('{', '[')))
    return json_decode($response);
  else
    return $response;
}

function twitter_paged_request($url) {
  $page = (int) $_GET['page'];
  if (!$page) $page = 1;
  $params = array(
    'page' => $page,
  );
  return twitter_request($url, 'GET', $params);
}

function twitter_trends() {
  $request = 'http://search.twitter.com/trends/current.json';
  $response = twitter_request($request, 'GET');
  
  // Convert the response into something a bit more usable:
  $trends = (array) $response->trends;
  if (empty($trends))
    return array();
  return array_pop($trends);
}

function twitter_friends_timeline() {
  $request = 'http://twitter.com/statuses/friends_timeline.json';
  $tl = twitter_paged_request($request);
  return twitter_standard_timeline($tl);
}

function twitter_replies_timeline() {
  $request = 'http://twitter.com/statuses/replies.json';
  $tl = twitter_paged_request($request);
  return twitter_standard_timeline($tl);
}

function twitter_standard_timeline($tl) {
  // This ought to be doing some pre-processing to make sure all timelines look the same,
  // the same as dabr trunk does.
  return array('tweets' => $tl);
}


function twitter_parse_tags($input) {
  $out = preg_replace_callback('#(\w+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)(?<![.,])#is', 'twitter_parse_links_callback', $input);
  $out = preg_replace('#(^|\s)@([a-z_A-Z0-9]+)#', '$1@<a href="user/$2">$2</a>', $out);
  $out = preg_replace('#(^|\s)(\\#([a-z_A-Z0-9:_-]+))#', '$1<a href="hash/$3">$2</a>', $out);
  return $out;
}

function twitter_parse_links_callback($matches) {
  $url = $matches[1];
  return "<a href='$url'>$url</a>";
}

function page_update() {
  $status = stripslashes(trim($_POST['status']));
  $request = 'http://twitter.com/statuses/update.json';
  $params = array('status' => $status);
  $b = twitter_request($request, 'POST', $params);
  
  header('Location: '. BASE_URL);
  exit();
}

function page_home() {
  $title = 'Home';
  $tl = twitter_friends_timeline();
  $content = theme('timeline', $tl);
  return compact('title', 'content');
}

function page_replies() {
  $title = 'Replies';
  $tl = twitter_replies_timeline();
  $content = theme('timeline', $tl);
  return compact('title', 'content');
}

function page_trends() {
  $title = 'Twitter Trends';
  $trends = twitter_trends();
  $content = theme('trends', array('trends' => $trends));
  return compact('title', 'content');
}

?>