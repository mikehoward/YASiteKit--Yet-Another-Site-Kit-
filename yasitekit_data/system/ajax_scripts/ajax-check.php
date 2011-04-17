<?php
/*
#doc-start
h1.  ajax-check.ajax - Does Ajax Work?

Created by  on 2010-03-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

A simple test to exercise the AJAX interface.
#end-doc
*/
function ajax_set_required_authority()
{
  Globals::$web_service->required_authority = 'ANY';
  return '200';
} // end of ajax_set_required_authority()

function ajax_content()
{
  // $ar = array('result' => Globals::$rc->dump('rc dump'));
  Globals::$web_service->add_error("Test Error Message 1");
  Globals::$web_service->add_error("Test Error Message 2");
  switch (Globals::$web_service->data_format) {
    case 'json':
    case 'jsonp':
    case 'xml':
      // Globals::$web_service->add_content('bad content');
      Globals::$web_service->add_content(array('content' => Globals::$rc->dump()));
      break;
    case 'html':
    case 'script':
    case 'text':
      // Globals::$web_service->add_content(array('foo' => 'bad content'));
      Globals::$web_service->add_content(Globals::$rc->dump());
      break;
  }
  return TRUE;
} // end of ajax_perform()

return TRUE;
?>
