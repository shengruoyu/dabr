<?php

/*
MAKING DABR INVITE ONLY

The function below would need to be added to your config.php file
and then you need to play around the with list of allowed users.
*/

function config_log_request() {
  // This function is called from menu.php if it exists
  
  // Ignore people that aren't logged in
  if (!user_is_authenticated()) return;
  
  // Create a *lowercase* list of allowed users
  $allowed_users = array(
    'twitter',
    'davidcarrington',
    'stephenfry',
  );
  // Or a file-based alternative if you prefer not to keep tampering with config.php:
  // $allowed_users = file('../allowed_users.php');
  
  // Check if the current user is in our allowed user list
  if (!in_array(strtolower(user_current_username()), $allowed_users)) {
    // They're not, kick them out!
    user_logout();
    die("Sorry, you're not on the list of allowed users for this site.");
  }
}

?>