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

// Global Variables
$legal_exts = 'jpg,jpeg,png,gif';
$dest_dir = NULL;

$dest_path = NULL;
$upload_fname = NULL;
$uploaded_file_path = NULL;

$result_text = array(
  'ajax_timeout' => 'Server Error: AJAX Call Timed Out',
  'dest_dir_not_exist' => 'Destination Directory does Not Exist',
  'dest_dir_not_set' => 'Destination Directory Not Defined',
  'dest_dir_not_writable' => 'Destination directory not writable',
  'dest_file_exists' => 'Destination File Exists',
  'dest_file_not_exist' => 'Destination File Does Not Exist',
  'dest_file_not_overwritable' => 'Destination File Exists and is NOT Over-Writable',
  'failure' => 'Failure',
  'file_move_finished' => 'Finished',
  'file_move_failed' => 'Failed to move uploaded file',
  'illegal_command' => 'Illegal Command',
  'illegal_file_type' => 'Illegal File Type',
  'no_dest_dir' => 'Destination path directory does not exist',
  'no_dest_fname' => 'destination file name not set',
  'no_upload_file' => 'No Upload File Specified or Sent',
  'no_upload_file_name' =>'No File Name',
  'replace' => 'Replace',
  'select_file_msg' => 'Please Select File to Upload',
  'success' => 'Success',
  'title' => 'Upload Image File',
  'upload' => 'Upload',
  'upload_error' => 'Upload Error',
  'uploading' => 'Upload in Progress',
  );

// end Global Variables

// function defintions
// required AJAX API functions
function ajax_set_required_authority()
{
  // insert any logic you need to check authority - for example, if
  //  Globals::$account_obj->userid is the owner of whatever you are accessing,
  // then you might want to restrict access to that owner - by setting
  // the owner's authority as required.
  // On the other hand, if the user has limited authority: say A,M,W,or C
  //  and the resource is owned by someone else, you can set required_authority to FORBIDDEN

  if (Globals::$account_obj instanceof Account) {
    Globals::$web_service->required_authority = 'A,M,W,S,X';
    return '200';
  } elseif (Globals::$rc->safe_post_imgupload_command == 'upload' && Globals::$web_service->data_format != 'text') {
    Globals::$web_service->required_authority = 'FORBIDDEN';
    Globals::$web_service->add_error("upload command requires 'text' data_format");
    return '400';
  } elseif (Globals::$rc->safe_post_imgupload_command == 'test' && Globals::$web_service->data_format != 'json') {
    Globals::$web_service->required_authority = 'FORBIDDEN';
    Globals::$web_service->add_error("test command requires 'json' data_format");
    return '400';
  } else {
    Globals::$web_service->required_authority = 'FORBIDDEN';
    return '403';
  }
  // Globals::$web_service->required_authority = 'FORBIDDEN';
  // Globals::$web_service->required_authority = 'X';
  // Globals::$web_service->required_authority = 'S';
  // Globals::$web_service->required_authority = 'A';
  // Globals::$web_service->required_authority = 'M';
  // Globals::$web_service->required_authority = 'W';
  // Globals::$web_service->required_authority = 'C';
  // Globals::$web_service->required_authority = 'C,A,M,W,S,X';
} // end of ajax_set_required_authority()

function ajax_content()
{
  global $legal_exts;
  global $dest_dir;
  global $upload_fname;
  global $dest_path;
  global $result_text;
  global $uploaded_file_path;
  
  // here we assume we have authority

  // instantiate dest_dir
  $dest_dir = Globals::$user_upload_root . DIRECTORY_SEPARATOR . Globals::$account_obj->userid;

  // everything is good, so return something good
  switch (Globals::$web_service->data_format) {
    case 'json':
    case 'xml':
    case 'html':
      break;
    case 'script':
    case 'text':
    default:
      Globals::$web_service->add_error($result_text['illegal_command'] . ": " . Globals::$web_service->data . "'");
      return FALSE;
      return '';
  }
  
  switch (Globals::$rc->safe_post_imgupload_command) {
    case 'upload':
      setup_upload_fname();
      construct_dest_info($upload_fname);

      if (move_uploaded_file($uploaded_file_path, $dest_path)) {
        Globals::$web_service->add_content("uploaded " . basename($upload_fname) . "to $dest_path");
        return TRUE;
      } else {
        Globals::$web_service->add_error('file_move_failed' . ": $dest_path");
        return FALSE;
      }
      break;
    case 'test':
      setup_test_upland_fname();
      construct_dest_info($upload_fname);
      // if we get here, then uploading is possible, so we only need to know if the file exists or not
      if (file_exists($dest_path)) {
        Globals::$web_service->add_content(array('result' => 'success', 'result_code' => 'dest_file_exists'));
      } else {
        Globals::$web_service->add_content(array('result' => 'success', 'result_code' => 'dest_file_not_exist'));
      }
      return TRUE;
      break;
    default:
      Globals::$web_service->add_error($result_text['illegal_command'] . ": '$command'");
      return FALSE;
      break;
  }
} // end of ajax_content()

// end AJAX API required functions

// upload specific functions - mostly clipped from the imgupload plugin sample server code

// common checks
function construct_dest_info($upload_fname)
{
  global $dest_fname;
  global $dest_dir;
  global $dest_path;
  global $legal_exts;
  global $dest_dir;
  global $result_text;

  // move to basename of upload file path
  $upload_fname = basename($upload_fname);

  // check file type by extents and set default_ext to same extent as upload file
  if (Globals::$upload_file_extentions) {
    $legal_exts = '/\.(' . implode('|', explode(',', Globals::$upload_file_extentions)) . ')$/i';
    if (!preg_match($legal_exts, $upload_fname, $match_obj)) {
      Globals::$web_service->add_error($result_text['illegal_file_type'] . " $upload_fname [ $legal_exts ]");
      Globals::$web_service->add_content('illegal_file_type');
      return;
    }
    $default_ext = strtolower('.' . $match_obj[1]);
  }

  // destination file name
  if (!($dest_fname = Globals::$rc->safe_post_imgupload_dest_fname)) {
    Globals::$web_service->add_content('no_dest_fname');
    return;
  }
  
  if (!is_dir($dest_dir)) {
    Globals::$web_service->add_error($result_text['dest_dir_not_exist'] . ": $dest_dir");
    Globals::$web_service->add_content('dest_dir_not_exist');
    return;
  }
  if (!is_writable($dest_dir)) {
    Globals::$web_service->add_error($result_text['dest_dir_not_writable'] . ": $dest_dir");
    Globals::$web_service->add_content('dest_dir_not_writable');
    return;
  }
  
  // append default extension so that file types match
  if (isset($default_ext) && strtolower(substr($dest_fname, strlen($dest_fname) - strlen($default_ext))) != $default_ext) {
    $dest_fname .= $default_ext;
  }

  // check to see that if target file exists, it can be overwritten
  $dest_path = $dest_dir . DIRECTORY_SEPARATOR . $dest_fname;
  if (is_file($dest_path) && !is_writable($dest_path)) {
    Globals::$web_service->add_error($result_text['dest_file_not_overwritable'] . ": $dest_path");
    Globals::$web_service->add_content('dest_file_not_overwritable');
  }
} // end of construct_dest_info()

function setup_upload_fname()
{
  global $upload_fname;
  global $uploaded_file_path;
  global $result_text;
  
  if (!Globals::$rc->safe_files_imgupload_upload_file) {
  	Globals::$web_service->add_content('no_upload_file');
  }
  $file_ar = Globals::$rc->safe_files_imgupload_upload_file;
  if (!$file_ar || !isset($file_ar['name'])) {
    Globals::$web_service->add_content('no_upload_file_name');
    exit(0);
  }
  if (intval($file_ar['error']) != 0) {
    Globals::$web_service->add_error($result_text['upload_error'] .  " Upload Error Code: {$file_ar['error']}");
    Globals::$web_service->add_content('upload_error');
  }

  $upload_fname = htmlentities($file_ar['name']);
  $uploaded_file_path = htmlentities($file_ar['tmp_name']);
} // end of setup_upload_fname()

function setup_test_upland_fname()
{
  global $upload_fname;
  if (!($upload_fname = Globals::$rc->safe_post_imgupload_upload_file)) {
    Globals::$web_service->failure('no_upload_file_name');
  }
} // end of setup_test_upland_fname()

// end Function Definitions
?>