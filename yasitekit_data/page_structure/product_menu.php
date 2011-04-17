<?php
/*
#doc-start
h1.  product_menu.php - Product Menu Navigation

Created by  on 2010-03-25.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. ProductMenu Object

$product_menu = new ProductMenu(dbaccess,  array(option => value, ...));

Where:

* dbaccess - is a DBAccess object - typically Globals::$dbaccess
* array(...) - an array of options from the following list
** recent_popular_interval - default is 30 - date interval in days to offset backward from today
** popular_products_max - default is  10 - number of products to show in list
** products_per_menu - default is 20 - number of products to show per panel in 'products' mode

h3. Session Keys

* product_menu_mode - one of:
** grouping
** products
** favorites
** recently-viewed
** popular
** recent-popular
* product_menu_grouping - only valid if product_menu_mode is grouping or products - name
of current grouping
* product_menu_product - name of currently selected product
* product_menu_start_offset - index into list of products by title to start menu in products mode
* product_menu_end_offset - index into list of products by title to end menu in products mode

h3. Attributes

None

h3. Class Methods

None

h3. Instance Methods

*render()* returns a string containing the current menu.

#end-doc
*/

// global variables
require_once('Product.php');

// end global variables

// class definitions
class ProductMenu {
  static private $legal_options = array();
  public $mode = 'product';
  private $options = array(
    'recent_popular_interval' => 30,  // date interval in days to offset backward from today
    'popular_products_max' => 10,       // number of products to show in list
    'products_per_menu' => 20,          // number of products to show per panel in 'products' mode
    );
  private $dbaccess = NULL;
  static private $legal_modes = array('popular', 'recent-popular',
    'grouping', 'products',
    'favorites', 'recently-viewed', );
  
  public function __construct($dbaccess, $options = NULL)
  {
    $this->dbaccess = $dbaccess;
    if ($options) {
      foreach ($options as $option => $value) {
        if (!array_key_exists($option, $this->options)) {
          throw new ProductMenuException("ProductMenu::__construct(options): Illegal option name: '$option'");
        }
        $this->options[$option] = $value;
      }
    }
  } // end of __construct()
  
  private function get_products($start_offset, $end_offset)
  {
    require_once('Product.php');
    $orderby = "order by title";
    if ($start_offset < $end_offset) {
      $tmp = $this->end_offset - $this->start_offset + 1;
      $order .= " limit $tmp offset {$this->start_offset}";
    }
    $tmp_obj = new Product($this->dbaccess);

    return $tmp_obj->get_objects_where(NULL, $orderby);
  } // end of get_products()
  
  
  private function prepare_to_render()
  {
    $this->mode = 'products';
    $this->start_offset = 0;
    $this->end_offset = 0;
    $this->menu_title = "YASiteKit Swag - by Zazzle";
    $this->product_list = $this->get_products($this->start_offset, $this->end_offset);
    return;
  } // end of prepare_to_render()
  
  public function render()
  {
    $this->prepare_to_render();
    
    $str = "<div id=\"product-menu\">\n";
    $str .= "<p id=\"product-menu-title\">$this->menu_title</p>\n";
    $str .= " <ul>\n";
    foreach ($this->product_list as $product) {
      $str .= "<li class=\"box clear\">"
        . "<a href=\"/DisplayProduct.php?product={$product->name}\" title=\"$product->title\">"
        . "<span class=\"\">$product->title</span><br>"
        . "</a></li>\n";
    }
    $str .= " </ul>\n";
    $str .= "</div>\n";
    
    return $str;
  } // end of render()
}

// end class definitions

// dispatch actions

Globals::$product_menu = new ProductMenu(Globals::$dbaccess);
echo Globals::$product_menu->render();
?>
