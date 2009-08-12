<?php

function twitter_fetch($url, $post_data = false) {
  // Automagically sign pages if necessary
  oauth_sign($url, $post_data);
  
  // Set up some options on the request
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  
  if($post_data !== false) {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
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

function twitter_trends() {
  $request = 'http://search.twitter.com/trends/current.json';
  $response = twitter_fetch($request);
  
  // Convert the response into something a bit more usable:
  $trends = (array) $response->trends;
  if (empty($trends))
    return array();
  return array_pop($trends);
}

function twitter_friends_timeline() {
  $request = 'http://twitter.com/statuses/friends_timeline.json';
  $tl = twitter_fetch($request);
  return twitter_standard_timeline($tl);
}

function twitter_replies_timeline() {
  $request = 'http://twitter.com/statuses/replies.json';
  $tl = twitter_fetch($request);
  return twitter_standard_timeline($tl);
}

function twitter_standard_timeline($tl) {
  // This ought to be doing some pre-processing to make sure all timelines look the same,
  // the same as dabr trunk does.
  return array('tweets' => $tl);
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