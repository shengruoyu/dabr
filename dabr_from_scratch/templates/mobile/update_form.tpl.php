<form method="post" action="update">
  <input type="hidden" name="in_reply_to_status_id" value="<?php echo $in_reply_to_status_id; ?>" />
  <textarea id="status" name="status" rows="3" style="width:100%; max-width: 400px;"><?php echo $status; ?></textarea><br />
  <input type="submit" value="Update" /> <span id="remaining">140</span>
</form> 
<script type="text/javascript">
function updateCount() 
{
  document.getElementById("remaining").innerHTML = 140 - document.getElementById("status").value.length;
  setTimeout(updateCount, 400);
}
updateCount();
</script>