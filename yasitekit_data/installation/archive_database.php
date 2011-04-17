<?php

array_shift($argv);

// get config file
$cwd = getcwd();
chdir('..');
set_include_path('.');
require_once('config.php');
require_once('session.php');
require_once('aclass.php');
chdir($cwd);
set_include_path('..' . PATH_SEPARATOR . implode(PATH_SEPARATOR, $argv) . PATH_SEPARATOR . get_include_path());
// echo Globals::dump();

require_once('dbaccess.php');
require_once('archive_functions.php');
require_once('StateMgt.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);

// change_site_state('on_line', 'F');
StateMgt::handle_event('GO_OFFLINE');
make_database_archive(Globals::$dbaccess, Globals::$dump_dir, Globals::$private_data_root);
// change_site_state('on_line', 'T');
StateMgt::handle_event('GO_ONLINE');
?>
