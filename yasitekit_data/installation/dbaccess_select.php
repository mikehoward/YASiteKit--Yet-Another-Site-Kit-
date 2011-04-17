<?php

$prog_name = basename(array_shift($argv));
$help = "Usage: $prog_name table <tablename> where 'field=value,field=>value,...' fields 'field,field,...' [verbose/-v/--verbose] [unverbose-u/--unverbose] [dump/print_r/table]. . .\n";

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
echo "  Table Selection Utility\n";

$chg_flag = FALSE;
$output_format = 'table';
while ($argv) {
  $cmd = array_shift($argv);
  switch ($cmd) {
    case 'help': case '-h': case '--help': case '-?': echo $help; exit;
    case 'verbose':case '-v':case '--verbose': $dbaccess->verbose(TRUE); break;
    case 'unverbose':case '-u':case '--unverbose': $dbaccess->verbose(FALSE); break;
    case 'table':
      if (isset($table) && $table) {
        $tmp_ar = $dbaccess->select_from_table($tablename, $fields, $where);
        $fields = NULL;
        $where = NULL;
        do_output($tmp_ar);
        echo "\n";
      }
      $tablename = array_shift($argv);
      break;
    case 'fields': $fields = preg_split('/\s*,\s*/', array_shift($argv)); break;
    case 'where':
      $where_ar = preg_split('/\s*,\s*/', array_shift($argv));
      $where = array();
      foreach ($where_ar as $tmp) {
        list($key, $val) = preg_split('/\s*=>?\s*/', $tmp);
        $where[$key] = $val;
      }
      break;
    case 'dump': case 'print_r': case 'table':
      $output_format = $cmd;
      break;
    default: echo $help; exit;
  }
}

function do_output($tmp_ar) {
  global $output_format;
  
  switch ($output_format) {
    case 'dump':
      var_dump($tmp_ar);
      break;
    case 'print_r':
      print_r($tmp_ar);
      break;
    case 'table':
      $keys = array_keys($tmp_ar[0]);
      echo implode('|', $keys) . "|\n";
      foreach ($tmp_ar as $row) {
        foreach ($keys as $key) {
          echo $row[$key] . '|';
        }
        echo "\n";
      }
      break;
  }
}

if ($tablename) {
  $tmp_ar = $dbaccess->select_from_table($tablename, $fields, $where);
  do_output($tmp_ar);
}
