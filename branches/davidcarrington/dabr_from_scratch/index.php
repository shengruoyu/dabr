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
  $query = (array) explode('/', $_GET['q']);
  $GLOBALS['page'] = $query[0];
  $page_function = 'page_'.$GLOBALS['page'];
  if (!function_exists($page_function)) {
    $page_function = 'page_default';
  }

  $page_args = call_user_func($page_function, $query);
  
  if (is_array($page_args) && array_key_exists('title', $page_args)) {
    $html = theme('page', $page_args);
    ob_start('ob_gzhandler');
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
  }
  
  exit();
}

front_controller();

?>