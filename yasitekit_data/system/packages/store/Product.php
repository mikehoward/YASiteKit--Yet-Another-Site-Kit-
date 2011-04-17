<?php
/*
#doc-start
h1.  Product.php - An Product which is for display and sale

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This object defines Product (which extend AClasls) and the ProductManager class
which implements the user interface for managing and updating Product instances.

An Product instance contains the data necessary to manage and display an image
product which is for sale.

It is specialized for YASiteKit.

h2. Attributes

* name - varchar(255)', 'Product Name'
* product_owner - join(Account.userid) - Product Owner User Id
* title - varchar(255) - Product Title
* description - text - Product Description
* fulfillment_url - text - Fullfilment URL
* available - enum(Y,N) - Product Available
* on_hand - int - Quantity Available
* sold_count - int - Number Sold
* path - interpolate-able string - Path to Product File
* thumbnail - file - thumbnail image
* image01 - 'file(products/{product_owner}/{name}/image01.jpg) - Product Image # 1
* image02 - 'file(products/{product_owner}/{name}/image02.jpg) - Product Image # 2
* image03 - 'file(products/{product_owner}/{name}/image03.jpg) - Product Image # 3
* image04 - 'file(products/{product_owner}/{name}/image04.jpg) - Product Image # 4
* image05 - 'file(products/{product_owner}/{name}/image05.jpg) - Product Image # 5
* image06 - 'file(products/{product_owner}/{name}/image06.jpg) - Product Image # 6
* image07 - 'file(products/{product_owner}/{name}/image07.jpg) - Product Image # 7
* image08 - 'file(products/{product_owner}/{name}/image08.jpg) - Product Image # 8
* image09 - 'file(products/{product_owner}/{name}/image09.jpg) - Product Image # 9



#end-doc
*/

// global variables
require_once('aclass.php');
require_once('Category.php');

AClass::define_class('Product', 'name',
  array(
    array('name', 'varchar(255)', 'Product Name'),
    array('product_owner', 'join(Account.userid)', 'Artist User Id'),
    array('title', 'varchar(255)', 'Product Title'),
    array('product_category', 'category(product)', 'Product Categories'),
    array('deliverable_category', 'category(deliverable)', 'Deliverable Categories'),
    array('description', 'text', 'Product Description'),
    array('available', 'enum(Y,N)', 'Product Available'),
    array('sold_count', 'int', 'Number Sold'),
    array('path', 'file(products/{product_owner}/{name}/content,private)', 'Path to Product File'),
    array('thumbnail', 'file(products/{product_owner}/{name}/thumbnail.jpg)', 'Thumbnail'),
    array('image01', 'file(products/{product_owner}/{name}/image01.jpg)', 'Image 01'),
    array('image02', 'file(products/{product_owner}/{name}/image02.jpg)', 'Image 02'),
    array('image03', 'file(products/{product_owner}/{name}/image03.jpg)', 'Image 03'),
    array('image04', 'file(products/{product_owner}/{name}/image04.jpg)', 'Image 04'),
    array('image05', 'file(products/{product_owner}/{name}/image05.jpg)', 'Image 05'),
    array('image06', 'file(products/{product_owner}/{name}/image06.jpg)', 'Image 06'),
    array('image07', 'file(products/{product_owner}/{name}/image07.jpg)', 'Image 07'),
    array('image08', 'file(products/{product_owner}/{name}/image08.jpg)', 'Image 08'),
    array('image09', 'file(products/{product_owner}/{name}/image09.jpg)', 'Image 09'),
    array('fulfillment_url', 'varchar(255)', 'Fulfillment URL'),
  ),
  array(
      'name' => 'public',
      'product_owner' => array('required' => TRUE),
      'product_category' => 'category_deep',
      'deliverable_category' => 'category_deep',
      'title' => array('required', 'public'),
      'description' => array('required', 'public'),
      'available' => 'public',
    )); // no additional properties

// end global variables

// class definitions
class Product extends AnInstance {
  const THUMBNAIL_MAX = 75;   // pixels
  private $deliverables_ar = array();

  public function __construct($dbaccess, $attribute_values = NULL)
  {
    parent::__construct('Product', $dbaccess, $attribute_values);
  } // end of __construct()

/*
#begin-doc
h2. Class Methods

* Product::product_thumbnails($dbaccess, $category_name) - a string containing
an HTML unordered list of product thumbnails for all products in _$dbaccess_
which are in category _$category_name_, or one of its descendents.
#end-doc
*/

  public static function product_thumbnails($dbaccess, $category_name)
  {
    if ($category_name[0] != '_') {
      $category_name = '_product_' . $category_name;
    }
    $product_list = Category::get_instances_for_category($category_name, $dbaccess, 'Product');
    $str = "<ul id=\"product-thumbnails\">\n";
    foreach ($product_list as $product_obj) {
      $str .= $product_obj->display_thumbnail();
    }
    return $str . "<ul><!-- product-thumbnails -->\n";
  } // end of product_thumbnail()

/*
#begin-doc
h2. Instance Methods

* save_as_current_product() - saves _$this->name_ in session store.
#end-doc
*/
  
  public function save_as_current_product()
  {
    if (Globals::$session_obj instanceof Session) {
      Globals::$session_obj->product_name = $this->name;
    }
  } // end of save_as_current_product()
  
/*
#begin-doc
* process_form($rc) - overrides parent _process_form()_ to automatically shrink
thumbnail images to correct size.
#end-doc
*/

  public function process_form($rc)
  {
    parent::process_form($rc);
    if ($this->thumbnail == 'defined') {
      $this->make_thumbnail();
    }
  } // end of process_form()
  
  private function make_thumbnail()
  {
    if (!isset($this->thumbnail)) {
      return;
    }
    require_once('ImageObject.php');
    $path_to_image = $this->interpolate_string($this->get_prop('thumbnail', 'path'));
    $img_obj = new ImageObject($path_to_image);
    if ($img_obj->max == Product::THUMBNAIL_MAX) {
      return;
    }
    $thumbnail_obj = $img_obj->shrink(Product::THUMBNAIL_MAX);
    $thumbnail_obj->save($path_to_image, 'jpg', TRUE);
  } // end of make_thumbnail()

/*
#begin-doc
* img_elt_thumbnail() - returns an HTML img element which will display the thumbnail
image for _$this_ product.
#end-doc
*/

  public function img_elt_thumbnail()
  {
    return "<img src=\"$this->thumbnail\" alt=\"$this->title\">";
  } // end of display_thumbnaile()

/*
#begin-doc
* render_summary($alt_text = $this->title) - returns an HTML _img_ element if the product thumbnail
is set, otherwise simply the supplied alt text. 
#end-doc
*/

  public function render_summary($alt_text = NULL)
  {
    if (!$alt_text) {
      $alt_text = $this->title;
    }
    return isset($this->thumbnail) && $this->thumbnail ? "<img src=\"$this->thumbnail\" alt=\"$alt_text\">"
        : $alt_text;
  } // end of display_list_elt()

/*
#begin-doc
* render_product() - returns a string completely describing the product. Has the following
HTML id's set:
** product - id of wrapping _div_
** product-title - id of _div_ wrapping the title, owner, and description block
** product-image-list - id of _ul_ wrapping list of images
#end-doc
*/

  public function render_product()
  {
    // display product - designed to be floated right
    $str = "<div> <!-- product -->\n";
    $str .=  "  <div id=\"product-title\">\n";
    $str .=  "    <p class=\"larger center\">&ldquo;{$this->title}&rdquo;</p>\n";
    $str .=  "    <p class=\"smaller center\">by {$this->join_value_of('product_owner')}</p>\n";
    $str .=  "    <p class=\"text-left\">{$this->description}</p>\n";
    $str .=  "  </div> <!-- product-title -->\n";
    
    $str .=  "    <ul id=\"product-image-list\">\n";
    for ($idx = 1;$idx <= 9;$idx++) {
      $image_attr = "image" . sprintf("%02d", $idx);
      if (isset($this->$image_attr) && $this->$image_attr) {
        $str .=  "      <li><img src=\"\" alt=\"Product Image $idx\"></li>\n";
      }
    }
    $str .=  "    </ul>\n";
    $str .=  "</div> <!-- product -->\n";

    return $str;
  } // end of render_product()

/*
#begin-doc
* deliverables($deliverable_category) - returns all deliverables which are in
sub-categories of _$this->deliverable_category_ and also in _$deliverable_category_.
The list is not ordered in any particular way.
#end-doc
*/

  public function deliverables($category)
  {
    return $this->select_objects_in_category('deliverable_category', $category, 'Deliverable');
  } // end of deliverables()

/*
#begin-doc
* dump($msg = '') - augments the standard instance method _dump()_ with the deliverables
available for _this_ product.
#end-doc
*/

  public function dump($msg = '')
  {
    $str = parent::dump($msg);
    foreach ($this->category_paths_of('deliverable_category') as $category) {
      foreach ($this->deliverables($category) as $deliverable) {
        $str .= $deliverable->dump("Deliverable in $category");
      }
    }
    return $str;
  } // end of dump()
}


class ProductManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Product', 'title');
  } // end of __construct()
}

// end class definitions
?>
