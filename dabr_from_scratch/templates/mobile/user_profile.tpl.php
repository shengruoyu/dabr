<?php
$tweet = array_pop($timeline);
$user = $tweet->from;
$screen_name = $user->screen_name;
?>
<table><tr><td><img src="<?php echo $user->profile_image_url ?>" alt="" /></td>
<td><b><a href='user/<?php echo $screen_name ?>'><?php echo $screen_name ?></a> (<?php echo $user->name ?>)</b>
<small>
<br />Bio: <?php echo $user->description ?> 
<br />Link: <a href="<?php echo $user->url ?>"><?php echo $user->url ?></a>
<br />Location: <?php echo $user->location ?>
<br />Joined: <?php echo $user->created_at ?>
</small>
<br />
<?php echo $user->statuses_count ?> tweets
| <a href='followers/<?php echo $screen_name ?>'><?php echo $user->followers_count ?> followers</a>
| <a href='follow/<?php echo $screen_name ?>'>Follow</a>
| <a href='unfollow/<?php echo $screen_name ?>'>Unfollow</a>
| <a href='confirm/block/<?php echo $screen_name ?>/<?php echo $user->id ?>'>Block / Unblock</a>
| <a href='friends/<?php echo $screen_name ?>'><?php echo $user->friends_count ?> friends</a>
| <a href='favourites/<?php echo $screen_name ?>'><?php echo $user->favourites_count ?> favourites</a>
| <a href='directs/create/<?php echo $screen_name ?>'>Direct Message</a>
</td></table>

<?php echo theme('timeline', compact('timeline')); ?>