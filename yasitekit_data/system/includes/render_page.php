<?php
/*
#doc-start
h1.  render_page - HTML Page dispatcher and renderer

Created by  on 2010-03-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*render_page.php* render viewable pages when the site is in Online or Read-Only
mode. It handles all PHP, in those modes, which render HTML.

It also the implements access control mechanism. Access control is
both session and account based. Each page controls its own level of access
by setting the page object attribute _required_access_ to NULL or
one or more account authority characters. [multiple authorities may
be assigned simultaneously as a comma separated list or an array of
authority characters. See "Account.php":/doc.d/system-objects/Account.html,
"Page.php":/doc.d/system-objects/Page.html and
"YASiteKit Overview":/doc.d/OverView.html#creating_a_page for more details]

#end-doc
*/

IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");

switch (Globals::$dbaccess->on_line) {
  case 'T': break;
  case 'R': Globals::add_message("WARNING: this site is in Read Only Mode - it may be viewed, but not modified. Some data may be inaccurate."); break;
  case 'F': Globals::add_message("WARNING: this site is in Off Line Mode"); break;
  default:
    Globals::add_message("WARNING: this site is in an Illegal Mode: '"
      . Globals::$dbaccess->on_line . "' - going Off Line");
    break;
}

// check to see if page is displayable
// This is a hack to stablize the page environment for management pages
// it should probably be some sort of switch statement or an array map
// but those are more difficult to maintain.
if (preg_match('/^Manage.*.php$/', Globals::$page_name)) {
  require_once('PageYASiteKit.php');
  Globals::$page_obj = new PageYASiteKit(Globals::$page_name);
} else {
  require_once('Page.php');
  Globals::$page_obj = new Page(Globals::$page_name);
}

IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");

// if page is not restricted, then display it. Set cookies and session if this is
//   NOT a known robot
if (Globals::$page_obj->displayableP()) {
  if (!Globals::$flag_is_robot) {
    if (!Globals::$flag_cookies_ok) {
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");
      IncludeUtilities::handle_no_cookies( basename(__FILE__) . ":" . __LINE__ );
      return;
    }
    if (!Globals::$flag_session_ok) {
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");
      Globals::$session_obj = Session::get_session();
      Globals::$flag_session_ok = TRUE;
    }
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");
    IncludeUtilities::set_all_cookies();
  }
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");
  echo Globals::$page_obj->render();
  return;
}

if (!Globals::$flag_cookies_ok) {
  // call handle_no_cookies() to vector off to cookie request land
  IncludeUtilities::handle_no_cookies(basename(__FILE__).':'.__LINE__);
  return;
} elseif (!(Globals::$account_obj instanceof Account)) {
  IncludeUtilities::redirect_to('/page_access_denied.php', basename(__FILE__) . ':' . __LINE__);
  return;
}

IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");

// echo Globals::$page_obj->dump('render_page.php:' . __LINE__);

// if page is not displayble with this account
// IMPORTANT: displayableP() calls render on the page object, which runs all the code in
//  the segments. This prepares the page for template substitution.
if (!Globals::$page_obj->displayableP(Globals::$account_obj)) {
  Globals::$session_obj->add_message('Insufficient Authority ' . basename(__FILE__) . ":" . __LINE__);
  IncludeUtilities::redirect_to('/page_access_denied.php', basename(__FILE__) . ':' . __LINE__);
}

if (!Globals::$account_obj->logged_in()) {
  Globals::$session_obj->add_message("Login Required to Access Page '" . Globals::$page_name);
  IncludeUtilities::redirect_to_with_return('Login.php', basename(__FILE__) . ':' . __LINE__);
}

if (Globals::$site_installation != 'development') {
  Globals::$flag_exceptions_on = FALSE;
}

echo Globals::$page_obj->render();

?>