<?php
$prog_name = basename(array_shift($argv));
$help_msg = "$prog_name [--force]\n";
$safe_flag = TRUE;

while (count($argv)) {
  $arg = array_shift($argv);
  switch ($arg) {
    case '--force': $safe_flag = FALSE; break;
    default:
      throw new Exception("Illegal Arg: $arg\n$help_msg\n");
  }
}

set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);

// create database if necessary
if (!Globals::$dbaccess->connectedP()) {
  switch (Globals::$db_params['db_engine']) {
    case 'sqlite':
    case 'sqlite3':
      break;
    case 'mysql':
    case 'mysqli':
      $foo = Globals::$db_params;
      $foo['dbname'] = 'mysql';
      $dba = new DBAccess($foo);
      $dba->create_database(Globals::$db_params['dbname']);
      $dba->close();
      Globals::$dbaccess = new DBAccess(Globals::$db_params);
      break;
    case 'postgresql':
      $foo = Globals::$db_params;
      $foo['dbname'] = 'template1';
      $dba = new DBAccess($foo);
      $dba->create_database(Globals::$db_params['dbname']);
      $dba->close();
      Globals::$dbaccess = new DBAccess(Globals::$db_params);
      break;
    case 'mongodb':
    default:
      throw new Exception("Illegal Database Engine: " . Globals::$db_params['db_engine']);
  }
  
  if (!Globals::$dbaccess->connectedP()) {
    throw new Exception("Unable to Create Database " . Globals::$dbaccess->error());
  }
}


if ($safe_flag && Globals::$dbaccess->on_line == 'T') {
  echo "Database is On-Line - aborting bootstrap\n";
  exit;
}
if ($safe_flag && Globals::$dbaccess->database_valid == 'T') {
  echo "Database is Valid - aborting bootstrap\n";
  exit;
}

// Add basic infrastructure articles
require_once('includes.php');

if ($safe_flag && Globals::$dbaccess->table_exists(AnInstance::ACLASS_HASHES_TABLENAME)) {
  echo "Hashes Table Exists - aborting bootstrap\n";
  return;
}
// non-destructive attempt to create _aclass_hashes
AClass::create_aclass_hashes_table(Globals::$dbaccess, FALSE);

require_once('session.php');
if (!Globals::$dbaccess->table_exists(Session::TABLENAME)) {
  Globals::$dbaccess->create_table(Session::TABLENAME, Session::$field_definitions);
}

// Create all data tables - even those we won't use
// require_once('aclass.php');
// run create_all_tables() non-destructively

require_once('aclass.php');
$ar_tmp = array_merge(scandir(Globals::$objects_root), scandir(Globals::$system_objects));
$object_file_list = array_unique(array_filter($ar_tmp, create_function('$s', 'return preg_match("/.php$/", $s);')));
sort($object_file_list);
$success_count = 0;
$fail_count = 0;
foreach ($object_file_list as $object_file) {
  if (!stream_resolve_include_path($object_file)) {
    echo "Cannot resolve include path to $object_file - skipping\n";
    continue;
  }
  require_once($object_file);
  $object_name = basename($object_file, '.php');
  // skip if this is not an AClass
  if (!AClass::existsP($object_name)) {
    echo "Class $object_name is not derived from AClass\n";
    continue;
  }
  $aclass = AClass::get_class_instance($object_name);
  if (Globals::$dbaccess->table_exists($aclass->tablename)) {
    echo "Table for $object_name already exists\n";
  } else {
    try {
      $aclass->create_table(Globals::$dbaccess, FALSE, TRUE);
      echo "Created Table for object $object_name\n";
      $success_count += 1;
    } catch (Exception $e) {
      $fail_count += 1;
    }
  }
}
echo "Created $success_count, Failed to create $fail_count\n";


// create admin account
// require_once('Account.php');
echo "Creating Admin Account with password admin\n";
if (!AnInstance::existsP('Account', Globals::$dbaccess, 'admin')) {
  $acnt = new Account(Globals::$dbaccess, array('userid' => 'admin'));
  $acnt->name = 'Admin';
  $acnt->set_password('admin');
  $acnt->authority = 'X';
  $acnt->state = 'A';
  $acnt->failed_login_attempts = 0;
  $acnt->save();
}

// echo $acnt->dump();

// load and initialize all sub_systems
foreach (scandir('./sub_systems') as $fname) {
  if (preg_match('/.php$/', $fname)) {
    echo "\nIncluding $fname\n";
    try {
      include('./sub_systems' . DIRECTORY_SEPARATOR . $fname);
    } catch (Exception $e) {
      echo "Include of $fname Failed:\n";
      echo $e . "\n";
    }
  }
}

Globals::$dbaccess->database_valid = 'T';
Globals::$dbaccess->archive_stale = 'T';
Globals::$dbaccess->on_line = 'T';

echo Globals::$dbaccess->dump();

echo "Finished\n";
?>