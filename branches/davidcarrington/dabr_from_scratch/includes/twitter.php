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
  
  // Set up some options on the request
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
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

function twitter_search_timeline($search_query, $params = array()) {
  $params['q'] = $search_query;
  $request = 'http://search.twitter.com/search.json';
  $tl = twitter_paged_request($request, $params);
  $tl = twitter_standard_timeline($tl->results, 'search');
  return $tl;
}

function twitter_friends_timeline($params = array()) {
  $request = 'http://twitter.com/statuses/friends_timeline.json';
  $tl = twitter_paged_request($request, $params);
  return twitter_standard_timeline($tl, 'friends');
}

function twitter_replies_timeline() {
  $request = 'http://twitter.com/statuses/replies.json';
  $tl = twitter_paged_request($request);
  return twitter_standard_timeline($tl, 'replies');
}

function twitter_standard_timeline($feed, $source) {
  // Proccesses API responses so they all return similar results
  $timeline = array();
  switch ($source) {
    case 'favourites':
    case 'friends':
    case 'public':
    case 'replies':
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

function twitter_update($status) {
  $request = 'http://twitter.com/statuses/update.json';
  $params = array('status' => $status);
  return twitter_request($request, 'POST', $params);
}

function page_update() {
  // TODO: basic verification
  // TODO: link shortening (optional?)
  $status = stripslashes(trim($_POST['status']));
  twitter('update', $status);  
  
  header('Location: '. BASE_URL);
  exit();
}

function page_home() {
  $title = 'Home';
  $tl = twitter('friends_timeline');
  $content = theme('timeline', $tl);
  return compact('title', 'content');
}

function page_replies() {
  $title = 'Replies';
  $tl = twitter('replies_timeline');
  $content = theme('timeline', $tl);
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

function page_followers($query) {
  $user = $query[1];
  if (!$user) {
    $user = user_current_username();
  }
  $title = 'Followers';
  $followers = twitter('followers', $user);
  $content = theme('followers', compact('user', 'followers'));
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

