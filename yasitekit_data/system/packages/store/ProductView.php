<?php
/*
#doc-start
h1.  ProductView.php - tracks view staticstics for images

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.
    
ProductView objects track number of views of images and view counts,
tracked by cookies.

h2. Attributes

* cookie - the value of the cookie associated with these views
* product_name - name of image - this the value of the key field Product->name
* timestamp - time of last view by this cookied access
* view_count - integer number of times this image has been viewed. NOTE that
this will be inflated by reloads

h2. Methods

For the most part, see "aclass.php":/doc.d/system-includes/aclass.html

h3. Class Methods

top_ne_images(dbaccess, $n = 1, $start_date = NULL) - returns a list of the
Product keys (name) for the top _n_ images by _view_count_ for all viewings.

Parameters:

* dbaccess - a DBAccess instance
* n - defaults to 1 for top viewed image. Number of image names to return
* start_date - if not NULL, specifies the date range to restrict viewings to.
Goes from _start_date_ to present.

h3. Instance Methods

* latest_product_for($cookie) - returns the name of the last image viewed
by this _cookie_ or FALSE.

#end-doc
*/

// global variables
require_once('aclass.php');

$test_imageview_values = array(
  'cookie' => 'COOKIEVALUE',
  'timestamp' => 'now',
  'product_name' => 'foo_the_image',
  );
AClass::define_class('ProductView', array('cookie', 'product_name'),
    array(
      array('cookie', 'varchar(255)', 'Cookie'),
      array('product_name', 'varchar(255)', 'Product Name'),
      array('timestamp', 'datetime', 'Access Timestamp'),
      array('view_count', 'int', 'View Count'),
    ),
    NULL);
// end global variables

// class definitions
class ProductView extends AnInstance {
  public function __construct($dbaccess, $attr_args = array())
  {
    parent::__construct('ProductView', $dbaccess, $attr_args);
    if (!isset($this->view_count)) $this->view_count = 0;
  } // end of __construct()
  
  public function latest_product_for($cookie)
  {
    $tmp = $this->get_objects_where(array('cookie' => $cookie), 'order by timestamp desc');
    return count($tmp) > 0 ? $tmp[0]->product_name : FALSE;
  } // end of latest_product_for()
  
  public static function top_n_images($dbaccess, $n = 10, $start_date = NULL)
  {
    $where = $start_date ? "where timestamp >= '$start_date'" : '';
    if ($n < 1) $n = 1;
    if ($n > 20) $n = 20;
    $class_instance = AClass::get_class_instance('ProductView');
    // $sql = "select product_name, sum(view_count) as views from imageview $where ";
    $tmp = $dbaccess->select_from_table($class_instance->tablename, 'product_name,sum(view_count) as views',
      $where, "group by product_name order by 2 desc limit $n");
    $ar = array();
    if (is_array($tmp)) {
      foreach ($tmp as $row) {
        $ar[] = $row['product_name'];
      }
    }
    return $ar;
  } // end of top_n_images()
}
// end class definitions
?>
