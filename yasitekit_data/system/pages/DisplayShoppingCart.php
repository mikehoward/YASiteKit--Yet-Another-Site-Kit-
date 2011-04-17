<?php
/*
#doc-start
h1.  DisplayShoppngCart.php - Customer Shopping Cart Managment

Created by Mike Howard on 2010-09-28
 
bq. (c) Copyright 2010 Mike. All Rights Reserved. 
All Rights Reserved.
Licensed under the terms of GNU LGPL Version 3

#end-doc
*/

// global variables
ObjectInfo::do_require_once('ShoppingCart.php');
ObjectInfo::do_require_once('Product.php');
ObjectInfo::do_require_once('Deliverable.php');

Globals::$page_obj->page_header = Globals::$site_name . " - Shopping Cart";
Globals::$page_obj->page_title = "Shopping Cart";
Globals::$page_obj->form_action = "DisplayShoppingCart.php";
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
class Foo {
  public function __construct($args)
  {
    # code...
  } // end of __construct()
}
// end class definitions

// function definitions
function dynamic_display()
{
  // $foo = Globals::$rc->safe_post_VARIABLE;
  // Globals::$dbaccess->FUNCTION(ARGS);
  // Globals::add_message(SOME MESSAGE);
  // Globals::session_obj->add_message(MESSAGE FOR DIVERTED-TO PAGE)
} // end of dynamic_display()

// wrap HTML in a function to control when and if it's displayed
function a_form($cart)
{
  ob_start();
?>
  <div class="shoppnig_cart">
    <form action="DisplayShoppingCart.php" method="post" accept-charset="utf-8">
<?php
  // Cart Heading
  echo "<span class=\"order_number float-right\">Order Number: $cart->order_number</span>\n";
  echo "    <p class=\"greeting \">Hi " . (Globals::$flag_account_ok ? Globals::$account_obj->name : '')
    . ", this is your Shopping Cart</p>\n";

  // Cart Items
  echo "<p class=\"item_header\">Shopping Cart contains " . sprintf("%d",$cart->line_item_count)
    . " Items</p>\n";
  if ($cart->line_item_count) {
    echo "   <table class=\"items\">\n";
    echo "    <tr>\n";
    echo "      <th></th>\n";
    echo "      <th>Delivery</th>\n";
    echo "      <th>Item</th>\n";
    echo "      <th>Quantity</th>\n";
    echo "      <th>Number</th>\n";
    echo "      <th>Unit Cost</th>\n";
    echo "      <th>Cost</th>\n";
    echo "      <th>Shipped</th>\n";
    echo "      <th>Delivered</th>\n";
    echo "      <th>Remove</th>\n";
    echo "    </tr>\n";
    foreach ($cart->cart_items as $item) {
      $product = new Product(Globals::$dbaccess, $cart->decode_key_values($item->product));
      $deliverable = new Deliverable(Globals::$dbaccess, $cart->decode_key_values($item->deliverable));
      echo "    <tr>\n";
      echo "      <td>$item->line_item_number</td>\n";
      echo "      <td>$item->delivery_method</td>\n";
      echo "      <td>$product->title / $deliverable->title</td>\n";
      echo "      <td><input type=\"text\" name=\"item_{$item->cart_item_key}_\" value=\"{$item->quantity}\"></td>\n";
      echo "      <td>{$item->unit_price}</td>\n";
      echo "      <td>" . sprintf("\$%0.2f", $item->unit_price * $item->quantity) . "</td>\n";
      echo "      <td>$item->shipped</td>\n";
      echo "      <td>$item->delivered</td>\n";
      echo "      <td>"
        . ( $this->shipped == 'Y'
           ? "<form action=\"DisplayShoppingCart.php\" method=\"POST\">"
            . "<input type=\"hidden\" name=\"delete_item\" value=\"cart->cart_item_key\">"
            . "<input type=\"submit\" name=\"submit\" value=\"Remove\">"
            . "</form>"
          : '')
        . "</td>\n";
      echo "    </tr>\n";
    }
    
    // Summary and Totals
    $totals_ar = array(array('Total Item Cost', $cart->total_item_cost),
        array('Handling Charge', $cart->handling),
        array('Shipping Charge', $cart->shipping),
        array('Tax', $cart->tax),
        array('Total Cost', $cart->total_cost)
      );
    foreach ($totals_ar as $row) {
      if ($row[1]) {
        echo "    <tr>\n";
        echo "     <td></td>\n";
        echo "     <td></td>\n";
        echo "     <td>{$row[0]}</td>\n";
        echo "     <td></td>\n";
        echo "     <td></td>\n";
        echo "     <td></td>\n";
        echo "     <td>${$row[1]}</td>\n";
        echo "     <td></td>\n";
        echo "     <td></td>\n";
        echo "    </tr>\n";
      }
    }
    echo "   </table>\n";
  }

  if ($cart->address) {
    echo "<p class=\"ship_to\">Ship Physical Deliveries To:</p>\n";
    echo $cart->address->render();
  }
  
  // Cart Actions
?>
    <p>
<?php if ($cart->line_item_count): ?>
      <input type="submit" name="submit" value="Update Cart">
      <input type="submit" name="submit" value="Pay Now">
<?php endif;  // line_item_count ?>
      <input type="submit" name="submit" value="Continue Shopping">
    </p>
  </form>
  </div>   <!-- shopping_cart -->
<?php
  return ob_get_clean();
}

// wrap action in function
function pay_for_order()
{
  echo "<h1>Pay For Order Stubb!!</h1>\n";
} // end of do_something()

// end function definitions

// initial processing of POST data
if (!isset(Globals::$session_obj->continue_shopping)) {
  Globals::$session_obj->continue_shopping = $_SERVER['REQUEST_URI'];
}
if (Globals::$rc->safe_request_email) {
  $cart = ShoppingCart::get_open_cart_by_email(Globals::$rc->safe_request_email);
} else {
  $cart = ShoppingCart::get_open_cart_by_cookie(Globals::$cookie_track->cookie);
}

// dispatch actions
switch (Globals::$rc->safe_post_submit) {
  case 'Remove':
    foreach ($cart->cart_items as $item) {
      if ($item->cart_item_key == Globals::$rc->safe_post_delete_item) {
        $cart->delete_from_cart($item);
        $cart->update_cart_stats();
        break;
      }
    }
    break;
  case 'Continue Shopping':
    IncludeUtilities::redirect_to(Globals::$session_obj->continue_shopping);
    break;
  case 'Update Cart':
    $cart->update_cart_stats();
    break;
  case 'Pay Now':
    // display do_something() output
    $cart->update_stats();
    pay_for_order();
    break;
  default:
    if (Globals::$rc->safe_post_submit) {
      throw new Exception("Illegal Submit Value: " . Globals::$rc->safe_post_submit);
    }
    break;
}

echo a_form($cart);
?>
