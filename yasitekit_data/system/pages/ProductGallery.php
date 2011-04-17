<?php
/*
#doc-start
h1.  ProductGallery.php - Displays a list of products in one of several format

Created by Mike Howard on 2010-10-25
 
bq. (c) Copyright 2010 Mike. All Rights Reserved. 
Licensed under the terms of GNU LGPL Version 3

Provides:

* Product category selection
* Product summary display - in both table and list format
* Product-per-page setting and display

Each product summary provides a link to the product detail page.

The page is driven by a smiggen of code at the bottom of the file.
It is not documented.

#end-doc
*/

// global variables
ObjectInfo::do_require_once('Product.php');
require_once('Category.php');

$default_product_category = Category::get_default_category('product');
if (!$default_product_category) {
  $default_product_category = 'product';
}
$product_cat_obj = new Category(Globals::$dbaccess, $default_product_category);

Globals::$page_obj->page_header = "Products";
Globals::$page_obj->page_title = Globals::$site_name . " - Product Gallery";
Globals::$page_obj->required_authority = FALSE;

// add jQuery code
// $my_javascript_text =<<<ENDHEREDOC
// <script type="text/javascript" charset="utf-8">
//   ;(function($) {
//     $(document).ready(function() {
//       // initialization code goes here
//       // insert your code
//   })})(jQuery);
// </script>
// ENDHEREDOC;
// $javascript_seg = Globals::$page_obj->get_by_name('javascript');
// $javascript_seg->append(new PageSegText('UNIQUE_PAGESEG_NAME', $my_javascript_text));

// end global variables

// class definitions
/*
#doc-start
h2. Gallery Object

The Gallery object encapsulates the gallery and provides the basic
functions necessary to implement a product gallery.

The Gallery object is an ephemeral PHP object. More specifically,
it does not have a persistent existence and is not aware of any
persistent data. Session data is handled _exterior_ to the Gallery
object

h3. Instantiation.

pre. $foo = new Gallery(dbaccess, $category, $page_number, $max_per_page, $sort_by = NULL, $style = 'list')

* dbaccess - a DBAcess object
* $category - a Category name, instance, or key array which specifies the category of Product
objects to display.
* $page_number - int - the number of the page to display - origin 1
* $max_per_page - integer or the string 'all' - maximum number of products to display per page
* $sort_by - string or NULL - name of one field in the Product object to sort the array of objects by
* $style - string - style of HTML element for _display_gallery()_ to return: either 'list' or 'table'

#doc-end
*/
class Gallery {
  const MAX_PER_PAGE = 20;
  public function __construct($dbaccess, $category, $page_number, $max_per_page, $sort_by = NULL,
      $style = 'list')
  {
    $this->dbaccess = $dbaccess;
    $this->category = $category;
    $this->max_per_page = $max_per_page;
    $this->sort_by = $sort_by ? $sort_by : 'title';
    $this->style = $style;
    $this->product_ar = Category::get_instances_for_category($category, $dbaccess, 'Product');
    $this->sort_products();
    $this->product_count = count($this->product_ar);
    $this->max_page_number = intval($this->max_per_page) ? $this->product_count / $this->max_per_page + 1 : 1;
    
    // set this->page_number, correcting for possible range in case $max_per_page has changed
    $this->page_number = $page_number < 1
      ? 1
      : ($page_number > $this->max_per_page ? $this->max_per_page : $page_number);
  } // end of __construct()

  private function sort_products()
  {
    usort($this->product_ar, new AClassCmp($this->sort_by));
  } // end of sort_products()
  
  // returns array(start_idx, end_idx) for the range of entries in $this->product_ar[]
  private function compute_product_indicies()
  {
    // all is a special case
    if ($this->max_per_page == 'all') {
      $this->page_number = 1;
      return array(0, $this->product_count - 1);
    }
    
    if ($this->page_number > $this->max_page_number) {
      $this->page_number = $this->max_page_number;
    }
    $start_idx = ($this->page_number - 1) * $this->max_per_page;
    $end_index = $start_idx + $this->max_per_page - 1;
    if ($end_index >= $this->product_count) {
      $end_index = $this->product_count - 1;
    }
    return array($start_idx, $end_index);
  } // end of compute_product_indicies()

/*
#begin-doc
h2. Instance Methods

* button_func(idx, $) - this is a function passed to Product->render_table_elt()
or Product->render_list_elt() to create a link to the ProductDisplay.php page.
_$idx_ is an integer to be displayed in the list element.
_$image_elt_ is an HTML im
Products know how to display themselves and summary information about themselves, but
know nothing of the page structure these displays are embedded in. They rely on having
a function passed in to create the link needed. This is that function for ProductGallery.php
#end-doc
*/


  public function button_func($idx, $image_elt)
  {
    // NOTE: $idx is offset from the array origin by 1 (for silly humans)
    return "<a href=\"ProductDisplay.php?product_name={$this->product_ar[$idx - 1]->name}\">$image_elt</a>";
  } // end of button_func()
  
  private function table_gallery($classes, $attributes)
  {
    list($start_idx, $end_index) = $this->compute_product_indicies();
    $str = "<table id=\"product-gallery\" " . ($classes?" class=\"$classes\"":'')
        . ($attributes? " $attributes":'') . ">\n";
    for ($idx = $start_idx;$idx <= $end_index; $idx += 1) {
      $product_obj = $this->product_ar[$idx];
      $str = "<tr>\n";
      $str .= "<td>$idx</td>\n";
      $str .= "<td>$this->title</td>\n";
      $str .= "<td><a class=\"box\" href=\"ProductDisplay.php?product_name={$product_obj->name}\">"
        . $product_obj->render_summary('Click for Details')
        . "</a></td>\n";
      $str .= "</tr>\n";
    }
    $str .= "</table>\n";
    return $str;
  } // end of table_gallery()
  
  private function list_gallery($classes, $attributes)
  {
    list($start_index, $end_index) = $this->compute_product_indicies();
    $str = "<ul id=\"product-gallery\" " . ($classes?" class=\"$classes\"":'')
        . ($attributes?" $attributes":'') . ">\n";
    for ($idx = $start_index;$idx <= $end_index; $idx += 1) {
      $product_obj = $this->product_ar[$idx];
      $str .= "<li>" . ($idx + 1) . ". $product_obj->title "
        . "<a class=\"box\" href=\"ProductDisplay.php?product_name={$product_obj->name}\">"
        . $product_obj->render_summary('Click for Details')
        . "</a>";
      $str .= "</li>\n";
    }
    $str .= "</ul>\n";
    return $str;
  } // end of list_gallery()

/*
#begin-doc
* display_gallery(classes, attributes) - returns either a _table_ or _list_ HTML element
formatting the current page of the product gallery. _$classes_ and _$attributes_ are
class names and attributes which are added to the _ul_ or _table_ element.
#end-doc
*/


  public function display_gallery($classes = '', $attributes = '')
  {
    switch ($this->style) {
      case 'table':
        return $this->table_gallery($classes, $attributes);
        break;
      case 'list':
        return $this->list_gallery($classes, $attributes);
      default:
        break;
    }
  } // end of display_gallery()

/*
#begin-doc
* page_nav($classes, $attributes, $range = 3, $endpoints = TRUE) - returns an HTML ul element of page numbers
so that the user may select the page number to view. Page numbers are limited
to the current page plus or minus $range. if _$endpoints_ is TRUE and the first and/or last
page numbers are outside the range of 'current page +/- $range', then links to the
first and last pages are added - as necessary.
#end-doc
*/


  public function page_nav($classes = '', $attributes = '', $range = 3, $endpoints = TRUE)
  {
    // if we don't have more than one page, then return empty string
    if ($this->max_per_page == 'all' || $this->product_count <= $this->max_per_page) {
      return '';
    }
    
    // we have pages
    $str = "<ul id=\"gallery-page-nav\" class=\"$classes\" $attributes>\n";
    $str .= "  <li>Page Navigation: Click a Page Number to go there</li>";
    $last_page = ($this->product_count - 1)/ $this->max_per_page + 1;

    if ($endpoints && $this->page_number - $range > 1) {
      $url = IncludeUtilities::rewrite_qs($_SERVER['REQUEST_URI'], "page_number=1");
      $str .= "  <li><a class=\"box\" href=\"$url\">First</a></li>\n";
    }
    for ($idx=-$range;$idx<=$range;$idx++) {
      if (($page_number = $this->page_number + $idx) >= 1 && $page_number <= $last_page) {
        if ($page_number != $this->page_number) {
          $url = IncludeUtilities::rewrite_qs($_SERVER['REQUEST_URI'], "page_number={$page_number}");
          $str .= "  <li><a class=\"box\" href=\"$url\">$page_number</a></li>\n";
        } else {
          $str .= "<li class=\"bold\">$page_number</li>\n";
        }
      }
    }
    if ($endpoints && $this->page_number + $range < $last_page) {
      $url = IncludeUtilities::rewrite_qs($_SERVER['REQUEST_URI'], "page_number={$this->max_per_page}");
      $str .= "  <li><a class=\"box\" href=\"$url\">Last</a></li>\n";
    }
    $str .= "</ul>\n";
    return $str;
  } // end of page_nav()

/*
#begin-doc
* items_per_page($classes = '', $attributes = '', $delta = 20, $max = 60) -
returns an HTML form which which allows selecting the number of items per page.
_$classes_ and _$attributes_ are added to the _form_ element.
_$delta_ is the difference between item ranges. _$max_ is the maximum number
of items-per-page choices. The choice _all_ is always displayed
#end-doc
*/


  public function items_per_page($classes = "", $attributes = "", $delta = 20, $max = 60)
  {
    // if everything fits within $delta - we only have one page, so there's no reason
    //   to change max_per_page
    if ($this->product_count <= $delta) {
      return '';
    }
    
    $str = "<form action=\"ProductGallery.php\" method=\"post\" accept-charset=\"utf-8\""
      . ($classes?" class=\"$classes)\"":'') . ($attributes?" $attributes":'') . ">\n";
    $str .= "  <select name=\"max_per_page\">\n";
    if ($max > $this->product_count) {
      $max = $this->product_count;
    }
    for ($items = $delta;$items <= $max; $items += $delta) {
      $selected = $this->max_per_page == $items ? " selected" :'';
      $str .= "    <option value=\"$items\"{$selected}>$items</option>\n";
    }
    $selected = $this->max_per_page == 'all' ? " selected" :'';
    $str .= "    <option value=\"all\"{$selected}>All</option>\n";
    $str .= "  </select>\n";
    $str .= "  <input type=\"submit\" name=\"submit\" value=\"Change Items Per Page\">\n";
    $str .= "</form>\n";
    return $str;
  } // end of items_per_page()

/*
#begin-doc
* options_elt_style() - returns an HTML options element which can be included in a select
element to chose _style_. This may disappear
#end-doc
*/

  
  public function options_elt_style()
  {
    $str = '';
    foreach (array(array('list', 'List'), array('table', 'Table')) as $style) {
      $str .= "<option value=\"{row[0]}\" " . ($this->style == $style ? 'selected':'') . ">{$row[1]}</option>\n";
    }
    return $str;
  } // end of options_elt_style()
  
  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\">\n";
    $str .= $msg ? "$msg\n" : '';
    foreach (get_object_vars($this) as $attr => $val) {
      $str .= "  $attr: $val\n";
    }
    $str .= "</div>\n";
    return $str;
  } // end of dump()
}
// end class definitions

/*
#begin-doc
h2. Functions

These top level functions are defined

* get_param($name, $default) - returns the named parameter, if it is defined
in the Query String, a Post Parameter, or in the user's session, or _$default_.
#end-doc
*/


// function definitions
function get_param($name, $default) {
  $query_param = "safe_request_$name";
  if (isset(Globals::$rc->$query_param)) {
    return Globals::$rc->$query_param;
  }
  $session_param = "product_gallery_{$name}";
  if (isset(Globals::$session_obj->$session_param)) {
    return Globals::$session_obj->$session_param;
  }
  return $default;
}

/*
#begin-doc
* dynamic_display() - returns saves required parameters
in the user's session store and then string containing the current gallery.
#end-doc
*/

function dynamic_display($product_cat_obj)
{
  if (!($product_catgory = Category::get_default_category('product'))) {
    $product_catgory = 'product';
  }
  $page_number = get_param('page_number', 1);
  $max_per_page = get_param('max_per_page', Gallery::MAX_PER_PAGE);
  $sort_by = get_param('sort_by', 'title');
  $style = get_param('style', 'list');

  $gallery = new Gallery(Globals::$dbaccess, $product_catgory, $page_number, $max_per_page, $sort_by = NULL,
        $style = 'list');

  Globals::$session_obj->product_gallery_page_number = $gallery->page_number;
  Globals::$session_obj->product_gallery_max_per_page = $gallery->max_per_page;
  Globals::$session_obj->product_gallery_sort_by = $gallery->sort_by;
  Globals::$session_obj->product_gallery_style = $gallery->style;

  $str = "<h1>Products in Category \"{$product_cat_obj->title}\"</h1>\n";

  $str .= $gallery->page_nav();
  $str .= $gallery->items_per_page();
  $str .= $gallery->display_gallery();
  return $str;
} // end of dynamic_display()


// dispatch actions
switch (Globals::$rc->safe_post_submit) {
  case 'Change Items Per Page':
    echo dynamic_display($product_cat_obj);
    break;
  default:
    echo dynamic_display($product_cat_obj);
    break;
}

?>
