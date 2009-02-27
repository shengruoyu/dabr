<?php

function user_ensure_authenticated() {
  if (!user_is_authenticated()) {
    $content = theme('login');
    $content .= file_get_contents('about.html');
    theme('page', 'Login', $content);
  }
}

function user_logout() {
  unset($GLOBALS['user']);
  setcookie('USER_AUTH', '', time() - 3600, '/');
}

function user_is_authenticated() {
  if (!isset($GLOBALS['user'])) {
    if(array_key_exists('USER_AUTH', $_COOKIE)) {
      _user_decrypt_cookie($_COOKIE['USER_AUTH']);
    } else {
      $GLOBALS['user'] = array();
    }
  }
  
  if (!$GLOBALS['user']['username']) {
    if ($_POST['username'] && $_POST['password']) {
      $GLOBALS['user']['username'] = $_POST['username'];
      $GLOBALS['user']['password'] = $_POST['password'];
      _user_save_cookie($_POST['stay-logged-in'] == 'yes');
      header('Location: '. BASE_URL);
      exit();
    } else {
      return false;
    }
  }
  return true;
}

function user_current_username() {
  return $GLOBALS['user']['username'];
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
  $plain_text = $GLOBALS['user']['username'] . ':' . $GLOBALS['user']['password'];
  
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
  
  list($GLOBALS['user']['username'], $GLOBALS['user']['password']) = explode(':', $plain_text);
}

function theme_login() {
  return '
  <p>Enter your Twitter username and password below:</p>
<form method="post" action="'.$_GET['q'].'">
Username <input name="username" size="15">
<br>Password <input name="password" type="password" size="15">
<br><label><input type="checkbox" value="yes" name="stay-logged-in"> Stay logged in? </label>
<br><input type="submit" value="Sign In">
</form>
';
}

function theme_logged_out() {
  return '<p>Logged out. <a href="">Login again</a></p>';
}

?>