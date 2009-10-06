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
    if ($response_info['http_code'] == 0 && !$response) $response = 'Twitter API timed out';
    if (strlen($response) > 500) $response = 'Twitter is probably overloaded right now.';
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

function twitter_public_timeline($params = array()) {
  $request = 'http://twitter.com/statuses/public_timeline.json';
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'public');
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

function twitter_user($screen_name) {
  if (!$screen_name)
    $screen_name = user_current_username();
  $request = "http://twitter.com/users/show.json";
  $params['screen_name'] = $screen_name;
  $user = twitter('request', $request, 'GET', $params);
  return $user;
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

function twitter_delete($id) {
  $request = "http://twitter.com/statuses/destroy/{$id}.json";
  twitter('request', $request, 'POST');
}

function twitter_favourite($id) {
  $request = "http://twitter.com/favorites/create/{$id}.json";
  twitter('request', $request, 'POST');
}

function twitter_unfavourite($id) {
  $request = "http://twitter.com/favorites/destroy/{$id}.json";
  twitter('request', $request, 'POST');
}

function twitter_single_tweet($id) {
  $request = "http://twitter.com/statuses/show/{$id}.json";
  $status = twitter('request', $request, 'GET');
  if ($status->user) {
    $status->from = $status->user;
  }
  return $status;
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

function twitter_update($status, $in_reply_to_status_id = null) {
  $request = 'http://twitter.com/statuses/update.json';
  $params = array('status' => $status);
  if ($in_reply_to_status_id) {
    $params['in_reply_to_status_id'] = $in_reply_to_status_id;
  }
  return twitter_request($request, 'POST', $params);
}

function twitter_tweets_per_day($user, $rounding = 1) {
  // Helper function to calculate an average count of tweets per day
  $days_on_twitter = (time() - strtotime($user->created_at)) / 86400;
  return round($user->statuses_count / $days_on_twitter, $rounding);
}

function page_update() {
  dabr_ensure_post_action();
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
  $term = trim($_GET['query']);
  $title = 'Search';
  $content = theme('search_form', compact('term'));
  if ($term) {
    $tl = twitter('search_timeline', $term);
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
  
  // As long as you're not looking at your own profile, put the screen name into the status box
  if (!user_is_current_user($screen_name)) {
    $status = "@{$screen_name} ";
  } else {
    $status = '';
  }
  
  // Fetch all the user details
  // TODO: find out why $timeline[x]->following is unreliable and get rid of this extra API call
  $user = twitter('user', $screen_name);
  
  if (isset($user->status)) {
    // Found a tweet to work with, now we want more
    $tl = twitter('user_timeline', $screen_name);
    $tl['user'] = $user;
    
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
  } else {
    // Protected user or simply has no tweets
    $tl = compact('user');
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
  return _page_action($query, 'friends');
}

function page_unfollow($query) {
  return _page_action($query, 'friends');
}

function page_retweet($query) {
  $title = 'Retweet';
  $id = $query[1];
  if (is_numeric($id)) {
    $status = twitter('single_tweet', $id);
    $status = "RT @{$status->from->screen_name}: {$status->text}";
    $content = theme('update_form', compact('status'));
    return compact('title', 'content');
  }
}

function page_public() {
  $title = 'Public';
  $tl = twitter('public_timeline');
  $content = theme('update_form');
  $content .= theme('timeline', $tl);
  return compact('title', 'content');
}

function page_status($query) {
  $screen_name = $query[1];
  $id = $query[2];
  if (is_numeric($id)) {
    $title = 'Tweet';
    $status = twitter('single_tweet', $id);
    $timeline = array($status);
    $user = $status->user;
    $content = theme('user_profile', compact('user', 'timeline'));
    return compact('title', 'content');
  }
}

function dabr_ensure_post_action() {
  // This function is used to make sure the user submitted their action as an HTTP POST request
  // It slightly increases security for actions such as Delete and Block
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Error: Invalid HTTP request method for this action.');
  }
}

function _page_action($query, $destination = null) {
  // Generic handler for calls of the form example.com/$action/$id
  // e.g. delete, block and favourite
  
  $action = $query[0];
  $target = $query[1];
  
  // Since _page_action() has to be called by another page, we'll just trust the $action is valid
  twitter($action, $target);
  redirect($destination);
}

function page_delete($query) {
  dabr_ensure_post_action();
  return _page_action($query, '');
}

function page_block($query) {
  dabr_ensure_post_action();
  return _page_action($query);
}
function page_unblock($query) {
  dabr_ensure_post_action();
  return _page_action($query);
}

function page_favourite($query) {
  return _page_action($query, 'favourites');
}

function page_unfavourite($query) {
  return _page_action($query, 'favourites');
}

function page_confirm($query) {
  $action = $query[1];
  $target = $query[2];
  
  $title = "Confirm $action";
  $content = '';
  
  // TODO: Move content into template files
  
  switch ($action) {
    case 'delete':
      $content = '<p>Are you sure you want to <strong>'.$action.'</strong>?</p>';
  $content .= '<form method="post" action="'.$action.'/'.$target.'"><input type="submit" value="Yes please" /></form>';
      break;
    case 'block':
      // TODO: check if block exists already
      $content = "<p>Are you sure you want to <strong>$action $target</strong>?</p>";
      $content .= "<ul><li>You won't show up in their list of friends</li><li>They won't see your updates on their home page</li><li>They won't be able to follow you</li><li>You <em>can</em> unblock them but you will need to follow them again afterwards</li></ul>";
      $content .= '<form method="post" action="'.$action.'/'.$target.'">';
      $content .= '<p><label><input type="checkbox" name="spam" value="yes" /> Also send a DM to @spam about this user [<a href="faq/spam" title="Why spam?">?</a>]</label></p>';
      $content .= '<input type="submit" value="Yes please" /></form>';
  }
  return compact('title', 'content');
}