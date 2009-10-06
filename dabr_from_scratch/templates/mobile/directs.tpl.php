<ul>
<li><a href="directs/create">Create</a></li>
<li><a href="directs/inbox">Inbox</a></li>
<li><a href="directs/sent">Sent</a></li>
</ul>

<table class="directs">
<tbody>
<?php foreach ($timeline as $dm): ?>
<tr class="<?php echo ($i++ %2 ? 'even' : 'odd'); ?>">
  <td><img src="<?php echo $dm->from->profile_image_url ?>" alt="avatar" height="24" width="24" /></td>
  <td><strong><?php echo $dm->from->screen_name; ?></strong> <a href="directs/create/<?php echo $dm->from->screen_name; ?>">DM</a><br />
  <?php echo twitter_parse_tags($dm->text); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo theme('pagination'); ?>