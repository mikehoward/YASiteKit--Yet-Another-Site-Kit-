<?php
$prog_name = basename(array_shift($argv));
// $help_msg = "$prog_name [--force] subsystem1 ...\n";
$help_msg = "$prog_name subsystem1 ...\n";
$safe_flag = TRUE;

// while (count($argv)) {
//   $arg = array_shift($argv);
//   switch ($arg) {
//     case '--force': $safe_flag = FALSE; break;
//     default:
//       throw new Exception("Illegal Arg: $arg\n$help_msg\n");
//   }
// }

set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);

// create database if necessary
if (!Globals::$dbaccess->connectedP()) {
  throw new Exception("Unable to connect to database: " . Globals::$dbaccess);
}


// if ($safe_flag && Globals::$dbaccess->on_line == 'T') {
//   echo "Database is On-Line - aborting installation\n";
//   exit;
// }
if ($safe_flag && Globals::$dbaccess->database_valid != 'T') {
  echo "Database is Invalid - aborting Installing Subsystems\n";
  exit;
}

// Add basic infrastructure articles
require_once('includes.php');

// load and initialize all sub_systems
foreach ($argv as $fname) {
  $path = './sub_systems' . DIRECTORY_SEPARATOR . $fname;
  if (!file_exists($path)) {
    $path .= ".php";
    if (!file_exists($path)) {
      echo "Unable to find subsystem $fname\n";
      continue;
    }
  }
    
  echo "\nIncluding $fname\n";
  try {
    include($path);
  } catch (Exception $e) {
    echo "Include of $fname Failed:\n";
    echo $e . "\n";
  }
}

Globals::$dbaccess->database_valid = 'T';
Globals::$dbaccess->archive_stale = 'T';
Globals::$dbaccess->on_line = 'T';

echo Globals::$dbaccess->dump();

echo "Finished\n";
?>