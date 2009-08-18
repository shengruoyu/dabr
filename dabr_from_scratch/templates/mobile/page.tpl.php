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
  <li><a href="replies">Replies</a></li>
  <li><a href="directs">Directs</a></li>
  <li><a href="logout">Logout</a></li>
</ul></div>
<form method="post" action="update">
  <textarea id="status" name="status" rows="3" style="width:100%; max-width: 400px;"></textarea><br />
  <input type="submit" value="Update" />
</form>
<?php endif; ?>
<?php echo $content; ?>
</body>
</html>