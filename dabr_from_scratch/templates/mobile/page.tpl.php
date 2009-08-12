<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo $title; ?></title>
  <base href="<?php echo BASE_URL; ?>" />
</head>
<body>
<ul>
  <li><a href="home">Home</a></li>
  <li><a href="trends">Trends</a></li>
  <li><a href="replies">Replies</a></li>
  <li><a href="oauth">OAuth login</a></li>
</ul>
<?php echo $content; ?>
</body>
</html>