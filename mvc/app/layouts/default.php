<html>
<head>
	<title><?php echo $pageTitle; ?></title>
</head>
<body>
<ul>
<?php foreach ($pageMenu as $page): if (substr($page, 0, 1) !== '_') : ?>
	<li><a href="<?php echo $page; ?>"><?php echo $page; ?></a></li>
<?php endif; endforeach; ?>
</ul>
<?php echo $pageContent; ?>
</body>
</html>