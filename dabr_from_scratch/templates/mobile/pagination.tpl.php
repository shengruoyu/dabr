<?php
$page = intval($_GET['page']);
if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches)) {
  $query = $matches[0];
}
if ($page == 0) $page = 1;
$links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";

echo '<p>'.implode(' | ', $links).'</p>';
?>