<form action='directs/send' method='post'>
<?php if ($target): ?>
  Sending direct message to <strong><?php echo $target; ?></strong><input name='to' value="<?php echo $target; ?>" type="hidden" />
<?php else: ?>
  To: <input name='to' />
<?php endif; ?>
<br /><textarea name='message' cols='50' rows='3' id='message'></textarea><br />
<input type='submit' value='Send' />
</form>