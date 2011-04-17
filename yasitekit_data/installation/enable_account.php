<?php

if (count($argv) != 4) {
  echo basename($argv[0]) . " userid password authority\n";
  return;
}

$progname = array_shift($argv);
$userid = array_shift($argv);
$password = array_shift($argv);
$authority = array_shift($argv);

set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require_once('includes.php');
require_once('Account.php');
$acnt = new Account(Globals::$dbaccess, array('userid' => $userid));
$acnt->set_password($password);
$acnt->authority = $authority;
$acnt->state = 'A';
$acnt->failed_login_attempts = 0;
$acnt->save();
echo $acnt->dump();
?>