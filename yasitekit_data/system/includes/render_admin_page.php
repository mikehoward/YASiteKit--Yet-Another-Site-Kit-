<?php
/*
#doc-start
h1.  render_admin_page - HTML Page dispatcher and renderer

Created by  on 2010-06-09.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module renders administrative pages when the site is offline. It refuses
to run except when the database flag _on_line_ is 'F' and refuses to do
anything for anyone who cannot verify that they have admin privileges.

It only displays the following pages:

* Login.tpl - to allow admin logins
* ReloadDB.tpl - to allow the database to be reconstructed
* AdminTools.tpl - which is a front end for low level administrative tools.
* page_not_found_page.tpl - error page
* page_access_denied.tpl - error page

Most of this module simply implements the following logic diagram:

!/doc.d/img/render_admin_page_Logic.jpg!

#end-doc
*/

require_once('YATheme.php');
require_once('Account.php');

// function definitions

function fail($status_code, $reason_phrase, $content_type = "text/plain", $content = '')
{
  $reason_phrase = preg_replace('/\s+/', ' ', $reason_phrase);
  header("HTTP/1.1 $status_code $reason_phrase");
  header("Content-Type: $content_type");
  header("Content-Length: " . strlen($content));
  echo $content;
  exit(1);
} // end of fail()

function render_page($page_name, $line_no)
{
  // echo "render_page($page_name, $line_no) - prev Globals::\$page_name: " . Globals::$page_name . "\n";
  if (Globals::$page_name != $page_name) {
    Globals::$session_obj->reserved_page_name = Globals::$page_name;
    Globals::$page_name = $page_name;
  }
  $yatheme_obj = new YATheme(Globals::$dbaccess, Globals::$page_name);
  $yatheme_obj->caching = 'off';
  require_once('yastream.php');
  file_put_contents('var://tmp', $yatheme_obj->render());
  if (Globals::$site_installation == 'development') {
    file_put_contents("/tmp/{$yatheme_obj->file_name}", $yatheme_obj->render());
  }
  include('var://tmp');
} // end of render_page()

// end function definitions

// ************************** Manage account login here so we can redirect as necessary, retry, etc

if (Globals::$dbaccess->on_line != 'F') {
  fail('403', 'Forbidden - Site Offline');
}

if (Globals::$flag_is_robot) {
  fail('403', 'Forbidden');
}

if (!Globals::$flag_cookies_ok) {
  IncludeUtilities::handle_no_cookies( basename(__FILE__) . ":" . __LINE__);
  return;
}

if (!Globals::$flag_session_ok) {
  render_page('Login.tpl',  basename(__FILE__) . ":" . __LINE__);
  return;
}

if (!Globals::$flag_account_ok) {
  render_page('Login.tpl',  basename(__FILE__) . ":" . __LINE__);
  return;
}

if (!Globals::$account_obj->logged_in()) {
  render_page('Login.tpl',  basename(__FILE__) . ":" . __LINE__);
}

if (Globals::$account_obj->authority != 'X') {
  Globals::$account_obj->logout();
  Globals::$session_obj->viciously_destroy_session();
  IncludeUtilities::redirect_to('/Login.tpl',  basename(__FILE__) . ":" . __LINE__);
}

// check for allowable pages
switch (Globals::$page_name) {
  case 'Login.tpl':
  case 'ReloadDB.tpl':
  case 'AdminTools.tpl':
  case 'page_not_found_page.tpl';
    break;
  case 'index.php':
  case '/index.php';
  case '/':
  case '':
    Globals::$page_name = 'AdminTools.tpl';
    break;
  default:
    Globals::$rc->safe_get_not_found_page = Globals::$page_name;
    Globals::$page_name = 'page_not_found_page.tpl';
    break;
}

render_page(Globals::$page_name, __LINE__);
?>
