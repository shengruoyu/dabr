<table class="users">
<tbody>
<?php foreach ($timeline as $user): ?>
<tr class="<?php echo ($i++ %2 ? 'even' : 'odd'); ?>">
<td><img src="<?php echo $user->profile_image_url ?>" alt="" height="24" width="24" /></td>
<td><strong><a href="user/<?php echo $user->screen_name; ?>"><?php echo $user->screen_name; ?></a></strong>
(<?php echo $user->name; ?>) - <?php echo $user->location; ?><br /><small>
<?php if ($user->description) echo $user->description."<br />"; ?>
<?php if ($source == 'followers' && $user->following) echo '<strong>Following, </strong>'; ?>
<?php echo $user->followers_count ?> followers, <?php echo $user->friends_count ?> friends, ~<?php echo twitter('tweets_per_day', $user) ?> tweets per day</small>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo theme('pagination'); ?>