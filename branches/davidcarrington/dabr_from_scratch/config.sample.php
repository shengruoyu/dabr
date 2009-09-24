<?php

// OAuth consumer and secret keys. Available from http://twitter.com/oauth_clients
define('OAUTH_CONSUMER_KEY', '');
define('OAUTH_CONSUMER_SECRET', '');

// Base URL, should point to your website, including a trailing slash
// Can be set manually but the following code tries to work it out automatically.
$base_url = 'http://'.$_SERVER['HTTP_HOST'];
if ($directory = trim(dirname($_SERVER['SCRIPT_NAME']), '/\,')) {
  $base_url .= '/'.$directory;
}
define('BASE_URL', $base_url.'/');

// Database connection string in the following format
// mysql://username:password@server/database
define('DATABASE_DSN', '');

// Only include database and caching code if we've got a database
if (DATABASE_DSN) {
  require 'includes/db.php';
  require 'includes/caching.php';
  db_connect(DATABASE_DSN);
}

?>