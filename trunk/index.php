<?php

if (file_exists('offline.html')) { readfile('offline.html'); exit(); }

include 'config.php';
include 'common/user.php';
include 'common/menu.php';
include 'common/theme.php';
include 'common/twitter.php';

menu_register(array(
  'about' => array(
    'callback' => 'about_page',
  ),
  'logout' => array(
    'security' => true,
    'callback' => 'logout_page',
  ),
));

function logout_page() {
  user_logout();
  $content = theme('logged_out');
  theme('page', 'Logged out', $content);
}

function about_page() {
  $content = file_get_contents('about.html');
  theme_page('About', $content);
}

menu_execute_active_handler();

?>