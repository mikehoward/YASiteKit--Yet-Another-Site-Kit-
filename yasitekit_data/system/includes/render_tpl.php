<?php
/*
#doc-start
h1.  render_yatheme - HTML Page dispatcher and renderer

Created by  on 2010-03-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*render_yatheme.php* render viewable yatheme conten when the site is in Online or Read-Only
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

IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");

// create YATheme object
require_once('YATheme.php');
$yatheme_obj = new YATheme(Globals::$dbaccess, Globals::$page_name);

switch (Globals::$site_installation) {
  case 'development':
    $yatheme_obj->caching = 'off';
    break;
  case 'production':
    $yatheme_obj->caching = 'compress';
    break;
  case 'alpha':
    // Intentional Fall Through
  default:
    $yatheme_obj->caching = 'on';
    break;
}

// manage cookie handling for humans and don't require for robots
if ($yatheme_obj->access_flag == YATheme::PUBLIC_OK) {
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

  require_once('yastream.php');
  file_put_contents('var://tmp', $yatheme_obj->render());
  if (Globals::$site_installation == 'development') {
    file_put_contents("/tmp/$yatheme_obj->file_name", $yatheme_obj->render());
  }
  include('var://tmp');
  // echo $yatheme_obj->render();
  // echo "<pre>\n" . $yatheme_obj->dump(__FILE__) . "\n</pre>\n";
  return;
}

// from here on, we need an account - hence we must have cookies
if (!Globals::$flag_cookies_ok) {
  // call handle_no_cookies() to vector off to cookie request land
  IncludeUtilities::handle_no_cookies(basename(__FILE__).':'.__LINE__);
  return;
}

IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . "\n");

// echo Globals::$page_obj->dump('render_page.php:' . __LINE__);

if (Globals::$site_installation != 'development') {
  Globals::$flag_exceptions_on = FALSE;
}

switch ($yatheme_obj->access_flag) {
  case YATheme::LOGIN_REQUIRED:
  IncludeUtilities::$enable_tracking = TRUE;
    IncludeUtilities::redirect_to_with_return('/Login.php',  basename(__FILE__) . ':' . __LINE__);
    return;
  case YATheme::NOT_AUTHORIZED:
    Globals::$session_obj->add_message('Insufficient Authority ' . basename(__FILE__) . ":" . __LINE__);
    IncludeUtilities::redirect_to('/page_access_denied.php', basename(__FILE__) . ':' . __LINE__);
    return;
  case YATheme::AUTHORITY_OK:
file_put_contents('/tmp/dump-of-' . $yatheme_obj->file_name, $yatheme_obj->dump(__FILE__ . ': ' . __LINE__));

    switch ($yatheme_obj->file_exists) {
      case 'Y':
        require_once('yastream.php');
        file_put_contents('var://tmp', $yatheme_obj->render());
        include('var://tmp');
        if (Globals::$site_installation == 'development') {
          file_put_contents("/tmp/$yatheme_obj->file_name", $yatheme_obj->render());
        }
        break;
      case 'N':
        IncludeUtilities::redirect_to('/page_not_found_page.tpl', basename(__FILE__) . ':' . __LINE__);
        break;
      default:
        throw new Exception("Illegal file object:" . $yatheme_obj->dump('render_tpl.php: ' . __LINE__));
    }
    break;
  default:
    Globals::$session_obj->add_message("Internal Error: access_flag: {$yatheme_obj->access_flag}");
    IncludeUtilities::redirect_to('/page_not_found_page.tpl', basename(__FILE__) . ':' . __LINE__);
}
