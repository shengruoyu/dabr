<?php

// TODO: change User to point to a database of users

class User extends Model {
	var $cookieName = 'user';
	var $encryptionKey = '';
	var $info = array();
	
	function __construct($key) {
		$this->encryptionKey = $key;
		$this->load();
	}

	function get($name) {
		return $this->info[$name];
	}
	
	function set($name, $value) {
		return $this->info[$name] = $value;
	}
	
	function isAuthenticated() {
		if (empty($this->info))
			return false;
		return true;
	}
	
	function load() {
		if (array_key_exists($this->cookieName, $_COOKIE)) {
			$cookie_data = $_COOKIE[$this->cookieName];
			$this->info = $this->decrypt($cookie_data);
			return true;
		}
		return false;
	}
	
	function save() {
		$data = $this->encrypt($this->info);
		$expire = time() + (3600 * 24 * 365);
		return setcookie($this->cookieName, $data, $expire, '/');
	}
	
	function logout() {
		$this->User->info = array();
		return setcookie($this->cookieName, '', time() - 3600, '/');
	}
	
	function encrypt($array) {
		$plainText = serialize($array);
		$td = mcrypt_module_open('blowfish', '', 'cfb', '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $this->encryptionKey, $iv);
		$cryptText = mcrypt_generic($td, $plainText);
		mcrypt_generic_deinit($td);
		$cryptText = base64_encode($iv.$cryptText);
		return $cryptText;
	}
	
	function decrypt($cookieString) {
		$cryptText = base64_decode($cookieString);
		$td = mcrypt_module_open('blowfish', '', 'cfb', '');
		$ivsize = mcrypt_enc_get_iv_size($td);
		$iv = substr($cryptText, 0, $ivsize);
		$cryptText = substr($cryptText, $ivsize);
		mcrypt_generic_init($td, $this->encryptionKey, $iv);
		$plainText = mdecrypt_generic($td, $cryptText);
		mcrypt_generic_deinit($td);
		return unserialize($plainText);
	}
}