<?php
/*
#doc-start
h1.  DisplayObject.tpl - Renders Object content

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Requires path parameter parameter: _object_key_ - which is the key of an Object object

#end-doc
*/

/// global variables
?>
{:php-prefix:}
require_once('Object.php');
if (isset(Globals::$router_obj->object_key)) {
  if (AnInstance::existsP('Object', Globals::$dbaccess, Globals::$router_obj->object_key)) {
    $object_obj = new Object(Globals::$dbaccess, Globals::$router_obj->object_key);
    $page_header = $object_obj->title;
    $page_title = Globals::$site_name . "- $object_obj->title";
    $robots_content = "INDEX, FOLLOW";
    $rendering = $object_obj->render();
  } else {
    $object_obj = new Object(Globals::$dbaccess, Globals::$router_obj->object_key);
    $page_header = "Object Not Found";
    $page_title = Globals::$site_name . " - Object Not Found";
    $robots_content = "NOINDEX, NOFOLLOW";
    $rendering = '';
  }
} else {
  $page_header = 'Error: No Object Specified'; 
  $page_title = 'Object Display';
  $robots_content = "NOINDEX, NOFOLLOW";
  $rendering = '';
}
{:end-php-prefix:}
{:meta robots <?php echo "$robots_content"; ?>:}
{:$rendering:}
<?php // echo Globals::$rc->dump('Object.php - dump of request cleaner'); ?>
