<?php if (is_array($timeline)): ?>
<ul>
<?php foreach ($timeline as $status): ?>
<li>
	<strong><?php echo $status->user->screen_name; ?></strong> :
	<?php echo $status->text; ?></li>
</li>
<?php endforeach; // timeline tweets ?>
</ul>
<?php endif; // do we have a timeline? ?>