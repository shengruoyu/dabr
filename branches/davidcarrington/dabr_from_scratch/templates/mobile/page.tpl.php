<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>templates/mobile/mobile.css" />
  <base href="<?php echo BASE_URL; ?>" />
</head>
<body>
<?php if (user_is_authenticated()): ?>
<div id="menu" class="menu"><ul id="menu-main">
  <li><a href="home">Home</a></li>
  <li><a href="trends">Trends</a></li>
  <li><a href="mentions">Mentions</a></li>
  <li><a href="directs">Directs</a></li>
  <li><a href="search">Search</a></li>
  <li><a href="logout">Logout</a></li>
</ul></div>
<?php endif; ?>
<?php echo $content; ?>


<?php

$GLOBALS['time']['dabr'] = microtime(1) - $GLOBALS['time']['dabr'];
$GLOBALS['time']['api'] = array_sum((array) $GLOBALS['time']['api']);
$GLOBALS['time']['db'] = array_sum((array) $GLOBALS['time']['db']);
echo '<pre>';
print_r($GLOBALS['time']);

?>
</body>
</html>