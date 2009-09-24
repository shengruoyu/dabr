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
  
  // Change cache settings depending on which page we're looking at
  $current_page = $params['page'];
  if ($current_page == 1) {
    // Page 1, short expiry time
    $cache_expiry = time() + 30;
    $cache_pages = 1;
  } else {
    // Page 2+, longer expiry, 200 tweets are fetched at once
    $cache_expiry = time() + 120;
    $cache_pages = 10;
    $params['count'] = 200;
    unset($params['page']);
  }
  
  // Wipe old cache entries (although it would be better to do this with a cron job)
  db_query("delete from cache where expiry < %d", time());
  
  // Check if we've got a cache of the current page
  $rs = db_query("select * from cache where owner='%s' and type='%s' and expiry > '%d' and pages >= '%d' order by expiry desc", user_current_username(), $cache_type, time(), $current_page);
  if (db_num_rows($rs) > 0) {
    // Found cached tweets
    $r = db_fetch_object($rs);
    $response = unserialize($r->cache);
  } else {
    // No cache found, lets make one instead
    $response = twitter_request($url, $method, $params);
    $cache = serialize($response);
    
    // Save our cache to the database
    db_query("insert into cache (owner, type, expiry, pages, cache) values ('%s', '%s', '%d', '%d', '%s')", user_current_username(), $cache_type, $cache_expiry, $cache_pages, $cache);
  }
  
  // Move to the correct page
  $tweets_per_page = 20;
  $response = array_slice($response, ($current_page - 1) * $tweets_per_page, $tweets_per_page);
  
  // Return a normal timeline (hopefully)
  return $response;
}