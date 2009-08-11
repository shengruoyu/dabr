<?php

function twitter_fetch($url, $post_data = false) {
  oauth_sign($url, $post_data);
    
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  
  if($post_data !== false) {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
  }
  
  $response = curl_exec($ch);
  $response_info = curl_getinfo($ch);
  
  if ($response_info['http_code'] != 200) {
    echo "<hr /><h3>Error {$response_info['http_code']}</h3><p>$url</p><hr /><pre>";
    die($response);
  }
  
  curl_close($ch);
  
  if (substr($response, 0, 1) == '{') // I should probably look at mime type, not the string
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
  if (!empty($raw_trends))
    return array();
  $raw_trends = array_pop($raw_trends);
  
  $trends = array();
  foreach ($raw_trends as $trend) {
    $trends[] = $trend->name;
  }
  return $trends;
}

function page_trends() {
  return array(
    'title' => 'Twitter Trends',
    'content' =>  theme('trends', array('trends' => twitter_trends())),
  );
}

?>