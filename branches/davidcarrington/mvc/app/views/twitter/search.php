<form method="get" action="search">
	<input name="q" value="<?php echo $searchTerm; ?>" /> <input type="submit" value="Search" />
</form>

<?php
echo View::_render('app/views/twitter/timeline.php', compact('timeline'));
?>