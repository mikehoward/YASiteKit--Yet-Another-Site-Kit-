<?php
/*
#doc-start
h1.  PageView.php - records statistics of page views

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
require_once('aclass.php');

$keys_list = array('cookie', 'timestamp');
$attribute_defs = array(
  array('cookie', 'varchar(40)', 'Cookie'),
  array('timestamp', 'datetime', 'Access Timestamp'),
  array('url', 'text', 'URL'),
  );
$test_pageview_values = array(
  'cookie' => 'COOKIEVALUE',
  'timestamp' => 'now',
  'url' => 'URL/to/page',
  );
AClass::define_class('PageView', $keys_list, $attribute_defs, NULL);
// end global variables

// class definitions
class PageViewException extends Exception {}

class PageView extends AnInstance {
  public function __construct($dbaccess, $args = array())
  {
    if (is_string($args)) {
      $attribute_values = array();
      $attribute_values['cookie'] = Globals::$user_cookie;
      $attribute_values['timestamp'] = 'now';
      $attribute_values['url'] = $args;
    } elseif (is_array($args)) {
      $attribute_values = $args;
    } elseif ($args) {
      throw new PageViewException("PageView::__construct(): Illegal data type for 'url'");
    }
    parent::__construct('PageView', $dbaccess, $attribute_values);
    if ($args) {
      $ajoin = AJoin::get_ajoin(Globals::$dbaccess, 'PageView', 'CookieTrack');
      $ajoin->add_to_join($this, Globals::$cookie_track);
    }
  } // end of __construct()
}
// end class definitions
?>
