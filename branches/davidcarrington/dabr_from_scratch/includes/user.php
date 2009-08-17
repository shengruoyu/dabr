<?php

require 'oauth.class.php';

function oauth_sign(&$url, &$args = false, $method = 'GET') {
  // Only sign twitter.com URLs (including https ones)
  if (strpos('/twitter.com', $url) !== false) return;
  
  // Set up some OAuth bits and bobs
  $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
  $token = NULL;

  // Test for tokens in the query string or cookies
  if (($oauth_token = $_GET['oauth_token']) && $_SESSION['oauth_request_token_secret']) {
    $oauth_token_secret = $_SESSION['oauth_request_token_secret'];
  } else {
    list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
  }
  
  if ($oauth_token && $oauth_token_secret) {
    // Build a signing token
    $token = new OAuthConsumer($oauth_token, $oauth_token_secret);
  }
  
  // Do the signing
  $request = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $url, $args);
  $request->sign_request($sig_method, $consumer, $token);
  
  // Rebuild the URL and parameters as necessary
  switch ($method) {
    case 'GET':
      $url = $request->to_url();
      return;
    case 'POST':
      $url = $request->get_normalized_http_url();
      $args = $request->to_postdata();
      return;
  }
}

function page_oauth() {
  if (user_is_authenticated()) {
    return array(
      'title' => 'Whoops',
      'content' => '<p>It looks like you are already logged in :)</p>',
    );
  }
  session_start();
  $GLOBALS['user']['type'] = 'oauth';
  if ($oauth_token = $_GET['oauth_token']) {
    // Stage 2: We have a "request" token, now we can get an "access" token
    $params = array('oauth_verifier' => $_GET['oauth_verifier']);
    $response = twitter_request('https://twitter.com/oauth/access_token', 'POST', $params);
    parse_str($response, $token);
    
    // Save the oauth login details to our cookie
    $GLOBALS['user']['username'] = $token['screen_name'];
    $GLOBALS['user']['password'] = $token['oauth_token'] .'|'.$token['oauth_token_secret'];
    _user_save_cookie(1);
    
    // Redirect the user back to the home page
    header('Location: '. BASE_URL);
    exit();
  } else {
    // Stage 1: We have no "request" token, so ask Twitter for one and redirect the user
    // Build some params so that Twitter knows where to send the user back to (overides Twitter's OAuth settings)
    $params = array('oauth_callback' => BASE_URL.'oauth');
    
    // Send the request (note that twitter_request() automagically signs the requests with the correct keys)
    $response = twitter_request('https://twitter.com/oauth/request_token', 'POST', $params);
    parse_str($response, $token);
    
    // Save the request token secret to session so we can use it when the user gets back from Twitter.com
    $_SESSION['oauth_request_token_secret'] = $token['oauth_token_secret'];
    
    // Send the user to Twitter's authorisation page
    $authorise_url = 'https://twitter.com/oauth/authorize?oauth_token='.$token['oauth_token'];
    header("Location: $authorise_url");
    exit();
  }
}

function page_login() {
  // TODO: login template
  $title = 'Login';
  $content = '<p>Still no login page, just <a href="oauth">OAuth</a>.';
  return compact('title', 'content');
}

function page_logout() {
  user_logout();
}

function user_logout() {
  // Clear cookies and unset GLOBALS
  unset($GLOBALS['user']);
  setcookie('USER_AUTH', '', time() - 3600, '/');
  
  // Send them back to the home page
  // TODO: a common redirect function rather than all these header calls
  header('Location: ', BASE_URL);
  exit();
}

function user_is_authenticated() {
  // This function checks if they are authenticated, but NOT if they
  // are a valid Twitter user. API will error if the details are wrong.

  if (!isset($GLOBALS['user'])) {
    // Not sure if the user is logged in or not, let's check the $_COOKIE
    if(array_key_exists('USER_AUTH', $_COOKIE)) {
      // Found a cookie, decrypt it
      _user_decrypt_cookie($_COOKIE['USER_AUTH']);
    } else {
      // No cookie, no user
      $GLOBALS['user'] = array();
    }
  }
  
  if (!$GLOBALS['user']['username']) {
    return false;
  }
  return true;
}

function user_current_username() {
  return $GLOBALS['user']['username'];
}

function user_type() {
  return $GLOBALS['user']['type'];
}

function _user_save_cookie($stay_logged_in = 0) {
  $cookie = _user_encrypt_cookie();
  $duration = 0;
  if ($stay_logged_in) {
    $duration = time() + (3600 * 24 * 365);
  }
  setcookie('USER_AUTH', $cookie, $duration, '/');
}

function _user_encryption_key() {
  return ENCRYPTION_KEY;
}

function _user_encrypt_cookie() {
  $plain_text = $GLOBALS['user']['username'] . ':' . $GLOBALS['user']['password'] . ':' . $GLOBALS['user']['type'];
  
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
  mcrypt_generic_init($td, _user_encryption_key(), $iv);
  $crypt_text = mcrypt_generic($td, $plain_text);
  mcrypt_generic_deinit($td);
  return base64_encode($iv.$crypt_text);
}
  
function _user_decrypt_cookie($crypt_text) {
  $crypt_text = base64_decode($crypt_text);
  $td = mcrypt_module_open('blowfish', '', 'cfb', '');
  $ivsize = mcrypt_enc_get_iv_size($td);
  $iv = substr($crypt_text, 0, $ivsize);
  $crypt_text = substr($crypt_text, $ivsize);
  mcrypt_generic_init($td, _user_encryption_key(), $iv);
  $plain_text = mdecrypt_generic($td, $crypt_text);
  mcrypt_generic_deinit($td);
  
  list($GLOBALS['user']['username'], $GLOBALS['user']['password'], $GLOBALS['user']['type']) = explode(':', $plain_text);
}

?>