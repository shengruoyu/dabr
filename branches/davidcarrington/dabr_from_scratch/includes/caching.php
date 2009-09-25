<?php

function twitter() {
  $args = func_get_args();  
  $function = 'twitter_'.array_shift($args);
  // We only want to overide the twitter_request function
  if ($function == 'twitter_request') $function = 'cached_twitter_request';
  return call_user_func_array($function, $args);
}

function cached_twitter_request($url, $method, $params = false) {
  // Only cache certain types of requests
  // (GET requests from page 1-10 on timeline API calls)
  if ($method == 'GET' && $params['page'] <= 10 && preg_match('#http://twitter.com/statuses/(.*).json#', $url, $matches)) {
    $cache_type = $matches[1];
  } else {
    return twitter_request($url, $method, $params);
  }
  
  // Turn off paging at the API level but remember the current page for later
  $current_page = $params['page'];
  $params['count'] = 200;
  unset($params['page']);
  
  // Check if we've got a cache of the current page
  $rs = db_query("select * from cache where owner='%s' and type='%s'", user_current_username(), $cache_type);
  if (db_num_rows($rs) > 0) {
    // Found cached tweets
    $r = db_fetch_object($rs);
    $response = unserialize($r->cache);
    
    // If we last checked the API more than 30 seconds ago, lets re-check.
    if ($r->last_checked < strtotime('30 seconds ago')) {
      // Grab the most recent tweet out of the cache and use that as the "since_id" parameter in our API call
      $last_tweet = array_shift($response);
      $params['since_id'] = $last_tweet->id;
      
      // Fetch new tweets
      $new_response = twitter_request($url, $method, $params);
      
      // Merge our cache and new tweets together and trim off the old ones
      $response = array_slice(array_merge($new_response, $response), 0, 200);
      
      // Save the cache back to our database
      $cache = serialize($response);
      db_query("update cache set cache='%s', last_checked='%d' where id=%d", $cache, time(), $r->id);
    }
  } else {
    // No cache found, lets make one
    $response = twitter_request($url, $method, $params);
    $cache = serialize($response);
    
    // Save our cache to the database
    db_query("insert into cache (owner, type, last_checked, cache) values ('%s', '%s', '%d', '%s')", user_current_username(), $cache_type, time(), $cache);
  }
  
  // Move to the correct page
  $tweets_per_page = 20;
  $response = array_slice($response, ($current_page - 1) * $tweets_per_page, $tweets_per_page);
  
  // Return a normal timeline (hopefully)
  return $response;
}