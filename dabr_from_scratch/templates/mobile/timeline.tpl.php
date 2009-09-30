<?php

function format_interval($timestamp, $granularity = 1) {
  $units = array(
    'years' => 31536000,
    'days' => 86400,
    'hours' => 3600,
    'min' => 60,
    'seconds' => 1
  );
  $output = '';
  foreach ($units as $key => $value) {
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '').floor($timestamp / $value).' '.$key;
      $timestamp %= $value;
      $granularity--;
    }
    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : '0 seconds';
}

?>
<table class="timeline">
<tbody>
<?php foreach ($timeline as $tweet): ?>
<tr class="<?php echo ($i++ %2 ? 'even' : 'odd'); ?>">
  <td><img src="<?php echo $tweet->from->profile_image_url ?>" alt="" height="24" width="24" /></td>
  <td><strong><a href="user/<?php echo $tweet->from->screen_name; ?>"><?php echo $tweet->from->screen_name; ?></a></strong> <small><?php echo format_interval(time() - strtotime($tweet->created_at)); ?> ago from <?php echo $tweet->source; ?></small><br />
  <?php echo twitter_parse_tags($tweet->text); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo theme('pagination'); ?>