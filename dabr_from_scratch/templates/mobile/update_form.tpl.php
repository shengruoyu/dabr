<form method="post" action="update">
  <input type="hidden" name="in_reply_to_status_id" value="<?php echo $in_reply_to_status_id; ?>" />
  <textarea id="status" name="status" rows="3" style="width:100%; max-width: 400px;"><?php echo $status; ?></textarea><br />
  <input type="submit" value="Update" />
</form>