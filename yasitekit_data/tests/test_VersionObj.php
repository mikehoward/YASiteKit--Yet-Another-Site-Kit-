<?php
/*
#doc-start
h1.  test_VersionObj.php - runs cursory tests on all VersionObj methods

Created by  on 2010-03-31.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

tests VersionObj:

* For Master versioning
** initialization
** inc()
** write()
** versioning_path()
** reloading from database
** reloading from file
* for Dev versioning
** initialization
** inc()

Methods not tested are the form generation and processing routines:

* new_version_obj_form()
* process_ver_obj_form()

#end-doc
*/

// global variables

set_include_path('..');
require_once('config.php');
Globals::$private_data_root = '..';
// Globals::$images_root = 'images' . DIRECTORY_SEPARATOR;
require_once('test_common.php');
// set_include_path(Globals::$private_data_root . PATH_SEPARATOR .
//   Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR .
//   Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'objects' . PATH_SEPARATOR .
//   get_include_path());
// date_default_timezone_set('America/Denver');
// if (!isset(Globals::$rc)) {
//   require_once('request_cleaner.php');
//   Globals::$rc = new RequestCleaner('post', 'get');
// }
require_once('VersionObj.php');
require_once('test_functions.php');

$vo = VersionObj::initialize_versioning(TRUE, 'foobar');
testTrue("$vo == 'master-foobar-1-0", $vo == 'master-foobar-1-0');
// echo $vo->dump();
$vo->write(Globals::$dbaccess, 'test vo - write');
$vo_db = VersionObj::get_from_db(Globals::$dbaccess);
testNoDBError('get_from_db() worked', Globals::$dbaccess);
testTrue("reload from database - identical value", $vo == $vo_db);

$vo->inc();
testNoDBError('inc() save to db worked', Globals::$dbaccess);
testTrue("\$vo: $vo == 'master-foobar-2-0 after inc()", "$vo" == 'master-foobar-2-0');
$vo_db2 = VersionObj::get_from_db(Globals::$dbaccess);
testTrue("\$vo_db: $vo_db != \$vo_db2: $vo_db2", "$vo_db" != "$vo_db2");
testTrue("\$vo: $vo == \$vo_db2: $vo_db2", "$vo" == "$vo_db2");

testException('$vo->write(/tmp/barf) causes exception', "global \$vo;\$vo->write('/tmp/barf');");
testNoException('vo->write(/tmp) does not cause exception', "global \$vo;\$vo->write('/tmp');");
testNoException('vo->write(/tmp/) does not cause exception', "global \$vo;\$vo->write('/tmp/');");
$vo_file = VersionObj::get_from_file(VersionObj::versioning_path('/tmp'));
testTrue("\$vo $vo == \$vo_file: $vo_file", "$vo" == "$vo_file");
unlink(VersionObj::versioning_path('/tmp'));
testFalse(VersionObj::versioning_path('/tmp') . " removed",
    file_exists(VersionObj::versioning_path('/tmp')));
testNoException('$vo_file->write() does not cause exception', 'global $vo_file;$vo_file->write();');
testTrue('VersionObj::get_from_file(/tmp/_versioning) == \$vo_file',
    $vo_file == VersionObj::get_from_file(VersionObj::versioning_path('/tmp')));
$vo_file_str_saved = "$vo_file";
$vo_file->inc();
testTrue('inc() of file based won\'t work', "$vo_file" == $vo_file_str_saved);

echo "\nChecking Dev versioning initialization and inc()\n";
$vd = VersionObj::initialize_versioning(FALSE, 'foobar');
testTrue("\$vd: $vd == 'dev-foobar-1-1'", "$vd" == 'dev-foobar-1-1');
$vd->inc();
testFalse("inc() won't work until written \$vd: $vd == 'dev-foobar-1-2'", "$vd" == 'dev-foobar-1-2');
$vd->write(Globals::$dbaccess);
$vd->inc();
testTrue("inc() will work after written \$vd: $vd == 'dev-foobar-1-2'", "$vd" == 'dev-foobar-1-2');


testReport();

?>
