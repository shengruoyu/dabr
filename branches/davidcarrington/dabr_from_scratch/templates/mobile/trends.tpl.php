<h2>Trends!</h2>
<ul>
<?php foreach ($trends as $trend): ?>
  <li><a href="search/<?php echo $trend; ?>"><?php echo $trend; ?></a></li>
<?php endforeach; ?>
</ul>
