<?php

if (!function_exists('twitter')) {
  function twitter() {
    $args = func_get_args();
    $function = 'twitter_'.array_shift($args);
    return call_user_func_array($function, $args);
  }
}

function twitter_request($url, $method, $params = false) {
  // Automagically sign pages if necessary
  oauth_sign($url, $params, $method);
  
  $api_start = microtime(1);
  
  // Set up some options on the request
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
  }
  if ($response_info['http_code'] != 200) {
    // TODO: template with suggested solutions (status.twitter.com, FAQ wiki page, new issue)
    echo "<hr /><h3>Error {$response_info['http_code']}</h3><p>$url</p><hr /><pre>";
    die($response);
  }
  
  // Close off the connection
  curl_close($ch);
  
  $GLOBALS['time']['api'][$url] = microtime(1) - $api_start;
  
  // Try to decode any JSON we get back automatically
  // This should probably look at mime type, not the string
  if (in_array(substr($response, 0, 1), array('{', '[')))
    return json_decode($response);
  else
    return $response;
}

function twitter_paged_request($url, $params = array()) {
  if (!array_key_exists('page', $params)) {
    $page = (int) $_GET['page'];
    if (!$page) $page = 1;
    $params['page'] = $page;
  }
  return twitter('request', $url, 'GET', $params);
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

function twitter_search_timeline($search_query, $params = array()) {
  $params['q'] = $search_query;
  $request = 'http://search.twitter.com/search.json';
  $tl = twitter_paged_request($request, $params);
  $tl = twitter_standard_timeline($tl->results, 'search');
  return $tl;
}

function twitter_home_timeline($params = array()) {
  $request = 'http://twitter.com/statuses/friends_timeline.json';
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'home');
}

function twitter_mentions_timeline($params = array()) {
  $request = 'http://twitter.com/statuses/mentions.json';
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'mentions');
}

function twitter_user_timeline($screen_name, $params = array()) {
  $params['screen_name'] = $screen_name;
  $request = 'http://twitter.com/statuses/user_timeline.json';
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'user');
}

function twitter_favourites_timeline($screen_name, $params = array()) {
  $request = "http://twitter.com/favorites.json";
  $params['screen_name'] = $screen_name;
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'favourites');
}

function twitter_friends_timeline($screen_name, $params = array()) {
  $request = "http://twitter.com/statuses/friends.json";
  $params['screen_name'] = $screen_name;
  $tl = twitter_paged_request($request, $params);
  return array('timeline' => $tl, 'source' => 'friends');
}

function twitter_followers_timeline($screen_name, $params = array()) {
  $request = "http://twitter.com/statuses/followers.json";
  $params['screen_name'] = $screen_name;
  $tl = twitter_paged_request($request, $params);
  return array('timeline' => $tl, 'source' => 'followers');
}

function twitter_follow($screen_name) {
  $request = "http://twitter.com/friendships/create/{$screen_name}.json";
  twitter('request', $request, 'POST');
}

function twitter_unfollow($screen_name) {
  $request = "http://twitter.com/friendships/destroy/{$screen_name}.json";
  twitter('request', $request, 'POST');
}

function twitter_standard_timeline($feed, $source) {
  // Proccesses API responses so they all return similar results
  $timeline = array();
  switch ($source) {
    case 'favourites':
    case 'home':
    case 'public':
    case 'mentions':
    case 'user':
      foreach ($feed as $status) {
        $new = $status;
        $new->from = $new->user;
        unset($new->user);
        $timeline[(string) $new->id] = $new;
      }
      break;
    
    case 'directs_sent':
    case 'directs_inbox':
      foreach ($feed as $status) {
        $new = $status;
        if ($source == 'directs_inbox') {
          $new->from = $new->sender;
          $new->to = $new->recipient;
        } else {
          $new->from = $new->recipient;
          $new->to = $new->sender;
        }
        unset($new->sender, $new->recipient);
        $new->is_direct = true;
        $timeline[] = $new;
      }
      break;
      
    case 'search':
      foreach ($feed as $status) {
        $timeline[(string) $status->id] = (object) array(
          'id' => $status->id,
          'text' => $status->text,
          'source' => strpos($status->source, '&lt;') !== false ? html_entity_decode($status->source) : $status->source,
          'from' => (object) array(
            'id' => $status->from_user_id,
            'screen_name' => $status->from_user,
            'profile_image_url' => $status->profile_image_url,
          ),
          'to' => (object) array(
            'id' => $status->to_user_id,
            'screen_name' => $status->to_user,
          ),
          'created_at' => $status->created_at,
        );
      }
      break;
  }
  return compact('source', 'timeline');
}


function twitter_parse_tags($input) {
  $out = preg_replace_callback('#(\w+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)(?<![.,])#is', 'twitter_parse_links_callback', $input);
  $out = preg_replace('#(^|\s)@([a-z_A-Z0-9]+)#', '$1@<a href="user/$2">$2</a>', $out);
  $out = preg_replace('#(^|\s)(\\#([a-z_A-Z0-9:_-]+))#', '$1<a href="hash/$3">$2</a>', $out);
  return $out;
}

function twitter_parse_links_callback($matches) {
  // TODO: Optional redirection with Google Web Transcoder
  // TODO: Optionally replace link text with [link]
  $url = $matches[1];
  return "<a href='$url'>$url</a>";
}

function twitter_direct_messages($subpage = null) {
  if ($subpage == 'sent') {
    $request = 'http://twitter.com/direct_messages/sent.json';
    $source = 'directs_sent';
  } else {
    $request = 'http://twitter.com/direct_messages.json';
    $source = 'directs_inbox';
  }
  $tl = twitter_paged_request($request);
  $tl = twitter_standard_timeline($tl, $source);
  return $tl;
}

function twitter_followers($user) {
  // TODO: really fetch a list of followers, probably combine this with a twitter_friends() function
  return array();
}

function twitter_update($status, $in_reply_to_status_id = null) {
  $request = 'http://twitter.com/statuses/update.json';
  $params = array('status' => $status);
  if ($in_reply_to_status_id) {
    $params['in_reply_to_status_id'] = $in_reply_to_status_id;
  }
  return twitter_request($request, 'POST', $params);
}

function page_update() {
  // TODO: basic verification
  // TODO: link shortening (optional?)
  $status = stripslashes(trim($_POST['status']));
  $in_reply_to_status_id = $_POST['in_reply_to_status_id'];
  twitter('update', $status, $in_reply_to_status_id);  
  
  header('Location: '. BASE_URL);
  exit();
}

function page_home() {
  $title = 'Home';
  $tl = twitter('home_timeline');
  $content = theme('update_form');
  $content .= theme('timeline', $tl);
  return compact('title', 'content');
}

function page_mentions() {
  $title = 'Mentions';
  $tl = twitter('mentions_timeline');
  $content = theme('update_form');
  $content .= theme('timeline', $tl);
  return compact('title', 'content');
}

function page_trends() {
  $title = 'Twitter Trends';
  $trends = twitter('trends');
  $content = theme('trends', compact('trends'));
  return compact('title', 'content');
}

function page_directs($query) {
  $subpage = $query[1];
  // This page is actually 3 pages
  switch ($subpage) {
    case 'create':
      // TODO: Handle posting new DMs here too
      $content = theme('directs_create');
      $title = 'New DM';
      break;
    case 'sent':
      $timeline = twitter('direct_messages', $subpage);
      $content = theme('directs', $timeline);
      $title = 'DM Sent';
      break;
    case 'inbox':
    default:
      $timeline = twitter('direct_messages', $subpage);
      $content = theme('directs', $timeline);
      $title = 'DM Inbox';
      break;
  }
  return compact('title', 'content');
}

function page_search() {
  $search_term = trim($_GET['query']);
  $title = 'Search';
  $content = '<p>TODO: Search form goes here</p>';
  if ($search_term) {
    $tl = twitter('search_timeline', $search_term);
    $content .= theme('timeline', $tl);
  }
  return compact('title', 'content');
}

function page_user($query) {
  $screen_name = $query[1];
  if (!$screen_name) {
    $screen_name = user_current_username();
  }
  $title = "User $screen_name";
  $tl = twitter('user_timeline', $screen_name);
  
  // As long as you're not looking at your own profile, put the screen name into the status box
  if (!user_is_current_user($screen_name)) {
    $status = "@{$screen_name} ";
  } else {
    $status = '';
  }
  
  // Replies logic:
  if (is_numeric($query[3])) {
    $in_reply_to_status_id = $query[3];
    // Attempt to find the right tweet in the users shown timeline
    // This is done to fetch some extra information but not use an extra API call
    if ($tweet = $tl['timeline'][$in_reply_to_status_id]) {
      // Look for hashtags in the tweet we found
      if (preg_match_all('/#([\w\d]+)/', $tweet->text, $matches)) {
        // Loop through the hashtags and append them to the status box
        foreach ($matches[1] as $hashtag) {
          $status .= "#{$hashtag} ";
        }
      }
    }
  }
  $content = theme('update_form', compact('status', 'in_reply_to_status_id'));
  $content .= theme('user_profile', $tl);
  return compact('title', 'content');
}

function page_favourites($query) {
  $title = 'Favourites';
  $screen_name = $query[1];
  if (!$screen_name) {
    $screen_name = user_current_username();
  }
  $tl = twitter('favourites_timeline', $screen_name);
  $content = theme('timeline', $tl);
  return compact('title', 'content');
}

function page_friends($query) {
  $title = 'Friends';
  $screen_name = $query[1];
  if (!$screen_name) {
    $screen_name = user_current_username();
  }
  $tl = twitter('friends_timeline', $screen_name);
  $content = theme('users', $tl);
  return compact('title', 'content');
}

function page_followers($query) {
  $title = 'Followers';
  $screen_name = $query[1];
  if (!$screen_name) {
    $screen_name = user_current_username();
  }
  $tl = twitter('followers_timeline', $screen_name);
  $content = theme('users', $tl);
  return compact('title', 'content');
}

function page_follow($query) {
  $screen_name = $query[1];
  twitter('follow', $screen_name);
  redirect('friends');
}

function page_unfollow($query) {
  $screen_name = $query[1];
  twitter('unfollow', $screen_name);
  redirect('friends');
}