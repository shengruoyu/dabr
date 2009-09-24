<?php

define('DB_QUERY_REGEXP', '/(%d|%s|%%|%f|%b)/');

function db_set_active($name) {
  global $db_url, $db_type, $active_db;
  static $db_conns;
  if (!isset($db_conns[$name])) {
    // Initiate a new connection, using the named DB URL specified.
    if (is_array($db_url)) {
      $connect_url = array_key_exists($name, $db_url) ? $db_url[$name] : $db_url['meta'];
    } else {
      $connect_url = $db_url;
    }
    $db_type = substr($connect_url, 0, strpos($connect_url, '://'));
    $db_conns[$name] = db_connect($connect_url);
  }
  $previous_db = $active_db;
  $active_db = $db_conns[$name];
  return array_search($previous_db, $db_conns);
}

function db_connect($url) {
  global $active_db;
  $url = parse_url($url);
  $url['user'] = urldecode($url['user']);
  $url['pass'] = urldecode($url['pass']);
  $url['host'] = urldecode($url['host']);
  $url['path'] = urldecode($url['path']);

  if (isset($url['port'])) {
     $url['host'] = $url['host'] .':'. $url['port'];
  }
  $active_db = mysql_connect($url['host'], $url['user'], $url['pass']);
  if (!$active_db) {
    trigger_error("Failed connecting to database server: {$url['host']}", E_USER_ERROR);
  }
  $path = substr($url['path'], 1);
  if (!mysql_select_db($path)) {
    trigger_error("Failed selecting database: {$path}",E_USER_ERROR);
  }
  return $active_db;
}

function db_query($query) {
  $args = func_get_args();
  array_shift($args);
  if (isset($args[0]) and is_array($args[0])) {
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  return _db_query($query);
}

function db_escape_string($text) {
  global $active_db;
  return mysql_real_escape_string($text, $active_db);
}

function db_error() {
  global $active_db;
  return mysql_errno($active_db);
}

function db_get_assoc($result) {
  $rows = array();
  while ($row = db_fetch_array($result)) {
    $rows[array_shift($row)] = array_shift($row);
  }
  return $rows;
}

function db_result($result, $row = 0) {
  if ($result && mysql_num_rows($result) > $row) {
    return mysql_result($result, $row);
  }
}

function db_latest() {
  global $active_db;
  return mysql_insert_id($active_db);
}

function db_num_rows($result) {
  if ($result) {
    return mysql_num_rows($result);
  }
}

function db_fetch_array($result) {
  if ($result) {
    return mysql_fetch_array($result, MYSQL_ASSOC);
  }
}

function db_fetch_object($result) {
  if ($result) {
    return mysql_fetch_object($result);
  }
}

function _db_query($query, $debug = 0) {
  global $active_db;

  $db_start = microtime(1);
  $result = mysql_query($query, $active_db);
  $GLOBALS['time']['db'][$query] = microtime(1) - $db_start;

  if ($debug) {
    print '<p>query: '. $query .'<br />error:'. mysql_error($active_db) .'</p>';
  }

  if (!mysql_errno($active_db)) {
    return $result;
  }
  else {
    trigger_error(mysql_error($active_db) ."\nquery: ". $query, E_USER_WARNING);
    return FALSE;
  }
}

function _db_query_callback($match, $init = FALSE) {
  static $args = NULL;
  if ($init) {
    $args = $match;
    return;
  }

  switch ($match[1]) {
    case '%d':
      return (int) array_shift($args);
    case '%s':
      return db_escape_string(array_shift($args));
    case '%%':
      return '%';
    case '%f':
      return (float) array_shift($args);
    case '%b': // binary data
      return db_encode_blob(array_shift($args));
  }
}

?>