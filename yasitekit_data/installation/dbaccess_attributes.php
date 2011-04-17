<?php

$prog_name = basename(array_shift($argv));
$help = "Usage: $prog_name [unset attr] [set attr value] [verbose/-v/--verbose] [unverbose-u/--unverbose] . . .\n";

// get config file
$cwd = getcwd();
chdir('..');
set_include_path('.');
require_once('config.php');

chdir($cwd);
set_include_path('..' . PATH_SEPARATOR . implode(PATH_SEPARATOR, $argv) . PATH_SEPARATOR . get_include_path());

require_once('dbaccess.php');
Globals::$dbaccess = new DBAccess(Globals::$db_params);
$dbaccess = Globals::$dbaccess;

echo "Database: $dbaccess\n\n";
echo "  Attributes Before Processing Change Commands\n";
foreach (Globals::$dbaccess->attribute_names() as $attr) {
  echo "    $attr: '{$dbaccess->$attr}'\n";
}

$chg_flag = FALSE;
while ($argv) {
  $cmd = array_shift($argv);
  switch ($cmd) {
    case 'help': case '-h': case '--help': case '-?': echo $help; exit;
    case 'verbose':case '-v':case '--verbose': $dbaccess->verbose(TRUE); break;
    case 'unverbose':case '-u':case '--unverbose': $dbaccess->verbose(FALSE); break;
    case 'unset': $chg_flag = TRUE; $attr = array_shift($argv); unset($dbaccess->$attr); break;
    case 'set': $chg_flag = TRUE; $attr = array_shift($argv); $val = array_shift($argv); $dbaccess->$attr = $val ; break;
    default: echo $help; exit;
  }
}

if ($chg_flag) {
  echo "\n  Attributes After Processing\n";
  foreach (Globals::$dbaccess->attribute_names() as $attr) {
    echo "    $attr: '{$dbaccess->$attr}'\n";
  }
}
