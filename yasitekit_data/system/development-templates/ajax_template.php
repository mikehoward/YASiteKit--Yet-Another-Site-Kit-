<?php
/*
#doc-start
h1. ajax_template.php - a Starter file for YASiteKit AJAX scripts

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 

This is a template for creating AJAX code to run using the YASiteKit protocol.

Instructions:

# read about "render_ajax.php":/doc.d/system-includes/render_ajax.html
# copy this script to something like "my_ajax.php" in the _private_data / ajax_scripts_ directory.
# hack away

#doc-end
*/

function ajax_set_required_authority()
{
  // insert any logic you need to check authority - for example, if
  //  Globals::$account_obj->userid is the owner of whatever you are accessing,
  // then you might want to restrict access to that owner - by setting
  // the owner's authority as required.
  // On the other hand, if the user has limited authority: say A,M,W,or C
  //  and the resource is owned by someone else, you can set required_authority to FORBIDDEN

  // Globals::$web_service->required_authority = 'FORBIDDEN';
  // return 403;

  Globals::$web_service->required_authority = 'ANY';
  // Globals::$web_service->required_authority = 'X';
  // Globals::$web_service->required_authority = 'S';
  // Globals::$web_service->required_authority = 'A';
  // Globals::$web_service->required_authority = 'M';
  // Globals::$web_service->required_authority = 'W';
  // Globals::$web_service->required_authority = 'C';
  // Globals::$web_service->required_authority = 'C,A,M,W,S,X';
  return '200';
} // end of ajax_set_required_authority()

function ajax_content()
{
  // here we assume we have authority

  // check for bad things and call failure() if they've happened
  $something_bad = FALSE;
  if ($something_bad) {
    Globals::$web_service->add_error('something bad happened');
    return FALSE;
  }

  // everything is good, so return something good
  switch (Globals::$web_service->data_format) {
    case 'json':
    case 'jsonp':
    case 'xml':
      Globals::$web_service->add_content(array('name' => 'ajax_template.php', 'data' => 'Sample Data'));
      // Construct an array containing your return data
      break;
    case 'html':
      // construct an HTML string
      Globals::$web_service->add_content("<h1>ajax_tmplate.php</h1><p>Sample Data</p>");
      break;
    case 'script':
      // construct a Javascript script as a string
      Globals::$web_service->add_content("alert('ajax_template.php - Sample Data);");
      break;
    case 'text':
      // construct raw text w/o formatting. It will be displayed using var_dump()
      Globals::$web_service->add_content("ajax_template.php\n\nSample Data\n");
      break;
    default:
      Globals::$web_service->add_error("Illegal AJAX data format requrested: '" . Globals::$web_service->data_format . "'");
      return FALSE;
  }
  
  return TRUE;
} // end of ajax_content()
?>
