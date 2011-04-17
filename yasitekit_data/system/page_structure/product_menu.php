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
ObjectInfo::do_require_once('Product.php');

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

  private function get_popular($where, $orderby)
  {
    ObjectInfo::do_require_once('Product.php');
    $max_len = 10;
    $tmp_ar = $this->dbaccess->select_from_table('productview', 'product_name, sum(view_count) as cnt', $where, $orderby  );
    $len = count($tmp_ar) < $max_len ? count($tmp_ar) : $max_len;
    $ar = array();
    for ($i=0;$i<$len;$i++) {
      $ar[] = new Product($this->dbaccess, array('name' => $tmp_ar[$i]['product_name']));
    }
    return $ar;
  } // end of get_popular()
  
  private function get_products($start_offset, $end_offset)
  {
    ObjectInfo::do_require_once('Product.php');
    $orderby = "order by title";
    if ($start_offset < $end_offset) {
      $tmp = $this->end_offset - $this->start_offset + 1;
      $order .= " limit $tmp offset {$this->start_offset}";
    }
    $tmp_obj = new Product($this->dbaccess);

    return $tmp_obj->get_objects_where(NULL, $orderby);
  } // end of get_products()
  
  private function get_favorites()
  {
    # code...
  } // end of get_favorites()
  
  private function get_recently_viewed()
  {
    # code...
  } // end of get_recently_viewed()
  
  private function prepare_to_render()
  {
    if (Globals::$session_obj instanceof Session) {
      if (!isset(Globals::$session_obj->product_menu_mode)
          || !in_array(Globals::$session_obj->product_menu_mode, ProductMenu::$legal_modes)) {
        Globals::$session_obj->product_menu_mode = 'products';
      }
      $this->mode = Globals::$session_obj->product_menu_mode;
    } else {
      $this->mode = 'products';
      $this->start_offset = 0;
      $this->end_offset = 0;
      $this->menu_title = "by Products";
      $this->product_list = $this->get_products($this->start_offset, $this->end_offset);
      return;
    }
    
    switch ($this->mode) {
      case 'popular':
        $this->menu_title = 'Most Popular';
        $this->product_list = $this->get_popular(NULL, "group by 1 order by 2 desc");
        break;
      case 'recent-popular':
        $this->menu_title = 'Recently Popular';
        $time_str = strftime("%Y-%m-%d", time() - 86400 * $this->options['recent_popular_interval']);
        $this->product_list = $this->get_popular("timestamp >= {$time_str}", "group by 1 order by 2 desc");
        break;
      case 'products':
        $this->start_offset = Globals::$session_obj->product_menu_start_offset;
        $this->end_offset = Globals::$session_obj->product_menu_end_offset;
        $this->menu_title = ($this->start_offset && $this->end_offset)
          ? "Products $start_offset through $this->end_offset"
          : "by Products";
        $this->product_list = $this->get_products($this->start_offset, $this->end_offset);
        break;
      case 'favorites':
        $this->menu_title = 'Your Favorites';
        $this->product_list = $this->get_favorites();
        break;
      case 'recently-viewed':
        $this->menu_title = 'Your Most Recent Views';
        $this->product_list = $this->get_recently_viewed();
        break;
    }
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
        . "<span class=\"smaller\">$product->title</span><br>"
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
