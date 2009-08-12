<table class="timeline">
<tbody>
<?php foreach ($tweets as $tweet): ?>
<tr>
  <td><img src="<?php echo $tweet->user->profile_image_url ?>" alt="avatar" /></td>
  <td><strong><?php echo $tweet->user->screen_name; ?></strong><small> from <?php echo $tweet->source; ?></small><br />
  <?php echo $tweet->text; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
