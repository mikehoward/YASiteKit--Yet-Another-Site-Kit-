<?php
/*
#doc-start
h1.  upload-file.php - file upload utility used with the tinyMCE imgupload plugin

*WARNING: This code is out of date and will not work properly*

Created by  on 2010-05-09.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Expects the following POST variables to be set:

* file - string - set in an _input_ element of type _file_
* dest_path - string - one of two values: 'public' or 'private'

#end-doc
*/


// Begin Global Variables

// end Global Variabls

// begin function defintions
// finish(result) echoes the JSON encoding of the return and then exits

function finish($result, $explanation = 'none')
{
  global $file_name;
  global $func_name;
  $ar = array('result' => $result,
    'explanation' => $explanation,
    'file_name' => $file_name,
    'func_name' => $func_name,
    );
  // if (class_exists('Globals') && Globals::$site_installation == 'development') {
  //   file_put_contents('/tmp/product_manage_paypal_buttons.diag', Globals::dump(basename(__FILE__).":".__LINE__));
  // }
  
  echo json_encode($ar);
} // end of finish()
// end function defintions

// send headers
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// dispatch actions
 if (!(Globals::$account_obj instanceof Account)) {
   finish("no-authority", "account_obj not Account");
   exit(0);
 }

if (!Globals::$account_obj->logged_in()) {
  Globals::$session_obj->add_message("You must be logged into upload a file");
  finish('no-authority', 'login-required', NULL);
  exit(0);
}

foreach (array(
  'safe_files_file' => "files variable 'file'",
  'safe_post_dest_path' => "dest_path variable not set OR not 'public' or 'private'",
  'safe_post_dest_fname' => "dest_fname not set",
  ) as $key => $errmsg) {
  if (!isset(Globals::$rc->$key) || !Globals::$rc->$key) {
    finish('bad-upload', $errmsg);
    exit(0);
  }
}

$file_ar = Globals::$rc->safe_file_file;
if (!$file_ar || !isset($file_ar['name'])) {
  finish('bad-upload', "No File Name Set");
  exit(0);
}
if (intval($file_ar['error']) != 0) {
  finish('bad-upload', "Upload Error Code: {$file_ar['error']}");
}

$upload_name = $file_ar['name'];
$uploaded_file_path = $file_ar['tmp_name'];

$dest_path = Globals::$rc->safe_post_dest_path;
switch ($dest_path) {
  case 'public': $dest_path = Globals::$document_root . DIRECTORY_SEPARATOR . $dest_path; break;
  case 'private': $dest_path = Globals::$private_data_root . DIRECTORY_SEPARATOR . $dest_path; break;
  default: break;
}

if (!is_dir(dirname($dest_path))) {
  finish('failure', "Destination path directory does not exist: " . dirname($dest_path));
  exit(0);
}
if (!is_writable(dirname($dest_path))) {
  finish('failure', "Destination path directory not writable: " . dirname($dest_path));
  exit(0);
}
if (file_exists($dest_path) && !is_writable($dest_path)) {
  finish('failure', "Destination path file exists and is NOT writable: $dest_path");
  exit(0);
}
if (move_uploaded_file($uploaded_file_path, $dest_path)) {
  finish('success');
} else {
  finish('failure', "Failed to move uploaded file to $dest_path");
}
?>
