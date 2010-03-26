<?php

require 'app/models/twitter.php';
require 'app/models/user.php';

class TwitterController extends Controller {
	var $Twitter = false;
	
	function __construct() {
		$this->User = new User(ENCRYPTION_KEY);
		$accessKey = $this->User->get('key');
		$accessSecret = $this->User->get('secret');
		
		$this->Twitter = new Twitter(OAUTH_CONSUMER_KEY, OAUTH_CONSUMER_SECRET, $accessKey, $accessSecret);
		
		// TODO: find a better place to define and adjust the menu
		$this->set('pageMenu', array(
			'index',
			'search',
			'oauth',
			'logout',
		));
	}
	
	function index() {
		$this->pageTitle = 'Home';
		if ($this->User->isAuthenticated()) {
			$this->set('timeline',  $this->Twitter->homeTimeline());
		}
	}
	
	function search() {
		$searchTerm = $_GET['q'];
		$timeline = $this->Twitter->search($searchTerm);
		$this->set('searchTerm', $searchTerm);
		$this->set('timeline',  $timeline->results);
	}
	
	function oauth() {
		if ($this->User->isAuthenticated()) {
			$this->redirect(BASE_URL);
		}
		$callbackUrl = 'http://localhost:81/mvcd/oauth_callback';
		$requestToken = $this->Twitter->getRequestToken($callbackUrl);
		$_SESSION['requestTokenSecret'] = $requestToken->secret;
		
		if (!$requestToken->key) die('Request token<hr>'. $requestToken);
		$this->redirect($this->Twitter->authoriseUrl . '?oauth_token=' . $requestToken->key);
	}
	
	function oauth_callback() {
		$requestTokenSecret = $_SESSION['requestTokenSecret'];
		$accessToken = $this->Twitter->getAccessToken($requestTokenSecret);
		
		$this->User->set('key', $accessToken->key);
		$this->User->set('secret', $accessToken->secret);
		$this->User->save();
		
		$this->redirect(BASE_URL);
	}
	
	function logout() {
		$this->User->logout();
		$this->redirect(BASE_URL);
	}
}