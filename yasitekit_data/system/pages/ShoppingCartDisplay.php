<?php
/*
#doc-start
h1.  page_template.php - SUMMARY

Created by Mike Howard on 2010-10-27
 
bq. (c) Copyright 2010 Mike. All Rights Reserved. 
Licensed under the terms of GNU LGPL Version 3

Shopping Cart display and manipulation

#end-doc
*/

// global variables
ObjectInfo::do_require_once('ShoppingCart.php');
Globals::$page_obj->page_header = Globals::$site_name . " - Shopping Cart";
Globals::$page_obj->page_title = "Shopping Cart";
Globals::$page_obj->form_action = "ShoppingCartDisplay.php";
Globals::$page_obj->required_authority = 'C';

$shopping_cart_order_number = Globals::$session_obj->shopping_cart_order_number;
$shopping_cart = new ShoppingCart(Globals::$dbaccess, $shopping_cart_order_number);
// Globals::$page_obj->required_authority = FALSE;

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

// function definitions

function add_to_cart($shopping_cart, $rc) {
  echo $rc->dump('Add to Cart');
}

function update_cart($shopping_cart, $rc) {
  echo $rc->dump('Update Cart');
}

// end function definitions

// initial processing of POST data

// dispatch actions
switch (strtolower(Globals::$rc->safe_post_submit)) {
  case 'add to cart':
    add_to_cart($shopping_cart, Globals::$rc);
    break;
  case 'update':
    update_cart($shopping_cart, Globals::$rc);
    break;
  default:
    break;
}

echo $shopping_cart->render_cart();

?>
