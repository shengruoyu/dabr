<table class="timeline">
<tbody>
<?php foreach ($timeline as $tweet): ?>
<tr class="<?php echo ($i++ %2 ? 'even' : 'odd'); ?>">
  <td><img src="<?php echo $tweet->from->profile_image_url ?>" alt="" height="24" width="24" /></td>
  <td><strong><?php echo $tweet->from->screen_name; ?></strong><small> from <?php echo $tweet->source; ?></small><br />
  <?php echo twitter_parse_tags($tweet->text); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo theme('pagination'); ?>