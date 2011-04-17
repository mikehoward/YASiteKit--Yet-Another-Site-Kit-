<?php
/*
#doc-start
h1.  cookie-check.ajax - Checks if the User Agent returns Cookies

Created by  on 2010-03-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.
#doc-end
*/

function ajax_set_required_authority()
{
  Globals::$web_service->required_authority = 'ANY';
  return '200';
}

function ajax_content()
{
  if (isset($_COOKIE[Globals::$user_cookie_name])) {
    Globals::$web_service->add_content('cookie ' . Globals::$user_cookie_name . ' is set');
    return TRUE;
  } else {
    Globals::$web_service->add_error('cookie not set');
    return FALSE;
  }
}
?>
