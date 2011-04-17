<?php
/*
#doc-start
h1.  test_common

Created by  on 2010-02-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.
#doc-end
*/

if (!class_exists('Globals')) {
  require_once('Globals.php');
}
require_once('includes.php');

Globals::$images_root = 'images' . DIRECTORY_SEPARATOR;
set_include_path(Globals::$private_data_root . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'objects' . PATH_SEPARATOR .
  get_include_path());
date_default_timezone_set('America/Denver');

if (count($argv) >= 1) {
  $progname = array_shift($argv);

  $hlp = "$progname [-h/--help] <sqlite | sqlite3 | mysql | mysqli | postgresql>\n NOTE: '$progname help' may yield more help\n";
  $flag = TRUE;
  while ($flag && $argv) {
    $val = array_shift($argv);
    switch ($val) {
      case '-h': case '--help': echo $hlp ; exit(0);
      case 'sqlite':
      case 'sqlite3':
      case 'mysql':
      case 'mysqli':
      case 'prostgresql':
        $db_engine = $val;
        break;
     default:
        array_unshift($argv, $val) ;
        $flag = FALSE;
        break;
    }
  }
}

if (!isset($db_engine)) $db_engine =  'sqlite';
Globals::$db_type = $db_engine;
Globals::$db_params['db_engine'] = $db_engine;
echo "Testing with db_engine: $db_engine\n";
switch ($db_engine) {
  case 'sqlite':
    Globals::$db_params['dbname'] = 'sqlite_testdb';
    Globals::$db_params['recreate_database'] = TRUE;
    break;
  case 'sqlite3':
    Globals::$db_params['dbname'] = 'sqlite3_testdb';
    Globals::$db_params['recreate_database'] = TRUE;
    unlink('sqlite3_testdb');
    break;
  case 'mysql':
    Globals::$db_params['dbname'] = 'mysql_testdb';
    Globals::$db_params['host'] = '10.211.55.2';
    Globals::$db_params['port'] = '3306';
    Globals::$db_params['user'] = 'root';
    Globals::$db_params['password'] = '';
    Globals::$db_params['recreate_database'] = TRUE;
    break;
  case 'mysqli':
    Globals::$db_params['dbname'] = 'mysqli_testdb';
    Globals::$db_params['host'] = '10.211.55.2';
    Globals::$db_params['port'] = '3306';
    Globals::$db_params['user'] = 'root';
    Globals::$db_params['password'] = '';
    Globals::$db_params['recreate_database'] = TRUE;
  break;
  case 'postgresql':
    Globals::$db_params['dbname'] = 'pgsql_testdb';
    Globals::$db_params['host'] = 'localhost';
    Globals::$db_params['port'] = '5432';
    Globals::$db_params['user'] = 'postgres';
    Globals::$db_params['password'] = '';
    Globals::$db_params['recreate_database'] = TRUE;
    break;
}

require_once('dbaccess.php');

global $dbaccess;

$dbaccess = new DBAccess(Globals::$db_params);

// echo "test_common.php: db_engine: '$db_engine'\n";
// echo "test_common.php: dbaccess: $dbaccess\n";

Globals::$dbaccess = $dbaccess;

?>
