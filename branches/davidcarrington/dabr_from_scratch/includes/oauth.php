<?php

require 'oauth.class.php';

function oauth_sign(&$url, &$args = false) {
  if (strpos('/twitter.com', $url) !== false) return;
  $method = ($args !== false) ? 'POST' : 'GET';
  
  $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET);
  $token = NULL;

  if (($oauth_token = $_GET['oauth_token']) && $_SESSION['oauth_request_token_secret']) {
    $oauth_token_secret = $_SESSION['oauth_request_token_secret'];
  } else {
    // TODO: Try fetch OAuth access token from cookie
    //~ list($oauth_token, $oauth_token_secret) = explode('|', $GLOBALS['user']['password']);
  }
  if ($oauth_token && $oauth_token_secret) {
    $token = new OAuthConsumer($oauth_token, $oauth_token_secret);
  }
  
  $request = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $url, $args);
  $request->sign_request($sig_method, $consumer, $token);
  
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
    $params = array('oauth_verifier' => $_GET['oauth_verifier']);
    $response = twitter_fetch('https://twitter.com/oauth/access_token', $params);
    parse_str($response, $token);
    
    // TODO: save access token to cookie
    
    echo "<p>Got an access token. This needs to be saved to a cookie for later.</p><pre>";
    print_R($token);
  } else {
    $params = array('oauth_callback' => BASE_URL.'oauth');
    $response = twitter_fetch('https://twitter.com/oauth/request_token', $params);
    parse_str($response, $token);
    
    $_SESSION['oauth_request_token_secret'] = $token['oauth_token_secret'];
    $authorise_url = 'https://twitter.com/oauth/authorize?oauth_token='.$token['oauth_token'];
    
    header("Location: $authorise_url");
    exit();
  }
}