<?php

$GLOBALS['time']['dabr'] = microtime(1);

require 'config.php';
require 'includes/user.php';
require 'includes/theme.php';
require 'includes/twitter.php';

function redirect($page = NULL) {
  if (isset($page)) {
    $page = BASE_URL . $page;
  } else {
    $page = $_SERVER['HTTP_REFERER'];
  }
  header('Location: '. $page);
  exit();
}

function front_controller() {
  // Our .htaccess file and ModRewrite send all page requests through here
  // Find the name of the page to use
  $query = (array) explode('/', $_GET['q']);
  $page_name = strtolower($query[0]);
  
  // Test if that page exists as a function
  $page_function = 'page_'.$page_name;
  if (!function_exists($page_function)) {
    $page_function = 'page_home';
  }
  
  // Unauthenticated users get sent to our login screen unless they're already trying to use OAuth
  if (!user_is_authenticated() && $page_name !== 'oauth') {
    $page_function = 'page_login';
  }
  
  // Log page requests if there's a function defined in config.php for it
  if (function_exists('config_log_request')) {
    config_log_request();
  }

  // Call the page function
  $page_args = call_user_func($page_function, $query);
  
  // If a valid page response was received, render it
  if (is_array($page_args) && array_key_exists('title', $page_args)) {
    $html = theme('page', $page_args);
    ob_start('ob_gzhandler');
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
  }
  
  // That's all folks
  exit();
}

front_controller();

