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
  if($trend_type == '')
    $trend_type = 'current';
  $request = 'http://search.twitter.com/trends/current.json';
  $response = twitter_fetch($request);
  $raw_trends = (array) $response->trends;
  if (empty($raw_trends))
    return array();
  $raw_trends = array_pop($raw_trends);
  
  $trends = array();
  foreach ($raw_trends as $trend) {
    $trends[] = $trend->name;
  }
  return $trends;
}

function page_trends() {
  $trends = twitter_trends();
  $content = theme('trends', array('trends' => $trends));
  return array(
    'title' => 'Twitter Trends',
    'content' => $content,
  );
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
  if (user_is_authenticated()) {
    // Logged in, try showing tweets!
    $tl = twitter_friends_timeline();
    $content = theme('timeline', $tl);
  } else {
    // Not logged in, show a dummy page for now
    $content = '<p>Not logged in, try the links above.</p>';
  }
  
  // Compact is an automagic PHP function
  return compact('title', 'content');
}

function page_replies() {
  $title = 'Replies';
  if (user_is_authenticated()) {
    // Logged in, try showing tweets!
    $tl = twitter_replies_timeline();
    $content = theme('timeline', $tl);
  } else {
    // Not logged in, show a dummy page for now
    $content = '<p>Not logged in, try the links above.</p>';
  }
  
  return compact('title', 'content');
}

?>