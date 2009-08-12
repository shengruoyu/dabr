<?php

require 'config.php';
require 'includes/oauth.php';
require 'includes/theme.php';
require 'includes/twitter.php';

if (!function_exists('page_default')) {
  function page_default() {
    return array(
      'title' => 'Home',
      'content' => '<p>This is a temporary page, to be replaced by a nice Twitter login.</p>',
    );
  }
}

function front_controller() {
  // Our .htaccess file and ModRewrite send all page requests through here
  // Find the name of the page to use
  $query = (array) explode('/', $_GET['q']);
  $page_name = $query[0];
  
  // Test if that page exists as a function
  $page_function = 'page_'.$page_name;
  if (!function_exists($page_function)) {
    $page_function = 'page_default';
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

?>