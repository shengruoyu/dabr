<?php

if ($_GET['debug']) {
  print_r($timeline);
  die();
}

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
<td><strong><a href="user/<?php echo $tweet->from->screen_name; ?>"><?php echo $tweet->from->screen_name; ?></a></strong>
<a href="user/<?php echo $tweet->from->screen_name; ?>/reply/<?php echo $tweet->id; ?>">@</a>
<a href="retweet/<?php echo $tweet->id; ?>">RT</a>
<?php if ($tweet->favorited): ?>
<a href="unfavourite/<?php echo $tweet->id; ?>">UNFAV</a>
<?php else: ?>
<a href="favourite/<?php echo $tweet->id; ?>">FAV</a>
<?php endif; ?>
<?php if (user_is_current_user($tweet->from->screen_name)): ?>
<a href="confirm/delete/<?php echo $tweet->id; ?>">DEL</a>
<?php else: ?>
<a href="directs/create/<?php echo $tweet->from->screen_name; ?>">DM</a>
<?php endif; ?>
<small><?php echo format_interval(time() - strtotime($tweet->created_at)); ?> ago</small><br />
<?php echo twitter_parse_tags($tweet->text); ?> <small>from <?php echo $tweet->source; ?>
<?php if ($tweet->in_reply_to_status_id): ?>
 in reply to <a href="status/<?php echo $tweet->in_reply_to_screen_name; ?>/<?php echo $tweet->in_reply_to_status_id; ?>"><?php echo $tweet->in_reply_to_screen_name; ?></a>
<?php endif; ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo theme('pagination'); ?>