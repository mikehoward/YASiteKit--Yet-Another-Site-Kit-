<?php
/*
#doc-start
h1.  test_common

Created by  on 2010-02-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.
#doc-end
*/


set_include_path('..');
require_once('config.php');
Globals::$private_data_root = '..';
Globals::$images_root = 'images' . DIRECTORY_SEPARATOR;
set_include_path(Globals::$private_data_root . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'objects' . PATH_SEPARATOR .
  get_include_path());
date_default_timezone_set('America/Denver');

if (count($argv) >= 1) {
  $progname = array_shift($argv);

  $hlp = "$progname [-h/--help] [--db-engine <sqlite sqlite3 | mysql | mysqli | postgresql>] Object-Name\n";
  $idx = 0;
  while ($idx < count($argv)) {
    $val = $argv[$idx];
    switch ($val) {
      case '-h': case '--help': echo $hlp ; exit(0);
      case '-d': case '--db-engine':
        $idx += 1;
        $db_engine = $argv[$idx];
        if (!in_array($db_engine, array('sqlite', 'sqlite3', 'mysql', 'mysqli', 'postgresql'))) {
          echo "Illegal mode: $db_engine\n";
          echo $hlp;
          exit(1);
        }
        break;
      default:
        $object_name = $val;
        break 2;
    }
    $idx += 1;
  }
}

if (!isset($object_name)) {
  if (isset($_REQUEST['object'])) {
    $object_name = $_REQUEST['object'];
  } else {
    echo "No Object Name specified\n";
    echo $hlp;
    var_dump($_REQUEST);
    exit(1);
  }
}

if (!isset($db_engine)) $db_engine = 'mysql'; // 'sqlite';
Globals::$db_type = $db_engine;
Globals::$db_params['db_engine'] = $db_engine;
echo "Testing $object_name with db_engine: $db_engine\n";

switch ($db_engine) {
  case 'sqlite':
    Globals::$db_params['dbname'] = 'sqlite_testdb';
    unlink('sqlite_testdb');
    break;
  case 'sqlite3':
    Globals::$db_params['dbname'] = 'sqlite3_testdb';
    unlink('sqlite3_testdb');
    break;
  case 'mysql':
    Globals::$db_params['dbname'] = 'mysql';
    Globals::$db_params['host'] = '10.211.55.2';
    Globals::$db_params['port'] = '3306';
    Globals::$db_params['user'] = 'root';
    Globals::$db_params['password'] = '';
    break;
  case 'mysqli':
    Globals::$db_params['dbname'] = 'mysql';
    Globals::$db_params['host'] = '10.211.55.2';
    Globals::$db_params['port'] = '3306';
    Globals::$db_params['user'] = 'root';
    Globals::$db_params['password'] = '';
  break;
  case 'postgresql':
    Globals::$db_params['dbname'] = 'template1';
    Globals::$db_params['host'] = 'localhost';
    Globals::$db_params['port'] = '5432';
    Globals::$db_params['user'] = 'postgres';
    Globals::$db_params['password'] = '';
    break;
}

require_once('dbaccess.php');

global $dbaccess;

$dbaccess = new DBAccess(Globals::$db_params);

switch ($db_engine) {
  case 'sqlite':
    $reopen = FALSE;
    break;
  case 'sqlite3':
    $reopen = FALSE;
    break;
  case 'mysql':
    $dbaccess->query('drop database mysql_testdb');
    $dbaccess->query('create database mysql_testdb');
    Globals::$db_params['dbname'] = 'mysql_testdb';
    $reopen = TRUE;
    break;
  case 'mysqli':
    $dbaccess->query('drop database mysqli_testdb');
    $dbaccess->query('create database mysqli_testdb');
    Globals::$db_params['dbname'] = 'mysqli_testdb';
    $reopen = TRUE;
    break;
  case 'postgresql':
    $dbaccess->query('drop database pgsql_testdb');
    echo $dbaccess->error() . "\n";
    $dbaccess->query('create database pgsql_testdb');
    echo $dbaccess->error() . "\n";
    Globals::$db_params['dbname'] = 'pgsql_testdb';
    $reopen = TRUE;
    break;
  
}

if ($reopen) {
  echo "closing $dbaccess\n";
  $dbaccess->close();
  $dbaccess = new DBAccess(Globals::$db_params);
  echo "opened $dbaccess\n";
}
Globals::$dbaccess = $dbaccess;
echo "db-engine: $dbaccess\n";

// have to require object here, after database work has finished
// so that the object has access to Globals::$dbaccess
require_once($object_name . ".php");
$object_instance = AClass::get_class_instance($object_name);
echo $object_instance->dump("Dump of class $object_name");

$_COOKIE[Globals::$user_cookie_name] = 'COOKIEVALUE';
Globals::$user_cookie_value = 'COOKIEVALUE';
require_once('CookieTrack.php');
$all_cookie_chars = implode('', CookieTrack::$cookie_letters);
if ($all_cookie_chars != $dbaccess->escape_string($all_cookie_chars)) {
  echo "ERROR: Bad Cookie Character - doesn't escape well for database $dbaccess\n";
  echo "all_cookie_chars: $all_cookie_chars\n";
  echo "dbaccess->escape_string($all_cookie_chars): " . $dbaccess->escape_string($all_cookie_chars). "\n";
} else {
  echo "PASS: Cookie Characters Escape Escaping on $dbaccess\n";
}
Globals::$cookie_track = new CookieTrack(Globals::$dbaccess, array('cookie' => Globals::$user_cookie_value));

require_once('aclass.php');
require_once($object_name . '.php');
AClass::get_class_instance($object_name)->create_table($dbaccess, TRUE);
$test_object_values_name = "test_" . strtolower($object_name) . "_values";

global $$test_object_values_name;

$test_object = new $object_name($dbaccess, $$test_object_values_name);
echo "<pre>\n";
echo $test_object->dump("Testing  $object_name");
echo "</pre>\n";

echo $test_object->render();
echo "\n";
echo $test_object->form();

switch ($object_name) {
  case 'Account':
    $test_object->password = 'foo password';
    echo $test_object->verify_password('foo password') ? "Account: PASS: Verify Password Works" :
      'Account: ERROR: Verify Password Failed\n';
    break;
}

// end global variables


?>
