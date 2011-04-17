<?php
/*
#doc-start
h1. archive_functions.php - functions used to dump and recreate database

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 
Licensed under GNU LGPL Version 3

This is a collection of functions used to dump and recreate the database. It is
used in 'ReloadDB.php' and various command line scripts.

h2. Functions

* dump directory checkers. All return TRUE if the directory passes the tests, otherwise
and error string. So, to use them, use the tripple equals or not: = = = or ! = =
** dump_dir_readable($dump_dir) - checks to make sure _$dump_dir_ exists, is a directory, and
is readable.
** dump_dir_writable($dump_dir) - makes sure dump_dir is both readable and writable.
** dump_dir_ok($dump_dir) - checks _$dump_dir_ for existence, readability, writability
and the existence of required infrastructure files.
** dump_dir_ok_readable($dump_dir) - same as _dump_dir_ok()_ except it does not check for
writability.
* backup_database_archive($dump_dir) - creates a subdirectory of _$dump_dir_ and copies all files to it.
The subdirectory name is the date formed as YYYYMMDDHHmmss.x, where x is an integer starting with 0
and is used to resolve conflicts when attempting to create two directories within the same second.
* package_objects() and object_file_ar() are service routines used by _make_database_archive()_.
They both return arrays of array(object-name, include-path) - where _include-path_ is
the correct relative path to put in a PHP _include_ or _require_ directive.
** package_objects($package_root) - finds all object files in package directories of
_$package_root_. Returns an array of array(object-name, include-path).
** object_file_ar($dir, $include_prefix = '') - scans _$dir_ for files which conform to the
YASiteKit object file syntax - ^[A-Z]\w+\.php$ - and returns an array of array(object-name, include-path),
where _object-name_ is the file name with the _.php_ suffix removed and _include-path_
is the proper relative path to use in a PHP _include_ or _require_ statement. [if _$include_prefix_
is empty, then it is the file name; if _$include_prefix_ is non-empty then it is used
to construct the relative path]
* make_database_archive($dbaccess, $dump_dir, $private_data_root) - creates an archive of the specified
_$dbaccess_ in the specified dump directory (_$dump_dir_) for all persistent objects in
_$private_data_root / objects_ and _$private_data_root / system / objects_.
Returns TRUE on success and FALSE on failure. Echo's output wrapped in a &lt;div&gt; in class _dump-output_.
** $dbaccess - must be an open DBAccess object
** $dump_dir - must be a directory path. The function will attempt to create the directory if it
does not exist.
** $private_data_root - must be a directory path. It is expected to contain two subdirectories:
*** private_data_root / objects - which contains site-specific objects
*** private_data_root / system / objects - which contains YASiteKit system objects.
*** NOTE: it does not matter if a site-specific object shadows a YASiteKit system object. The
site-specific object definition will be saved - because of include order - and the data in the
database will correctly match.
* rebuild_infrastructure($dbaccess, $dump_dir, $drop_first) - recreates infrastructure files
for site using the files: '_aclass_attribute_defs.php', '_aclass_create_tables.php',
'_encryptor.php', '_join_tables.php', '_sessions.php', 'Parameters.dump'.
_$drop_first_ is a boolean which controls whether the tables are dropped and recreated.
* reload_database($dbaccess, $dump_dir, $private_data_root, $drop_first) - Reloads the database
data from all of the '.dump' files in the dump directory. Creates a new archive after successfully
reloading the data. _$drop_first_ is a boolean which controls whether tables are dropped and
recreated.

#doc-end
*/

// global variables & requires
require_once('dbaccess.php');
require_once('aclass.php');
require_once('VersionObj.php');
global $infrastructure_files;
$infrastructure_files = array(
    '_aclass_attribute_defs.php',
    '_aclass_create_tables.php',
    '_encryptor.php',
    '_join_tables.php',
    '_sessions.php',
    'Parameters.dump',
  );

// end global variables & requires

// function definitions

function dump_dir_readable($dump_dir)
{
  if (!$dump_dir) $dump_dir = Globals::$dump_dir;
  if (!file_exists($dump_dir)) return "Dump Directory $dump_dir does not exist";
  if (!is_dir($dump_dir)) return "Dump Directory $dump_dir is Not a directory";
  if (!is_readable($dump_dir)) return "Dump Directory $dump_dir is not readable";
  return TRUE;  
}

function dump_dir_writable($dump_dir)
{
  if (($str = dump_dir_readable($dump_dir)) !== TRUE) return $str;
  if (!is_writable($dump_dir)) return "Dump Directory $dump_dir is not writable";
  return TRUE;
}

function dump_dir_ok($dump_dir)
{
  global $infrastructure_files;
  
  if (($str = dump_dir_writable($dump_dir)) !== TRUE) return $str;
  foreach ($infrastructure_files as $fname) {
    if (!file_exists($dump_dir . DIRECTORY_SEPARATOR . $fname)) return "Required infrastructure file $fname is missing from $dump_dir";
  }
  return TRUE;
}

function dump_dir_ok_readable($dump_dir)
{
  global $infrastructure_files;
  
  if (($str = dump_dir_readable($dump_dir)) !== TRUE) return $str;
  foreach ($infrastructure_files as $fname) {
    if (!file_exists($dump_dir . DIRECTORY_SEPARATOR . $fname)) return "Required infrastructure file $fname is missing from $dump_dir";
  }
  return TRUE;
}

function backup_database_archive($dump_dir)
{
  try {
    $version_obj = VersionObj::get_from_file($dump_dir . DIRECTORY_SEPARATOR . 'version');
  } catch (VersionObjException $e) {
    $version_obj = VersionObj::initialize_versioning(TRUE, Globals::$site_id);
    $version_obj->write(VersionObj::versioning_path($dump_dir));
  }
  
  $path = $dump_dir . DIRECTORY_SEPARATOR . "$version_obj";

  $str = "Backing up files in $dump_dir by moving them to $version_obj\n";
  if (!file_exists($path) && !mkdir($path, 0755)) {
    $str .= "Unable to make backup directory: $path\n";
    echo $str;
    return FALSE;
  }
  $success = TRUE;
  $dir_name = dirname($path);
  foreach (scandir($dump_dir) as $fname) {
    if (is_file($dump_dir . DIRECTORY_SEPARATOR . $fname)) {
      $tmp = copy($dump_dir . DIRECTORY_SEPARATOR . $fname, $path . DIRECTORY_SEPARATOR . $fname);
      $str .= $tmp ? "copied $fname to $dir_name\n" : "Failed to move $fname to $dir_name\n";
      if (!$tmp) $success = FALSE;
    }
  }
  echo $str;
  return $success;
} // end of backup_database_archive()

function package_objects($package_root) {
  static $cache = array();
  
  if (array_key_exists($package_root, $cache)) {
    return $cache[$package_root];
  }
  
  $pwd = getcwd();
  chdir($package_root);
  $ar = array();
  foreach (scandir($package_root) as $dir) {
    foreach (object_file_ar($dir, $dir) as $tmp) {
      $ar[] = $tmp;
    }
  }
  chdir($pwd);
  return ($cache[$package_root] = $ar);
} // end of package_objects()

function object_file_ar($dir, $include_prefix = '') {
  if ($include_prefix) {
    $include_prefix .= DIRECTORY_SEPARATOR;
  }
  $ar = array();
  foreach (scandir($dir) as $fname) {
    if (preg_match('/^[A-Z]\w*\.php$/', $fname)) {
      $obj_name = preg_replace('/\.php$/', '', $fname);
      $ar[] = array($obj_name, $include_prefix . $fname);
    }
  }
  return $ar;
} // end of object_file_ar()

function make_database_archive($dbaccess, $dump_dir, $private_data_root)
{
  require_once('StateMgt.php');
  // This uses exceptions in the lower level i/o routines to abort the dump process
  //  if an i/o error occurs. If an error happens when opening a file, writing a string
  //  or if the finished file is not the correct length, then the responsible routine
  //  throws and exception which is caught in the 'catch' code and this function returns FALSE.
  ob_start();
  echo "<div class=\"dump-output\">\nDumping Database $dbaccess to $dump_dir\n";
  if (!($dbaccess instanceof DBAccess)) {
    echo "Database parameter is not an instance of DBAccess\n";
    echo ob_get_clean() . "</div>\n";
    return FALSE;
  }
  
  if (!is_dir($dump_dir)) {
    mkdir($dump_dir);
    if (!is_dir($dump_dir)) {
      echo "Dump Directory does not exist and cannot be created\n";
      echo ob_get_clean() . "</div>\n";
      return FALSE;
    }
  }

  if ($dbaccess->database_valid != 'T') {
    echo "Database is not in Valid State: Skipping Archive\n";
    echo ob_get_clean() . "</div>\n";
    return FALSE;
  }
  if ($dbaccess->on_line != 'F') {
    echo "Database not Off-Line Skipping Archive\n";
    echo ob_get_clean() . "</div>\n";
    return FALSE;
  }

  // backup any currently existing archive
  if (!backup_database_archive($dump_dir)) {
    echo "Unable to backup Current Archive\n";
    echo ob_get_clean() . "</div>\n";
    return FALSE;
  }

  // this simply creates a list of all objects in the system
  $ar = array_merge(object_file_ar(Globals::$objects_root), object_file_ar(Globals::$system_objects),
      package_objects(Globals::$packages_root), package_objects(Globals::$system_packages));

  $obj_include_fname_list = array();
  $obj_name_list = array();
  foreach ($ar as $tmp) {
    $obj_name_list[] = $tmp[0];
    $obj_include_fname_list[$tmp[0]] = $tmp[1];
  }
  
  // update model_mismatch database state variable
  $persistent_objects = array();
  foreach ($obj_name_list as $obj_name) {
    if (!class_exists($obj_name)) require_once($obj_include_fname_list[$obj_name]);
    // NOTE: the private method _check_model_mismatch()_ is called by the object constructor,
    //  if the object is an AnInstance extension_
    $ref_obj = new ReflectionClass($obj_name);
    if ($ref_obj->isSubclassOf('AnInstance')) {
      try {
        $obj = new $obj_name($dbaccess);  // force check for model mismatch
      } catch (Exception $e) {
        throw new Exception("make_database_archive(): Unable to create object '$obj_name'");
      }
      $persistent_objects[] = $obj_name;
    }
  }

  $ret = TRUE;
  try {
    // only create models if there is no detected mismatch
    if ($dbaccess->model_mismatch == 'F' || !file_exists($dump_dir . DIRECTORY_SEPARATOR . '_aclass_attribute_defs.php')) {
      if (!AClass::php_create_string($dbaccess, $persistent_objects, $dump_dir))
      throw new Exception('AClass Definitions Write Failed');
    }
    if (!Parameters::php_create_string($dbaccess, $dump_dir))
      throw new Exception("Parameters archive write failed");
    if (!Session::php_create_string($dbaccess, $dump_dir))
      throw new Exception('Session Write Failed');
    if (!AnEncryptor::php_create_string($dbaccess, $dump_dir))
      throw new Exception('Encryptor Write Failed');
    if (!AJoin::php_create_string($dbaccess, $dump_dir))
      throw new Exception('AJoin Write Failed');
    if (!AnInstance::php_create_string($dbaccess, $persistent_objects, $dump_dir))
      throw new Exception('AnInstance Data Write Failed');
    // change_site_state('archive_stale', 'F', $dbaccess);
    StateMgt::handle_event('CREATE_ARCHIVE');
  } catch (Exception $e) {
    echo "$e" . "\n";
    $ret = FALSE;
  }
  
  echo ob_get_clean() . "</div>\n";
  return $ret;
} // end of make_database_archive()

function rebuild_infrastructure($dbaccess, $dump_dir, $drop_first, $force_flag = FALSE)
{
  require_once('StateMgt.php');
  global $infrastructure_files;
  
  $str = "<div class=\"dump-output\">\n";
  if ($dbaccess->on_line != 'F') {
    $str .= "Database is not Off-Line - Aborting Infrastructure Rebuild\n</div>\n";
    return $str;
  }
  if ($dbaccess->archive_stale == 'T' && !$force_flag) {
    $str .= "Archive is Stale - Aborting Infrastructure Rebuild\n</div>\n";
    return $str;
  }
  if (($tmp = dump_dir_writable($dump_dir)) !== TRUE) {
    $str .= "Dump Directory Not OK - Aborting Infrastructure Rebuild\n\n$tmp</div>\n";
    return $str;
  }
  $cur_include_path = get_include_path();
  if (!is_array($cur_include_path) || !in_array($dump_dir, get_include_path()))
    set_include_path($dump_dir . PATH_SEPARATOR . get_include_path());
  
  // rebuild infrastructure ONLY if archive is not stale.
  // reload infrastructure files
  $str .= "Recreating Infrastructure Data\n";
  StateMgt::handle_event('START_REBUILD');
  foreach ($infrastructure_files as $infrastructure_fname) {
    $str .= " Loading $infrastructure_fname";
    $tmp = file_get_contents($dump_dir . DIRECTORY_SEPARATOR . $infrastructure_fname);
    $eval_str = preg_replace(array('/<\?php/', '/\?>/'), array('', ''), $tmp);
    $str .= eval($eval_str) !== FALSE ? " - <span style=\"color:green\">Success</span>\n"
      : " - <span style=\"color:red\">Failure</span>\n";
  }
  $str .= " Loading Account Data";
  $str .= (include('Account.dump')) ? " - <span style=\"color:green\">Success</span>\n"
    : " - <span style=\"color:red\">Failure</span>\n";
  // change_site_state('database_valid', 'F', $dbaccess);
  // change_site_state('archive_stale', 'F', $dbaccess);

  return $str . "Done\n</div>\n";
}
  
function reload_database($dbaccess, $dump_dir, $private_data_root, $drop_first, $force_flag = FALSE)
{
  require_once('StateMgt.php');
  $str = "<div class=\"dump-output\">\n";
  if ($dbaccess->on_line != 'F') {
    $str .= "Database is not Off-Line - Aborting Database Reload\n</div>\n";
    return $str;
  }

  // reload data ONLY if archive is not stale or forced
  if ($dbaccess->archive_stale != 'F' && !$force_flag) {
    $str .= "Archive is Stale - Aborting Database Reload\n</div>\n";
    return $str;
  }
  
  // check accessibility of dump directory
  if ($force_flag && (($tmp = dump_dir_readable($dump_dir_readable)) !== TRUE)) {
    $str .= "Archive Not Readable - Aborting Forced Database Reload Reload\n$tmp\n</div>\n";
    return $str;
  } elseif (($tmp = dump_dir_ok($dump_dir)) !== TRUE) {
    $str .= "Archive Not OK - Aborting Database Reload Reload\n$tmp\n</div>\n";
    return $str;
  }
  
  // add to include path
  if (!in_array($dump_dir, explode(PATH_SEPARATOR, get_include_path())))
    set_include_path($dump_dir . PATH_SEPARATOR . get_include_path());

  $basic_file_list = scandir($dump_dir);
  $object_files = array_filter($basic_file_list, create_function('$x', 'return preg_match("/\.dump$/", $x) != 0;'));

  // reload object files
  $str .= "Recreating Objects\n";
  $success_flag = TRUE;
  foreach ($object_files as $object_dump_fname) {
    $str .= " Processing $object_dump_fname - ";
    $object_name = basename($object_dump_fname, '.dump');
    if (!class_exists($object_name))
      require_once($object_name . ".php");

    if (!method_exists($object_name, 'php_create_string'))
      continue;
    
    $str .= ($success = include($object_dump_fname)) ? " - <span style=\"color:green\">Loaded</span>\n"
        : "<span style=\"color:red\">Failed</span>\n";

    if ($success === FALSE)
      $success_flag = FALSE;
  }
  if ($success_flag) {
    $str .= "<span class=\"ok\">Database Rebuild Successful</span>\n";
    StateMgt::handle_event('FINISH_REBUILD');
    // change_site_state('database_valid', 'T', $dbaccess);
    // create a new archive so that all the infrastructure files are up to date
    make_database_archive($dbaccess, $dump_dir, $private_data_root);
  } else {
    $str .= "<span class=\"error\">Database Rebuild Failed - Examine output Above</span>\n";
    // change_site_state('database_valid', 'F', $dbaccess);
  }
  
  return $str . "</div>\n";
} // end of reload_database()

// end function definitions
?>