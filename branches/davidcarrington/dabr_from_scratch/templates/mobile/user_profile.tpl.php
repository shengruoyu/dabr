<table><tr><td><img src="<?php echo $user->profile_image_url ?>" alt="" /></td>
<td><b><a href='user/<?php echo $user->screen_name ?>'><?php echo $user->screen_name ?></a> (<?php echo $user->name ?>)</b>
<small>
<?php if ($user->verified == true): ?>
<br /><strong>Verified Account</strong>
<?php endif;
if ($user->protected == true): ?>
<br /><strong>Private/Protected Tweets</strong>
<?php endif; ?>
<br />Bio: <?php echo $user->description ?> 
<br />Link: <a href="<?php echo $user->url ?>"><?php echo $user->url ?></a>
<br />Location: <?php echo $user->location ?>
<br />Joined: <?php echo $user->created_at ?>
</small>
<br />
<?php echo $user->statuses_count ?> tweets
| <a href='followers/<?php echo $user->screen_name ?>'><?php echo $user->followers_count ?> followers</a>
<?php if (!$user->following): ?>
| <a href='follow/<?php echo $user->screen_name ?>'>Follow</a>
<?php else: ?>
| <a href='unfollow/<?php echo $user->screen_name ?>'>Unfollow</a>
<?php endif; ?>
| <a href='confirm/block/<?php echo $user->screen_name ?>/<?php echo $user->id ?>'>Block / Unblock</a>
| <a href='friends/<?php echo $user->screen_name ?>'><?php echo $user->friends_count ?> friends</a>
| <a href='favourites/<?php echo $user->screen_name ?>'><?php echo $user->favourites_count ?> favourites</a>
| <a href='directs/create/<?php echo $user->screen_name ?>'>Direct Message</a>
</td></table>

<?php if (is_array($timeline)) echo theme('timeline', compact('timeline')); ?>