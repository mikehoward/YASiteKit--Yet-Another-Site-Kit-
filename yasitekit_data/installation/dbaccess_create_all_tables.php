<?php
/*
  (c) Copyright 2010 Mike. All Rights Reserved. Licensed under GNU Lesser Public Licences, V3.
  See http://www.gnu.org/licences/lgpl.html for details
  
  This is a HACK which can be used to create all missing AClass database tables for a YASiteKit
  site.
*/

$prog_name = basename(array_shift($argv));
$help = "Usage: $prog_name [-f/--force]\n creates all tables in site\n force flag drops them first (DANGEROUS)\n" ;
$drop_first = FALSE;
while (count($argv)) {
  $arg = array_shift($argv);
  switch ($arg) {
    case '-h': case '--help': echo $help; exit(0);
    case '-f': case '--force': $drop_first = TRUE; break;
    default: echo $help ; exit(1);
  }
}

// get config file
$cwd = getcwd();
chdir('..');
set_include_path('.');
require_once('config.php');
require_once('includes.php');
require_once('archive_functions.php');

chdir($cwd);
set_include_path('..' . PATH_SEPARATOR . implode(PATH_SEPARATOR, $argv) . PATH_SEPARATOR . get_include_path());

require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);
$dbaccess = Globals::$dbaccess;

// run create_all_tables() non-destructively
require_once('aclass.php');

$object_file_list = array_merge(object_file_ar(Globals::$objects_root), object_file_ar(Globals::$system_objects),
    package_objects(Globals::$packages_root), package_objects(Globals::$system_packages));
// $object_file_list = array_map(create_function('$r', 'return $r[1];'), $ar);
$sort_result = usort($object_file_list, create_function('$a,$b', 'return $a[0]==$b[0] ? ($a[1]==$b[1]?0:($a[1]<$b[1] ? -1 : 1)) : ($a[0]<$b[0] ? -1 : 1);'));
var_dump($sort_result);
var_dump($object_file_list);
$exists_count = 0;
$success_count = 0;
$fail_count = 0;
foreach ($object_file_list as $row) {
  list($object_name, $include_path) = $row;
  require_once($include_path);

  // skip if this is not an AClass
  if (!AClass::existsP($object_name)) {
    echo "Class $object_name is not derived from AClass\n";
    continue;
  }
   $aclass = AClass::get_class_instance($object_name);
  if (Globals::$dbaccess->table_exists($aclass->tablename)) {
    echo "Table for $object_name already exists\n";
    $exists_count += 1;
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
echo "Created $success_count, Failed to create $fail_count, Pre-Existing $exists_count\n";