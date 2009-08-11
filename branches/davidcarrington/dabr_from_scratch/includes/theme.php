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

function theme_attributes($attributes) {
  if (!$attributes) return;
  foreach ($attributes as $name => $value) {
    $out .= " $name=\"$value\"";
  }
  return $out;
}

function theme_table($headers, $rows, $attributes = NULL) {
  $out = '<table'.theme_attributes($attributes).'>';
  if (count($headers) > 0) {
    $out .= '<thead><tr>';
    foreach ($headers as $cell) {
      $out .= theme_table_cell($cell, TRUE);
    }
    $out .= '</tr></thead>';
  }
  if (count($rows) > 0) {
    $out .= '<tbody>'.theme('table_rows', $rows).'</tbody>';
  }
  $out .= '</table>';
  return $out;
}

function theme_table_rows($rows) {
  $i = 0;
  foreach ($rows as $row) {
    if ($row['data']) {
      $cells = $row['data'];
      unset($row['data']);
      $attributes = $row;
    } else {
      $cells = $row;
      $attributes = FALSE;
    }
    $attributes['class'] .= ($attributes['class'] ? ' ' : '') . ($i++ %2 ? 'even' : 'odd');
    $out .= '<tr'.theme_attributes($attributes).'>';
    foreach ($cells as $cell) {
      $out .= theme_table_cell($cell);
    }
    $out .= "</tr>\n";
  }
  return $out;
}

function theme_table_cell($contents, $header = FALSE) {
  $celltype = $header ? 'th' : 'td';
  if (is_array($contents)) {
    $value = $contents['data'];
    unset($contents['data']);
    $attributes = $contents;
  } else {
    $value = $contents;
    $attributes = false;
  }
  return "<$celltype".theme_attributes($attributes).">$value</$celltype>";
}

?>