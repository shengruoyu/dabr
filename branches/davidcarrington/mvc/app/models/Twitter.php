<?php

require 'app/components/oauth.php';

class Twitter extends OAuthDoohickey {
	var $apiUrl = 'http://api.twitter.com/1/';
	var $searchApiUrl = 'http://search.twitter.com/';
	
	var $requestTokenUrl = 'http://api.twitter.com/oauth/request_token';
	var $accessTokenUrl = 'http://api.twitter.com/oauth/access_token';
	var $authoriseUrl = 'http://api.twitter.com/oauth/authorize';
	
	function __construct($consumerKey, $consumerSecret, $accessKey = null, $accessToken = null) {
		parent::__construct($consumerKey, $consumerSecret, $accessKey, $accessToken);
		
	}
	
	// Makes signed API requests to the Twitter API
	function jsonApiRequest($httpMethod, $method, $params = array()) {
		$url = $this->apiUrl . $method . '.json';
		$data = $this->doRequest($httpMethod, $url, $params);
		return json_decode($data);
	}
	
	//  Twitter API methods
	function search($term) {
		$url = $this->searchApiUrl . 'search.json?q=' . urlencode($term);
		return json_decode($this->httpRequest($url));
	}
	
	function mentions($params = array()) {
		return $this->jsonApiRequest('GET', 'statuses/mentions', $params);
	}
	
	function userTimeline($user, $params = array()) {
		$params['user'] = $user;
		return $this->jsonApiRequest('GET', 'statuses/user_timeline', $params);
	}
	
	function userShow($user, $params = array()) {
		$params['user'] = $user;
		return $this->jsonApiRequest('GET', 'users/show', $params);
	}
	
	function homeTimeline($params = array()) {
		return $this->jsonApiRequest('GET', 'statuses/home_timeline', $params);
	}
	
	function directMessage($user, $text, $params = array()) {
		$params['user'] = $user;
		$params['text'] = $text;
		return $this->jsonApiRequest('POST', 'direct_messages/new', $params);
	}
	
	function post($status, $inReplyToId = 0, $params = array()) {
		$params['status'] = $status;
		$params['in_reply_to_id'] = $inReplyToId;
		return $this->jsonApiRequest('POST', 'statuses/update', $params);
	}
	
	function updateProfile($name, $url, $location, $description, $params = array()) {
		$params['name'] = $name;
		$params['url'] = $url;
		$params['location'] = $location;
		$params['description'] = $description;
		return $this->jsonApiRequest('POST', 'account/update_profile', $params);
	}
}

abstract class OAuthDoohickey {
	var $authoriseUrl;
	var $accessTokenUrl;
	var $requestTokenUrl;
	var $accessToken;
	var $consumerToken;
	
	// Construct CONSUMER and ACCESS tokens
	function __construct($consumerKey, $consumerSecret, $accessKey = null, $accessToken = null) {
		$this->consumerToken = $this->createToken($consumerKey, $consumerSecret);
		
		if ($accessKey) {
			$this->accessToken = $this->createToken($accessKey, $accessToken);
		}
	}
	
	// Perform an HTTP request through curl
	function httpRequest($url, $postData = null) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if($postData !== null) {
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $postData);
		}
		
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
	
	// Signs a request and sends it
	function doRequest($httpMethod, $url, $params = array(), $token = null) {
		if (!$token) $token = $this->accessToken;
		$request = OAuthRequest::from_consumer_and_token($this->consumerToken, $token, $httpMethod, $url, $params);
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumerToken, $token);
		
		if ($request->get_normalized_http_method() == 'POST') {
			$data = $this->httpRequest($request->get_normalized_http_url(), $request->to_postdata());
		} else {
			$data = $this->httpRequest($request->to_url());
		}
		return $data;
	}
	
	// Create OAuth token from key and secret
	function createToken($key, $secret) {
		return new OAuthConsumer($key, $secret);
	}

	// Fetch a new REQUEST token from Twitter API
	function getRequestToken($callbackUrl) {
		$params = array('oauth_callback' => $callbackUrl);
		return $this->getToken($this->requestTokenUrl, null, $params);
	}
	
	// Fetch a new ACCESS token from Twitter API
	function getAccessToken($requestSecret) {
		$requestToken = $this->createToken($_GET['oauth_token'], $requestSecret);
		$params = array('oauth_verifier' => $_GET['oauth_verifier']);
		return $this->getToken($this->accessTokenUrl, $requestToken, $params);
	}
	
	// Perform a signed API request to fetch and parse a token
	function getToken($url, $token, $params) {
		$data = $this->doRequest('POST', $url, $params, $token);
		parse_str($data, $response);
		if (!array_key_exists('oauth_token', $response)) {
			return null;
		}
		return $this->createToken($response['oauth_token'], $response['oauth_token_secret']);
	}
}