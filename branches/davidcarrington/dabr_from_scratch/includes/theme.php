<?php

function theme($template, $args) {
  static $templates = array();
  
  $current_theme = 'mobile';
  
  $template_directory = 'templates/'.$current_theme;
  if (empty($templates)) {
    $dh  = opendir($template_directory);
    while (false !== ($filename = readdir($dh))) {
      if (substr($filename, -8) == '.tpl.php')
      $templates[] = substr($filename, 0, -8);
    }
  }
  if (in_array($template, $templates)) {
    extract($args, EXTR_SKIP);
    ob_start(); 
    include $template_directory.'/'.$template.'.tpl.php';
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  } else {
    $function = 'theme_'.$template;
    $custom_function = $current_theme.'_'.$function;
    if (!function_exists($custom_function))
      $function = $custom_function;
    if (!function_exists($function)) return "<p>Error: theme template <b>$template</b> not found.</p>";
    return call_user_func_array($function, $args);
  }
  return $output;
}