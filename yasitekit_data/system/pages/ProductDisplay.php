<?php
/*
#doc-start
h1.  ProductDisplay.php - Display's One product with Add to Cart options

Created by Mike Howard on 2010-10-22
 
bq. (c) Copyright 2010 Mike. All Rights Reserved. 
Licensed under the terms of GNU LGPL Version 3

This displays a single product.

The display is controlled by the following parameters:

* Globals::$rc->safe_request_product_name - the name value of the product to be displayed.
* Globals::$session_obj->product_name - current product name attribute
* Globals::$session_obj->category_defaults['deliverable'] - default deliverables category
chosen by user

#end-doc
*/

// global variables
ObjectInfo::do_require_once('Product.php');
require_once('Category.php');

if (!Globals::$rc->safe_request_product_name 
    && (!isset(Globals::$session_obj->product_name) || !Globals::$$session_obj->product_name)) {
  Globals::add_message('No Product Selected - (sniff sniff)');
  Globals::$page_obj->page_header = Globals::$site_name . " - No Product Selected";
  Globals::$page_obj->page_title = "No Product Selected";
  Globals::$page_obj->required_authority = FALSE;
  return;
}

$product_name = Globals::$rc->safe_request_product_name ? Globals::$rc->safe_request_product_name
  : Globals::$session_obj->product_name;
$product = new Product(Globals::$dbaccess, $product_name);
Globals::$session_obj->product_name = $product->name;
Globals::$page_obj->page_header = Globals::$site_name . " - " . $product->title;
Globals::$page_obj->page_title = $product->title;
Globals::$page_obj->form_action = "";
Globals::$page_obj->required_authority = FALSE;

if (($user_deliverable_category = Category::get_default_category('deliverable')) === FALSE) {
  $user_deliverable_category = $product->default_category('deliverable_category');
}

$product_deliverables = $product->select_objects_in_category('deliverable_category', $user_deliverable_category, 'Deliverable');

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

echo $product->render_product();

echo "<table>\n";
foreach ($product_deliverables as $deliverable) {
  echo $deliverable->add_to_cart_form($product, 'ShoppingCartDisplay.php');
}
echo "</table>\n";
?>
