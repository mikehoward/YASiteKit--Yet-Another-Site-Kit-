<?php
/*
#doc-start
h1. tinymce-available-images.php - returns variable assignment for tinyMCEImageList

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 

bq. Licensed under terms of LGPL Version 3.
#doc-end
*/

function ajax_set_required_authority()
{
  Globals::$web_service->required_authority = 'ANY';
  // Globals::$web_service->required_authority = 'W,M,A,S,X';
  return '200';
} // end of ajax_set_required_authority()

function ajax_content()
{
  // legal extentions: jpg,jpeg,png,gif
  $legal_ext_regx = '/\.(jpg|jpeg|png|gif)$/i';
  // legal extentions: jpg,jpeg,png,gif,svg
  // $legal_ext_regx = '/\.(jpg|jpeg|png|gif|svg)$/i';
  // legal extentions: pdf
  // $legal_ext_regx = '/\.pdf$/i';
  // legal extentions: pdf,txt
  // $legal_ext_regx = '/\.(pdf|txt)$/i';

  // here we assume we have authority
  $image_dir_url = '/images/' . Globals::$account_obj->userid;
  $image_dir = Globals::$document_root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . Globals::$account_obj->userid;

  // check for bad things and failure if they happened
  if (!is_dir($image_dir)) {
    mkdir($image_dir);
    
    // check existence - after we tried to make it
    if (!is_dir($image_dir)) {
      Globals::$web_service->add_error('Unable to create image file directory for user ' . Globals::$account_obj->userid);
      return FALSE;
    }
  }

  // check writability
  if (!is_writable($image_dir)) {
    Globals::$web_service->add_error("image file directory [$image_dir] for user " . Globals::$account_obj->userid
      . ' is not writable');
    return FALSE;
  }

  // test to see if image_dir is searchable - see PHP doc for is_executable()
  if (!file_exists($image_dir . DIRECTORY_SEPARATOR . '.')) {
    Globals::$web_service->add_error('image file directory for user ' . Globals::$account_obj->userid
      . ' is not writable');
    return FALSE;
  }
  
  if (Globals::$web_service->data_format != 'text') {
    Globals::$web_service->add_error('Bad data format - must be "text"');
    return FALSE;
  }

  // get existing interesting files
  $ar = array();
  foreach (scandir($image_dir) as $fname) {
    if (preg_match($legal_ext_regx, $fname)) {
      $ar[] = "['$fname', '$image_dir_url/$fname']";
    }
  }

  Globals::$web_service->add_content("var tinyMCEImageList = new Array(\n" . implode(",\n", $ar) . ");\n");
  return TRUE;
} // end of ajax_content()
?>