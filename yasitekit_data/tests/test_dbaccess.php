<?php
/*
#doc-start
h1.  test_dbaccess

Created by  on 2010-02-11.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/


class Globals {
  public static $private_data_root = '..';
  public static $images_root = NULL;
  public static $db_type = NULL;
  public static $db_params = NULL;
  public static $flag_exceptions_on = TRUE;
}
Globals::$images_root = 'images' . DIRECTORY_SEPARATOR;
set_include_path(Globals::$private_data_root . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system'. DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system'. DIRECTORY_SEPARATOR . 'objects' . PATH_SEPARATOR .
  get_include_path());
  

$data_connection_params = array(
  'illegal' => array('db_engine' => 'illegal', 'dbname' => 'bogus'),
  'none' => array('db_engine' => 'none', 'dbname' => 'none_testdb', 'recreate_database' => TRUE),
  'sqlite' => array('db_engine' => 'sqlite', 'dbname' => 'sqlite_testdb', 'recreate_database' => TRUE),
  'sqlite3' => array('db_engine' => 'sqlite3', 'dbname' => 'sqlite3_testdb', 'recreate_database' => TRUE),
  'mysql' => array('db_engine' => 'mysql', 'dbname' => 'mysql_testdb', 'user' => 'root', 'password' => '', 'host' => 'mike-shared', 'port' => '3306', 'recreate_database' => TRUE),
  'mysqli' => array('db_engine' => 'mysqli', 'dbname' => 'mysqli_testdb', 'user' => 'root', 'password' => '', 'host' => 'mike-shared', 'port' => '3306', 'recreate_database' => TRUE),
  'postgresql' => array('db_engine' => 'postgresql', 'dbname' => 'postgresql_testdb', 'user' => 'postgres', 'password' => '', 'host' => 'localhost', 'port' => '5432', 'recreate_database' => TRUE),
  );

$progname = array_shift($argv);
$hlp = "$progname [-h/--help] <none | sqlite | sqlite3 | mysql | mysqli | postgresql>\n";
$db_engine_list = array();
$verbose = FALSE;

// end Globals

// functions
require_once('test_functions.php');
// end functions


foreach ($argv as $val) {
  switch ($val) {
    case '-h': case '--help': echo $hlp ; exit(0);
    case '-v': case '--verbose': $verbose = TRUE; break;
    default:
      if (!in_array($val, array('none', 'sqlite', 'sqlite3', 'mysql', 'mysqli', 'postgresql', 'illegal'))) {
        echo "Illegal db_engine: '$val'\n";
        echo $hlp;
        exit(1);
      }
      $db_engine_list[] = $val;
      break;
  }
}
require_once('dbaccess.php');

if (count($db_engine_list) == 0) {
//  $db_engine_list = array('none', 'sqlite', 'sqlite3', 'mysql', 'mysqli', 'postgresql');
  $db_engine_list = DBAccess::available_db_engines();
  $missing_db_engine_list = NULL;
} else {
  $missing_db_engine_list = array_diff($db_engine_list, DBAccess::available_db_engines());
  $db_engine_list = array_intersect($db_engine_list, DBAccess::available_db_engines());
}

if ($missing_db_engine_list) {
  echo "The following Database Adaptors are Missing from this PHP installation\n and will NOT be tested: "
    . implode(', ', $missing_db_engine_list) . "\n";
}

foreach ($db_engine_list as $db_engine) {
  testReset();
  echo "=============================START {$db_engine}=======================\n";
  $old_error_reporting = error_reporting(0)& ~E_WARNING;
  set_exception_handler('ignore_exception');
  testException('new DBAccess(w/o parameters)', 'new DBAccess();');
  error_reporting($old_error_reporting);
  restore_exception_handler();
  $dbaccess = new DBAccess($data_connection_params[$db_engine], $verbose);
  echo "DBAccess as string: '$dbaccess'\n";
  testTrue("Testing Connection", $dbaccess->connectedP());

  echo "\nAttribute Tests\n";
  echo "Attributes: " . implode(', ', $dbaccess->attribute_names()) . "\n";
  $dbaccess->foo = 'bar';
  testTrue('test value of foo attribute', $dbaccess->foo == 'bar');
  testException('attribute bar does not exist throws exception', 'global $dbaccess; $x = $dbaccess->bar;');
  testTrue('isset($dbaccess->foo)', isset($dbaccess->foo));
  testFalse('isset($dbaccess->bar)', isset($dbaccess->bar));
  
  
//  $dbaccess->query('create table foo ( one text, two text)');
  $dbaccess->create_table('foo', array(array('one', 'varchar(255)', TRUE), array('two', 'text')), TRUE);
  //   echo "Unexpected Error: Test Failure:\n";
  //   echo $dbaccess->error() . "\n";
  //   $dbaccess-close();
  //   continue;
  // }
  testNoDBError('create_table(foo)', $dbaccess);

  $dbaccess->insert_into_table('foo', array('one' => 'one value', 'two' => 'two value'));
  testNoDBError('insert_into_table', $dbaccess);
  $quoted_value = 'another "two" \' \\ value';
  $dbaccess->insert_into_table('foo', array('one' => 'another value', 'two' => $quoted_value));
  testNoDBError('insert_into_table 2', $dbaccess);
  echo "Number of inserted rows: {$dbaccess->changes()}\n";
  testTrue("Inserted 1 rows", $dbaccess->changes() == 1);
  
  $tmp_ar = $dbaccess->select_from_table('foo', 'two', "one = 'another value'");
  testTrue("Quote Escaping works correctly", $quoted_value == $tmp_ar[0]['two']);
  
  $row_count = $dbaccess->rows_in_table('foo');
  testTrue('foo has two rows', $row_count == 2);
  $row_count = $dbaccess->rows_in_table('notable');
  testFalse('notable has not rows and does not exist', $row_count);

  $tmp = $dbaccess->select_from_table('foo');
  testTrue("select returned 2 rows", count($tmp) == 2);
  
  $dbaccess->update_table('foo', array('two' => 'three'));
  testTrue("updated 2 rows", $dbaccess->changes() == 2);

  // $dbaccess->delete_from_table('foo', 'one = \'another value\'');
  $dbaccess->delete_from_table('foo', array('two' => 'three'));
  testNoDBError('Deleted rows w/o error', $dbaccess);
  testTrue('deleted 2 rows from table', $dbaccess->changes() == 2);
  
  // putting some stuff back and checking
  echo "\nReloading foo table\n";
  $dbaccess->insert_into_table('foo', array('one' => 'one', 'two' => 'two'));
  testTrue('inserted 1 row', $dbaccess->changes() == 1);
  testTrue('retrieve 1 row', count($dbaccess->select_from_table('foo')) == 1);

  // syntax error test
  echo "\nError tests\n";
  $dbaccess->select_from_table('bad-name');
  testDBError('DB Catches Syntax Error', $dbaccess);

  // non existent table tests
  testFalse('table bar does not exist', $dbaccess->table_exists('bar'));
  $dbaccess->select_from_table('bar');
  testDBError('select from bar failed', $dbaccess);

  // $dba2 = new DBAccess($data_connection_params[$db_engine], $verbose);
  // echo "\nCreating second connection to same db - testing shared attributes\n";
  // testTrue('isset($dbaccess->foo)', isset($dbaccess->foo));
  // testTrue('isset($dba2->foo)', isset($dba2->foo));
  // testTrue('attributes shared: $dbaccess->foo == $dba2->foo', $dbaccess->foo == $dba2->foo);
  // echo "\nUnsetting Attribute Test: unset(\$dbaccess->foo)\n";
  // unset($dbaccess->foo);
  // testFalse('isset($dbaccess->foo)', isset($dbaccess->foo));
  // testFalse('isset($dba2->foo)', isset($dba2->foo));
  
  echo "\nRecreating attribute foo: \$dbaccess->foo == \"another foo\"\n";
  // $dba2->foo = 'another foo';
  testTrue('isset($dbaccess->foo)', isset($dbaccess->foo));
  // testTrue('$dbaccess->foo == "another foo"', $dbaccess->foo == "another foo");
  echo "\n";
  
  testTrue('$dbaccess->table_exists(foo)', $dbaccess->table_exists('foo'));
  // testTrue('$dba2->table_exists(foo)', $dba2->table_exists('foo'));
  // var_dump($dba2->select_from_table('foo'));
  testFalse('$dbaccess->table_exists(bar)', $dbaccess->table_exists('bar'));
  // testFalse('$dba2->table_exists(bar)', $dba2->table_exists('bar'));

  echo "\nClose Function Test\n";
  $dbaccess->register_close_function(create_function('$x', 'echo "In Function - arg $x\n";'), 'test one');
  $dbaccess->register_close_function(create_function('$x', 'echo "In Function - arg $x\n";'), 'test two');
  $dbaccess->register_close_function(create_function('$x', 'echo "In Function - arg $x\n";'), 'test three');

  $dbaccess->close();
  testFalse('$dbaccess->connectedP()', $dbaccess->connectedP());
  // testTrue('$dba2->connectedP()', $dba2->connectedP());

  testReport();

  echo "=============================END {$db_engine}=======================\n";
}

?>
