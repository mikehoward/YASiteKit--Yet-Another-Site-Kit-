<?php
/*
#doc-start
h1.  test_Map.php - runs cursory tests on all Map methods

Created by  on 2010-03-31.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

tests MapForObj

tests Map:


#end-doc
*/

// global variables

set_include_path('..');
require_once('config.php');
Globals::$private_data_root = '..';
// Globals::$images_root = 'images' . DIRECTORY_SEPARATOR;
// require_once('test_common.php');
set_include_path(Globals::$private_data_root . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'includes' . PATH_SEPARATOR .
  Globals::$private_data_root . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'objects' . PATH_SEPARATOR .
  get_include_path());
// date_default_timezone_set('America/Denver');
// if (!isset(Globals::$rc)) {
//   require_once('request_cleaner.php');
//   Globals::$rc = new RequestCleaner('post', 'get');
// }
require_once('Map.php');
require_once('test_functions.php');

// array elements are cls_name, source_defs, target_defs
$map_for_obj_need_mapP_tests = array(
  // First Test
  array('First', // cls_name
    array(    // source_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    array(    // target_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    ),
  // Second Test
  array('Second', // cls_name
    array(    // source_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    array(    // target_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    ),
  // Third Test
  array('Third', // cls_name
    array(    // source_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    array(    // target_defs
      'defs' => array(
        array('', '', ''),),
      'keys' => array('',),
      'props' => array(
        '' => array(''),),
      ),
    ),
    // Fourth Test
    array('Fourth', // cls_name
      array(    // source_defs
        'defs' => array(
          array('', '', ''),),
        'keys' => array('',),
        'props' => array(
          '' => array(''),),
        ),
      array(    // target_defs
        'defs' => array(
          array('', '', ''),),
        'keys' => array('',),
        'props' => array(
          '' => array(''),),
        ),
      ),
  );
  
foreach ($map_for_obj_need_mapP_tests as $test) {
  list($cls_name, $source_defs, $target_defs) = $test;
  
}