<?php

require 'includes/theme.php';
//~ require 'includes/oauth.php';

function page_home() {
  $content = '<p>Home</p>';
  echo theme('page', array(
    'content' => $content,
    'title' => 'Home',
  ));
}

function front_controller() {
  $query = (array) explode('/', $_GET['q']);
  $GLOBALS['page'] = $query[0];
  $page_function = 'page_'.$GLOBALS['page'];
  if (!function_exists($page_function)) {
    $page_function = 'page_home';
  }

  return call_user_func($page_function, $query);
}

front_controller();

?>