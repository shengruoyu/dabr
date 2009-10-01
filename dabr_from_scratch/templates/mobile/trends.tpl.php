<h2>Trends</h2>
<ul>
<?php foreach ($trends as $trend): ?>
  <li><a href="search?query=<?php echo urlencode($trend->query); ?>"><?php echo $trend->name; ?></a></li>
<?php endforeach; ?>
</ul>
