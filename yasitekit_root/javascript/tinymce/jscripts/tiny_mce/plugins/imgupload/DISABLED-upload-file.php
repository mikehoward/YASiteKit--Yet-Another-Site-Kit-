<?php
/*
#doc-start
h1.  upload-file

Created by  on 2010-05-09.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Licensed under the Lesser GNU Public License, version 3. See LGPL-3.txt for details.

p{font-weight:bold}. WARNING: USING THIS CODE IS INHERENTLY DANGEROUS BECAUSE IT ALLOWS USERS TO UPLOAD
FILES TO YOUR SERVER. IT HAS NO MEANINGFUL SECURITY.

p{font-weight:bold}. THIS SOFTWARE IS MADE AVAILABLE WITHOUT WARRANTY. USE AT YOUR OWN RISK.

p{font-weight:bold}. The following warranty disclaimer and limitation of liability are 'borrowed'
from the copying provisions from GNU Emacs and EXPLICITLY apply
to this code:

bq{font-weight:bold}. Disclaimer of Warranty.

bq{font-weight:bold}. THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY
APPLICABLE LAW.  EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT
HOLDERS AND/OR OTHER PARTIES PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY
OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO,
THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
PURPOSE.  THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM
IS WITH YOU.  SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF
ALL NECESSARY SERVICING, REPAIR OR CORRECTION.

bq{font-weight:bold}. Limitation of Liability.

bq{font-weight:bold}. IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MODIFIES AND/OR CONVEYS
THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES, INCLUDING ANY
GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE
USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED TO LOSS OF
DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD
PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER PROGRAMS),
EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF
SUCH DAMAGES.

This program is shipped disabled. Enable at your own risk. The author assumes
no liability or responsibility for it's use.

Expects the following POST variables to be set:

* imgupload_command - string - either 'test' or 'upload'. This determines
the mode the program operates in.
* imgupload_upload_file - string - set in an _input_ element of type _file_
* imgupload_dest_fname - string - file name for uploaded file _on the server_.

#end-doc
*/


// Begin Global Variables
// Configuration Start
// This program is shipped disabled. Please read warnings in Spec.html
// before deciding to enable it.

// uploadable file extent values
$legal_exts = 'jpg,jpeg,png,gif';
// $legal_exts = 'jpg,jpeg,png,gif,svg';
// $legal_exts = 'pdf';
// $legal_exts = NULL;   // anything can be uploaded

// destination directory - can be anywhere on the file system of the host which is writeble by
// the HTTP server. Does not need to be beneight DocumentRoot
// destination directory
$dest_dir = '/tmp/images';  // generally this should be an absolute path
// $dest_dir = '/path/to/destination/directory';

// Configuration End

$output_format = NULL;
$command = NULL;
$dest_fname = NULL;
$dest_path = NULL;

$upload_fname = NULL;
$uploaded_file_path = NULL;

// need to fix this to make it language targetable
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

// begin function defintions
// finish(result) echoes the JSON encoding of the return and then exits

function finish($result, $result_code = 'none', $explanation = '')
{
  global $upload_fname;
  global $dest_fname;
  global $dest_dir;
  global $output_format;
  
  $ar = array('result' => $result,
    'result_code' => $result_code,
    'explanation' => $explanation,
    'upload_file' => $upload_fname,
    'dest_fname' => $dest_fname,
    'get_data' => $_GET,
    'post_data' => $_POST,
    'files_data' => $_FILES,
    );

  // some debug code
  // ob_start();
  // var_dump($ar);
  // $s = ob_get_clean();
  // file_put_contents('/tmp/upload-file.out', json_encode($ar) . "\n" . $s);
  switch ($output_format) {
    case 'json':
      header("Content-Type: application/json");
      echo json_encode($ar);
      break;
    case 'xml':
      header("Content-Type: application/xml");
      $str = "<?xml version=\"1.0\"?>\n<upload>";
      foreach ($ar as $key => $val) $str .= "  <$key>$val</$key>\n";
      echo $str . "</upload>\n";
      break;
    case 'html':
      global $result_text;
      header("Content-Type: text/html");
      switch ($result) {
        case 'success':
          echo "<span class=\"imgupload_ok\">Success: $upload_fname to $dest_fname</span>\n";
          break;
        case 'failure':
          echo "<span class=\"imgupload_error\">Failure: $upload_fname Not Copied To $dest_dir: {$result_text[$result_code]}"
            . ($explanation ? "($explanation)" : '') . "</span>\n";
          break;
        default:
          echo "<span class=\"imgupload_error\">Illegal result value: '$result'</span>\n";
          break;
      }
      break;
    default: var_dump($ar);
      break;
    
  }

	exit(0);
} // end of finish()
// end function defintions

// some debugging code
// ob_start();
// var_dump($_POST);
// 
// file_put_contents('/tmp/upload-file.out', "upload-file.php starting\n" . ob_get_clean());

// send headers
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// deterimine command mode
$command = htmlentities($_POST['imgupload_command']);
switch ($command) {
  case 'upload':
    $output_format = 'html';
    break;
  case 'test':
    $output_format = 'json';
    // $output_format = 'xml';
    break;
  default:
    $output_format = 'html';
    finish('failure', 'illegal_command', $command);
    break;
}

// common checks
function construct_dest_info($upload_fname)
{
  global $dest_fname;
  global $dest_dir;
  global $dest_path;
  global $legal_exts;
  global $dest_dir;

  // move to basename of upload file path
  $upload_fname = basename($upload_fname);

  // check file type by extents and set default_ext to same extent as upload file
  if ($legal_exts) {
    $legal_exts = '/\.(' . implode('|', explode(',', $legal_exts)) . ')$/i';
    if (!preg_match($legal_exts, $upload_fname, $match_obj)) {
      finish('failure', 'illegal_file_type', $upload_fname . "[ $legal_exts ]");
    }
    $default_ext = strtolower('.' . $match_obj[1]);
  }

  // destination file name
  if (!isset($_POST['imgupload_dest_fname']) || !$_POST['imgupload_dest_fname']) {
    finish('failure', 'no_dest_fname');
  } else {
    $dest_fname = htmlentities($_POST['imgupload_dest_fname']);
  }
  
  if (!is_dir($dest_dir)) {
    finish('failure', 'dest_dir_not_exist', $dest_dir);
  }
  if (!is_writable($dest_dir)) {
    finish('failure', 'dest_dir_not_writable', $dest_dir);
  }
  
  // append default extension so that file types match
  if (isset($default_ext) && strtolower(substr($dest_fname, strlen($dest_fname) - strlen($default_ext))) != $default_ext) {
    $dest_fname .= $default_ext;
  }

  // check to see that if target file exists, it can be overwritten
  $dest_path = $dest_dir . DIRECTORY_SEPARATOR . $dest_fname;
  if (is_file($dest_path) && !is_writable($dest_path)) {
    finish('failure', 'dest_file_not_overwritable', $dest_path);
  }
} // end of construct_dest_info()

function setup_upload_fname()
{
  global $upload_fname;
  global $uploaded_file_path;
  
  if (!isset($_FILES['imgupload_upload_file'])) {
  	finish('failure', 'no_upload_file');
  }
  $file_ar = $_FILES['imgupload_upload_file'];
  if (!$file_ar || !isset($file_ar['name'])) {
    finish('failure', 'no_upload_file_name');
    exit(0);
  }
  if (intval($file_ar['error']) != 0) {
    finish('failure', 'upload_error', "Upload Error Code: {$file_ar['error']}");
  }

  $upload_fname = htmlentities($file_ar['name']);
  $uploaded_file_path = htmlentities($file_ar['tmp_name']);
} // end of setup_upload_fname()

function setup_test_upland_fname()
{
  global $upload_fname;
  if (!isset($_POST['imgupload_upload_file']) || !$_POST['imgupload_upload_file']) {
    finish('failure', 'no_upload_file_name');
  }
  $upload_fname = basename(htmlentities($_POST['imgupload_upload_file']));
} // end of setup_test_upland_fname()

switch ($command) {
  case 'upload':
    setup_upload_fname();
    construct_dest_info($upload_fname);

    // This line disables file upload. Remove at your own risk.
    // WARNING: removing this line may subject your server to malicious file uploads
    finish('failure', 'File Upload Aborted, although it would have worked at line ' . __LINE__ . ' of ' . basename(__FILE__));

    if (move_uploaded_file($uploaded_file_path, $dest_path)) {
      finish('success');
    } else {
      finish('failure', 'file_move_failed', $dest_path);
    }
    break;
  case 'test':
    setup_test_upland_fname();
    construct_dest_info($upload_fname);
    // if we get here, then uploading is possible, so we only need to know if the file exists or not
    if (file_exists($dest_path)) {
      finish('success', 'dest_file_exists');
    } else {
      finish('success', 'dest_file_not_exist');
    }
    break;
  default:
    finish('failure', 'illegal_command', "'$command'");
    break;
}
?>
