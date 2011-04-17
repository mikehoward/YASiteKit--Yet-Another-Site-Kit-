<?php

// global variables
$script_name = array_shift($argv);
$force_flag = FALSE;
$usage = "Usage: $script_name [-f/--force] <path to dump dir>\n";
// end global variables

// strip off name of script
if (!$argv) {
  echo $usage;
  return;
}


while (($arg = array_shift($argv))) {
  switch ($arg) {
    case '-f':
    case '--force': $force_flag = TRUE ; break;
    case '-h':
    case '--help': echo $usage ; return;
    default: $dump_dir = $arg ; break 2;
  }
}
if ($argv) {
  echo "$usage\n";
  echo "Extra arguments: " . implode(', ', $argv) . "\n";
  return;
}
echo "Initializing Database using Dump in $dump_dir\n";

// get config file
$cwd = getcwd();
chdir('..');
set_include_path('.');
require_once('config.php');
chdir($cwd);
// echo Globals::dump();

require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);
print "Using Database: " . (string)Globals::$dbaccess . "\n";

require_once('archive_functions.php');

if (($str = dump_dir_ok_readable($dump_dir)) !== TRUE) {
  echo "$str\n";
  return;
} else {
  echo "Dump Directory is OK to Restore From\n";
}

// check to see if database is already initialized
echo "Checking to see if database already initialized\n";
if (Globals::$dbaccess->table_exists('sessions')) {
  echo "Sessions table already exists\n";
  if (!$force_flag) {
    echo " - assuming database currently initialized and Aborting\n";
    echo "Use -f or --force flag to Force Reinitialization\n";
    return;
  } else {
    echo "--force flag is TRUE - Proceeding to ReInitialize Database\n";
  }
} else {
  echo "Creating Database from dump in $dump_dir\n";
}

require_once('includes.php');

echo rebuild_infrastructure(Globals::$dbaccess, $dump_dir, $force_flag);
echo reload_database(Globals::$dbaccess, $dump_dir, Globals::$private_data_root, $force_flag);
?>