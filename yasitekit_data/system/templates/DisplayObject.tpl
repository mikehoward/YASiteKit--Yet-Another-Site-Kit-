<?php
/*
#doc-start
h1.  DisplayObject.tpl - Object Display

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
?>
{:php-setup:}
// this is a comment
{:end-php-setup:}
{:php-prefix:}
if (Globals::$router_obj->object) {
  $object = Globals::$router_obj->object;
//  $key_value = Globals::$router_obj->key_value;
} else {
  Globals::add_message("No Object Specified");
}
if (($incl_result = include_once("{$object}.php")) === FALSE) {
  IncludeUtilities::redirect_to('/page_not_found_page.tpl?page_not_found_page=' . $_SERVER['REQUEST_URI'], basename(__FILE__) . ": " . __LINE__);
} else {
  $manager_obj = "{$object}Manager";
  $page_header = Globals::$site_name . " - $object Display";
  $page_title = "$object Display";
}
{:end-php-prefix:}
{:authority S,X:}
{:yatemplate default_template.tpl:}
<?php
if (class_exists($manager_obj)) {
  $obj = new $manager_obj(Globals::$dbaccess);
  // $obj->set_option('form_action', Globals::$router_obj->uri);
  echo $obj->render(Globals::$router_obj->uri);
} else {
  // FIXME!!!!
}
?>
