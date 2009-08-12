<?php

require 'oauth.class.php';

function oauth_sign(&$url, &$args = false) {
  // Only sign twitter.com URLs (including https ones)
  if (strpos('/twitter.com', $url) !== false) return;
  
  // Check if we're doing a post
  $method = ($args !== false) ? 'POST' : 'GET';
  
  // Set up some OAuth bits and bobs
  $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
  $token = NULL;

  // Test for tokens in the query string or cookies
  if (($oauth_token = $_GET['oauth_token']) && $_SESSION['oauth_request_token_secret']) {
    $oauth_token_secret = $_SESSION['oauth_request_token_secret'];
  } else {
    // TODO: Try fetch OAuth access token from cookie
    //~ list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
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
  session_start();
  if ($oauth_token = $_GET['oauth_token']) {
    // Stage 2: We have a "request" token, now we can get an "access" token
    $params = array('oauth_verifier' => $_GET['oauth_verifier']);
    $response = twitter_fetch('https://twitter.com/oauth/access_token', $params);
    parse_str($response, $token);
    
    // TODO: save access token to cookie
    
    echo "<p>Got an access token. This needs to be saved to a cookie for later.</p><pre>";
    print_R($token);
  } else {
    // Stage 1: We have no "request" token, so ask Twitter for one and redirect the user
    // Build some params so that Twitter knows where to send the user back to (overides Twitter's OAuth settings)
    $params = array('oauth_callback' => BASE_URL.'oauth');
    
    // Send the request (note that twitter_fetch() automagically signs the requests with the correct keys)
    $response = twitter_fetch('https://twitter.com/oauth/request_token', $params);
    parse_str($response, $token);
    
    // Save the request token secret to session so we can use it when the user gets back from Twitter.com
    $_SESSION['oauth_request_token_secret'] = $token['oauth_token_secret'];
    
    // Send the user to Twitter's authorisation page
    $authorise_url = 'https://twitter.com/oauth/authorize?oauth_token='.$token['oauth_token'];
    header("Location: $authorise_url");
    exit();
  }
}