<?php

function theme($template, $args = null) {
  // Cached list of templates available
  static $templates = array();
  
  // Hard coded theme for now. This would be in settings
  $current_theme = 'mobile';
  
  $template_directory = 'templates/'.$current_theme;
  if (empty($templates)) {
    // Populate the $templates cache with a quick directory scan
    $dh  = opendir($template_directory);
    while (false !== ($filename = readdir($dh))) {
      // Only .tpl.php files are valid templates
      if (substr($filename, -8) == '.tpl.php')
      $templates[] = substr($filename, 0, -8);
    }
  }
  
  // Check if the chosen template exists
  if (in_array($template, $templates)) {
    // Extract function is magic, converts everything in $args into
    // variables for the template to use.
    if (is_array($args)) {
      extract($args, EXTR_SKIP);
    }
    
    // Include the template and return the HTML
    // No HTML is rendered to the client here because of the ob_get_contents()
    ob_start(); 
    include $template_directory.'/'.$template.'.tpl.php';
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  } else {
    // Template doesn't exist in our cache
    trigger_error("<p>Error: template <b>$template</b> not found.</p>");
  }
}

?>