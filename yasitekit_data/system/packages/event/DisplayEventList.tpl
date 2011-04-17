<?php
/*
#doc-start
h1.  DisplayEventList.tpl - Renders Event content

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Requires path parameter parameter: _event_name_ - which is the key of an Event object

#end-doc
*/

/// global variables
?>
{:php-prefix:}
ObjectInfo::do_require_once('Event.php');
if (isset(Globals::$router_obj->event_name)) {
  if (AnInstance::existsP('Event', Globals::$dbaccess, Globals::$router_obj->event_name)) {
    $object_obj = new Event(Globals::$dbaccess, Globals::$router_obj->event_name);
    $page_header = $object_obj->title;
    $page_title = Globals::$site_name . "- $object_obj->title";
    $robots_content = "INDEX, FOLLOW";
    $rendering = $object_obj->render();
  } else {
    $object_obj = new Event(Globals::$dbaccess, Globals::$router_obj->event_name);
    $page_header = "Event Not Found";
    $page_title = Globals::$site_name . " - Event Not Found";
    $robots_content = "NOINDEX, NOFOLLOW";
    $rendering = '';
  }
} else {
  $page_header = 'Error: No Event Specified'; 
  $page_title = 'Event Display';
  $robots_content = "NOINDEX, NOFOLLOW";
  $rendering = '';
}
{:end-php-prefix:}
{:meta robots <?php echo "$robots_content"; ?>:}
{:$rendering:}
<?php // echo Globals::$rc->dump('Event.php - dump of request cleaner'); ?>
