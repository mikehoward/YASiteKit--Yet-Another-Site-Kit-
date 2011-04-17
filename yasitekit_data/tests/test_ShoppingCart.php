<?php
/*

h3. Test Functions

* testReset() - resets test count and error count to zero
* testReport() - prints a two line summary - number of tests and number
of failures.

The following functions print a test result message and increment the counters.

* testTrue(message, value) - print Pass if _value_ is TRUE else Fail followed by
message.
* testFalse(message, value) - prints Pass if _value_ is FALSE, else Fail
* testNoDBError(message, $dbaccess) - prints Pass message if $dbaccess->errorP()
returns TRUE - indicating that the last database operating completed successfully.
Else Fail
* testDBError(message, $dbaccess) - reverses testNoDBError()
* testException(message, $code) - executes _$code_ using eval() inside a try ... catch
construct. Prints Pass if _$code_ generates an exception, otherwise Fail. Couple
of Gotchas:
** $code must be syntactically correct PHP - including semicolons
** $code must NOT include and php escapes (&lt;?php)
** $code must include 'global' directives if you need to access a global variable,
like: "global $dbaccess;$dbaccess->method();"
* testNoException(message, $code) - the reverse of testException(). Same considerations
apply.

Utilities

* test_helper(message, value) - does the actual work for most of the test result functions.
Use if you want to add a test so we keep all the message headers and counters in one place.
* ignore_exception() - an exception handler which does nothing. Useful if you have some
exception handling buried deep enough that a try ... catch ... be able to clean up
any undesired output. If you use it, follow with a _restore_exception_handler()_ as
soon as possible to avoid losing interesting error reports.

*/
set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
global $dbaccess;

echo "$dbaccess\n";

foreach (array(
    'Account',
    'Category',
    'Address', 'CountryCode', 'CurrencyCode',
    'Product', 'Deliverable', 'TaxAuthority',
    'ShoppingCart', 'ShoppingCartItem', 'RMA',
    ) as $object_name) {
  require_once($object_name . ".php");
  $class_instance = AClass::get_class_instance($object_name);
  $class_instance->create_table($dbaccess);
  testNoDBError("Created table for $object_name", $dbaccess);
}


// create admin and mike accounts
$admin_account_obj = new Account($dbaccess, array('userid' => 'admin', 'password' => 'admin', 'name' => 'Admin'));
$admin_account_obj->save();
$mike_account_obj = new Account($dbaccess, array('userid' => 'mike', 'password' => 'mike', 'name' => 'Mike'));
$mike_account_obj->save();

// create some generic objects so we call exercise class functions
$cat_obj = new Category($dbaccess);
$address_obj = new Address($dbaccess);
$country_code_obj = new CountryCode($dbaccess);
$currency_code_obj = new CurrencyCode($dbaccess);
$product_obj = new Product($dbaccess);
$deliverable_obj = new Deliverable($dbaccess);
$tax_authority_obj = new TaxAuthority($dbaccess);
$shopping_cart_obj = new ShoppingCart($dbaccess);
$shopping_cart_item_obj = new ShoppingCartItem($dbaccess);
$rma_obj = new RMA($dbaccess);

// create some products
$product_attributes = array(
  array('name' => 'prod1',
    'product_owner' => $mike_account_obj,
    'title' => 'Product 1',
    'description' => 'Product 1',
    'product_category' => 'product_image',
    'deliverable_category' => 'deliverable_image_free,deliverable_image_licensed',
    'fulfillment_url' => 'http://foo.bar/prod1',
    'available' => 'Y',
    'on_hand' => 12,
    'downloadable' => 'Y',
  ),
  array('name' => 'prod2',
    'product_owner' => $mike_account_obj,
    'title' => 'Product 2',
    'description' => 'Product 2 Desc',
    'product_category' => 'product_image,product_print',
    'deliverable_category' => 'deliverable_image_downloadable,deliverable_print',
    'fulfillment_url' => 'http://foo.bar/prod2',
    'available' => 'Y',
    'on_hand' => 12,
    'downloadable' => 'Y',
  ),
  array('name' => 'prod3',
    'product_owner' => $mike_account_obj,
    'title' => 'Product 3',
    'description' => 'Product 3 Desc',
    'product_category' => 'product_print',
    'deliverable_category' => 'deliverable_print,deliverable_image',
    'fulfillment_url' => 'http://foo.bar/prod3',
    'available' => 'N',
    'on_hand' => 12,
    'downloadable' => 'N',
  ),
);
foreach ($product_attributes as $attr_ar) {
  $p =
    $products[] = new Product($dbaccess, $attr_ar);
  $p->save();
}

// create some deliverables
$deliverable_attributes = array(
  array(
    'name' => 'image_800x600', // 'varchar(40)', 'Deliverable Name'),
    'title' => '800 by 600 Downloadable Image', // 'varchar(255)', 'Title'),
    'deliverable_category' => 'deliverable_image_downloadable', // 'category(deliverable)', 'Deliverable Categories'),
    'unit_price' => '0.50', // 'float', 'Unit Price'),
    'currency_code' => 'USD', // 'link(CurrencyCode.country_name)', 'Currency Code'),
    'downloadable' => 'Y', // 'enum(Y,N)', 'Downloadable'),
    'available' => 'Y', // 'enum(Y,N)', 'Available'),
    'on_hand' => '0', // 'int', 'Units on Hand'),
    'lead_time' => '0', // 'int', 'Lead Time (days)'),
    
    // for fixed dimension downloadables, these are the height and width dimensions
    // for non-downloadables, this is the footprint required within a box
    'width' => '800', // 'float(2)', 'Width (footprint or pixel width)'),
    'height' => '600', // 'float(2)', 'Height (footprint or pixel height)'),

    'length_units' => 'px', // 'enum(in,cm, px)', 'Length Units'),
    'length' => '', // 'float(2)', 'Length (non-downloadables)'),

    'weight' => '', // 'float(2)', 'Weight (physical product only)'),
    'weight_units' => 'no', // 'enum(lb,kg)', 'Weight Units'),

    'paypal_buy_now_buttonid' => 'buy-now-buttonid', // 'varchar(255)', 'PayPal Buy Now Button Id'),
    'paypal_buy_now_websitecode' => 'buy-now-websitecode', // 'text', 'PayPal Buy Now Website Code'),
    'paypal_buy_now_emaillink' => 'buy-now-emaillink', // 'varchar(255)', 'PayPal Buy Now Email Link'),
    'paypal_add_to_cart_buttonid' => 'add-to-cart-buttonid', // 'varchar(255)', 'PayPal Add-to-Cart Button Id'),
    'paypal_add_to_cart_websitecode' => 'add-to-cart-websitecode', // 'text', 'PayPal Add-to-Cart Website Code'),
    'paypal_add_to_cart_emaillink' => 'add-to-cart-emaillink', // 'varchar(255)', 'PayPal Add to Cart Email Link'),
  ),
  array(
    'name' => 'image_1024x768', // 'varchar(40)', 'Deliverable Name'),
    'title' => '1024 by 768 Downloadble Image', // 'varchar(255)', 'Title'),
    'deliverable_category' => 'deliverable_image_free', // 'category(deliverable)', 'Deliverable Categories'),
    'unit_price' => '1.98', // 'float', 'Unit Price'),
    'currency_code' => 'USD', // 'link(CurrencyCode.country_name)', 'Currency Code'),
    'downloadable' => 'Y', // 'enum(Y,N)', 'Downloadable'),
    'available' => 'Y', // 'enum(Y,N)', 'Available'),
    'on_hand' => '0', // 'int', 'Units on Hand'),
    'lead_time' => '0', // 'int', 'Lead Time (days)'),
    
    // for fixed dimension downloadables, these are the height and width dimensions
    // for non-downloadables, this is the footprint required within a box
    'width' => '1024', // 'float(2)', 'Width (footprint or pixel width)'),
    'height' => '768', // 'float(2)', 'Height (footprint or pixel height)'),

    'length_units' => 'px', // 'enum(in,cm, px)', 'Length Units'),
    'length' => '', // 'float(2)', 'Length (non-downloadables)'),

    'weight' => '', // 'float(2)', 'Weight (physical product only)'),
    'weight_units' => 'no', // 'enum(lb,kg)', 'Weight Units'),

    'paypal_buy_now_buttonid' => 'buy-now-buttonid', // 'varchar(255)', 'PayPal Buy Now Button Id'),
    'paypal_buy_now_websitecode' => 'buy-now-websitecode', // 'text', 'PayPal Buy Now Website Code'),
    'paypal_buy_now_emaillink' => 'buy-now-emaillink', // 'varchar(255)', 'PayPal Buy Now Email Link'),
    'paypal_add_to_cart_buttonid' => 'add-to-cart-buttonid', // 'varchar(255)', 'PayPal Add-to-Cart Button Id'),
    'paypal_add_to_cart_websitecode' => 'add-to-cart-websitecode', // 'text', 'PayPal Add-to-Cart Website Code'),
    'paypal_add_to_cart_emaillink' => 'add-to-cart-emaillink', // 'varchar(255)', 'PayPal Add to Cart Email Link'),
  ),
  array(
    'name' => 'print_8x10', // 'varchar(40)', 'Deliverable Name'),
    'title' => '8 by 10 Print', // 'varchar(255)', 'Title'),
    'deliverable_category' => 'deliverable_print_framed', // 'category(deliverable)', 'Deliverable Categories'),
    'unit_price' => '14.95', // 'float', 'Unit Price'),
    'currency_code' => 'USD', // 'link(CurrencyCode.country_name)', 'Currency Code'),
    'downloadable' => 'N', // 'enum(Y,N)', 'Downloadable'),
    'available' => 'Y', // 'enum(Y,N)', 'Available'),
    'on_hand' => '12', // 'int', 'Units on Hand'),
    'lead_time' => '3', // 'int', 'Lead Time (days)'),
    
    // for fixed dimension downloadables, these are the height and width dimensions
    // for non-downloadables, this is the footprint required within a box
    'width' => '10', // 'float(2)', 'Width (footprint or pixel width)'),
    'height' => '12', // 'float(2)', 'Height (footprint or pixel height)'),

    'length_units' => 'in', // 'enum(in,cm, px)', 'Length Units'),
    'length' => '1', // 'float(2)', 'Length (non-downloadables)'),

    'weight' => '1', // 'float(2)', 'Weight (physical product only)'),
    'weight_units' => 'lb', // 'enum(lb,kg)', 'Weight Units'),

    'paypal_buy_now_buttonid' => 'buy-now-buttonid', // 'varchar(255)', 'PayPal Buy Now Button Id'),
    'paypal_buy_now_websitecode' => 'buy-now-websitecode', // 'text', 'PayPal Buy Now Website Code'),
    'paypal_buy_now_emaillink' => 'buy-now-emaillink', // 'varchar(255)', 'PayPal Buy Now Email Link'),
    'paypal_add_to_cart_buttonid' => 'add-to-cart-buttonid', // 'varchar(255)', 'PayPal Add-to-Cart Button Id'),
    'paypal_add_to_cart_websitecode' => 'add-to-cart-websitecode', // 'text', 'PayPal Add-to-Cart Website Code'),
    'paypal_add_to_cart_emaillink' => 'add-to-cart-emaillink', // 'varchar(255)', 'PayPal Add to Cart Email Link'),
  ),
  array(
    'name' => 'licensed_image', // 'varchar(40)', 'Deliverable Name'),
    'title' => 'Licenseable Image', // 'varchar(255)', 'Title'),
    'deliverable_category' => 'deliverable_image_licensed', // 'category(deliverable)', 'Deliverable Categories'),
    'unit_price' => '194.95', // 'float', 'Unit Price'),
    'currency_code' => 'USD', // 'link(CurrencyCode.country_name)', 'Currency Code'),
    'downloadable' => 'Y', // 'enum(Y,N)', 'Downloadable'),
    'available' => 'Y', // 'enum(Y,N)', 'Available'),
    'on_hand' => '0', // 'int', 'Units on Hand'),
    'lead_time' => '0', // 'int', 'Lead Time (days)'),
    
    // for fixed dimension downloadables, these are the height and width dimensions
    // for non-downloadables, this is the footprint required within a box
    'width' => '0', // 'float(2)', 'Width (footprint or pixel width)'),
    'height' => '0', // 'float(2)', 'Height (footprint or pixel height)'),

    'length_units' => 'px', // 'enum(in,cm, px)', 'Length Units'),
    'length' => '', // 'float(2)', 'Length (non-downloadables)'),

    'weight' => '', // 'float(2)', 'Weight (physical product only)'),
    'weight_units' => 'no', // 'enum(lb,kg)', 'Weight Units'),

    'paypal_buy_now_buttonid' => 'buy-now-buttonid', // 'varchar(255)', 'PayPal Buy Now Button Id'),
    'paypal_buy_now_websitecode' => 'buy-now-websitecode', // 'text', 'PayPal Buy Now Website Code'),
    'paypal_buy_now_emaillink' => 'buy-now-emaillink', // 'varchar(255)', 'PayPal Buy Now Email Link'),
    'paypal_add_to_cart_buttonid' => 'add-to-cart-buttonid', // 'varchar(255)', 'PayPal Add-to-Cart Button Id'),
    'paypal_add_to_cart_websitecode' => 'add-to-cart-websitecode', // 'text', 'PayPal Add-to-Cart Website Code'),
    'paypal_add_to_cart_emaillink' => 'add-to-cart-emaillink', // 'varchar(255)', 'PayPal Add to Cart Email Link'),
  ),
);

$idx = 0;
foreach ($deliverable_attributes as $attr) {
  $d =
    $deliverables[] = new Deliverable($dbaccess, $attr);
  $d->save();
  // echo "$idx: $d->name: $d->deliverable_category\n";
  $idx += 1;
}

// display categories

// display product and deliverables by category
$deliverables_test_ar = array(
  'prod1' => array('deliverable_image_free' => array($deliverables[1]),
    'deliverable_image_licensed' => array($deliverables[3])),
  'prod2' => array('deliverable_print' => array($deliverables[2]),
      'deliverable_image_downloadable' => array($deliverables[0]),),
  'prod3' => array('deliverable_image' => array($deliverables[0],$deliverables[1],$deliverables[3]),
      'deliverable_print' => array($deliverables[2]),),
);

function print_names($ar)
{
  return implode(', ', array_map(create_function('$d', 'return $d->name;'), $ar));
} // end of print_names()

// returns TRUE if both arrays have the same values - irrespective or order
function arrays_equal($a, $b)
{
  $ar = array_diff($a, $b) + array_diff($b, $a);
  return $ar == array() ? TRUE : FALSE;
} // end of compare_arrays()

// test array_equal()
// testTRUE('arrays_equal 1', arrays_equal(array(1,2), array(1,2)));
// testTRUE('arrays_equal 1', arrays_equal(array(1,2), array(2,1)));

echo "Testing Product::deliverables() method\n";
foreach ($products as $p) {
  foreach ($p->category_paths_of('deliverable_category') as $cat) {
    testTRUE("checking deliverables in $cat",
        arrays_equal($deliverables_test_ar[$p->name][$cat], $p->deliverables($cat)));
  }
}

$shopping_cart = new ShoppingCart($dbaccess);
$shopping_cart->email = 'joe@email.baz';
$shopping_cart->cookie_value = 'This is a Value';
$shopping_cart->save();

testTrue('open date is now', $shopping_cart->open_date == new DateTime());
testTrue('commit_date is empty', $shopping_cart->commit_date == '');
testTrue('production_start_date is empty', $shopping_cart->production_start_date == '');
testTrue('final_ship_date is empty', $shopping_cart->final_ship_date == '');
testTrue('final_receipt_date is empty', $shopping_cart->final_receipt_date == '');
testTrue('rma_close_date is empty', $shopping_cart->rma_close_date == '');
// echo $shopping_cart->dump();
$foo = new ShoppingCart($dbaccess, $shopping_cart->order_number);
echo "\nchecking retrieved, empty cart\n";
testTrue('order_number matches', $shopping_cart->order_number == $foo->order_number);
testTrue('cookie_value matches', $shopping_cart->cookie_value == $foo->cookie_value );
testTrue('email matches', $shopping_cart->email == $foo->email );
testTrue('state matches', $shopping_cart->state == $foo->state );
testTrue('rma matches', $shopping_cart->rma == $foo->rma );
testTrue('address_id matches', $shopping_cart->address_id == $foo->address_id );
testTrue('line_item_count matches', $shopping_cart->line_item_count == $foo->line_item_count );
testTrue('item_count matches', $shopping_cart->item_count == $foo->item_count );
testTrue('payment_method matches', $shopping_cart->payment_method == $foo->payment_method );
testTrue('total_item_cost matches', $shopping_cart->total_item_cost == $foo->total_item_cost );
testTrue('handling matches', $shopping_cart->handling == $foo->handling );
testTrue('shipping matches', $shopping_cart->shipping == $foo->shipping );
testTrue('tax matches', $shopping_cart->tax == $foo->tax );
testTrue('total_cost matches', $shopping_cart->total_cost == $foo->total_cost );
testTrue('open_date matches', $shopping_cart->open_date == $foo->open_date );
testTrue('commit_date matches', $shopping_cart->commit_date == $foo->commit_date );
testTrue('production_start_date matches', $shopping_cart->production_start_date == $foo->production_start_date );
testTrue('final_ship_date matches', $shopping_cart->final_ship_date == $foo->final_ship_date );
testTrue('final_receipt_date matches', $shopping_cart->final_receipt_date == $foo->final_receipt_date );
testTrue('rma_close_date matches', $shopping_cart->rma_close_date == $foo->rma_close_date );

$shopping_cart_items[] = $shopping_cart->add_to_cart($products[0], $deliverables[1], 12);
$shopping_cart_items[] = $shopping_cart->add_to_cart($products[1], $deliverables[2], 1);
$shopping_cart_items[] = $shopping_cart->add_to_cart($products[1], $deliverables[0], 1);
// echo $shopping_cart->dump('After Additions');
foreach ($shopping_cart->cart_items as $item) {
  testTrue("$item is a ShoppingCartItem", $item instanceof ShoppingCartItem);
}
$shopping_cart->save();
// echo $shopping_cart->dump("\n\n");

$foo = new ShoppingCart($dbaccess, $shopping_cart->order_number);
echo "\nchecking retrieved, filled cart\n";
// echo $foo->dump("\n\nRetreived Cart");

testTrue('order_number matches', $shopping_cart->order_number == $foo->order_number);
testTrue('cookie_value matches', $shopping_cart->cookie_value == $foo->cookie_value );
testTrue('email matches', $shopping_cart->email == $foo->email );
testTrue('state matches', $shopping_cart->state == $foo->state );
testTrue('rma matches', $shopping_cart->rma == $foo->rma );
testTrue('address_id matches', $shopping_cart->address_id == $foo->address_id );
testTrue('line_item_count matches', $shopping_cart->line_item_count == $foo->line_item_count );
testTrue('item_count matches', $shopping_cart->item_count == $foo->item_count );
testTrue('payment_method matches', $shopping_cart->payment_method == $foo->payment_method );
testTrue('total_item_cost matches', $shopping_cart->total_item_cost == $foo->total_item_cost );
testTrue('handling matches', $shopping_cart->handling == $foo->handling );
testTrue('shipping matches', $shopping_cart->shipping == $foo->shipping );
testTrue('tax matches', $shopping_cart->tax == $foo->tax );
testTrue('total_cost matches', $shopping_cart->total_cost == $foo->total_cost );
testTrue('open_date matches', $shopping_cart->open_date == $foo->open_date );
testTrue('commit_date matches', $shopping_cart->commit_date == $foo->commit_date );
testTrue('production_start_date matches', $shopping_cart->production_start_date == $foo->production_start_date );
testTrue('final_ship_date matches', $shopping_cart->final_ship_date == $foo->final_ship_date );
testTrue('final_receipt_date matches', $shopping_cart->final_receipt_date == $foo->final_receipt_date );
testTrue('rma_close_date matches', $shopping_cart->rma_close_date == $foo->rma_close_date );

testTrue('3 line items', $foo->line_item_count == 3);
testTrue('line count matches cart item count', count($foo->cart_items) == $foo->line_item_count);
testTrue('14 items', $foo->item_count == 14);

echo "\nTesting get_cart class methods;\n";
$bar = ShoppingCart::get_carts_by_cookie($foo->cookie_value, $dbaccess);
testTrue('get_carts_by_cookie works', $foo->equal($bar[0]));
$bar = ShoppingCart::get_carts_by_email($foo->email, $dbaccess);
testTrue('get_carts_by_email works', $foo->equal($bar[0]));

testTrue('get_open_cart_by_cookie works', $foo->equal(ShoppingCart::get_open_cart_by_cookie($foo->cookie_value, $dbaccess)));
testTrue('get_open_cart_by_emails works', $foo->equal(ShoppingCart::get_open_cart_by_email($foo->email, $dbaccess)));


echo "\nTesting Shopping Cart date setting methods\n";
$now = new DateTime();
$date_today = new DateTime($now->format('Y-m-d'));
foreach (array('commit_date', 'production_start_date', 'final_ship_date', 'final_receipt_date') as $date_name) {
  $func = "set_{$date_name}";
  $foo->$func();
  testTrue("$date_name set to now", $foo->$date_name == $date_today);
}
$foo->set_rma_close_date(12);
$now_plus_12 = $date_today;
$now_plus_12->modify('+12 day');
testTrue('rma_close_date set to now + 12 days', $foo->rma_close_date == $now_plus_12);

echo "\nDeleting middle item and verifying it is gone\n";
$item_to_delete = $shopping_cart_items[1];
// echo $item_to_delete->dump('item to delete');
$shopping_cart->delete_from_cart($item_to_delete);
testTrue('item count is now 2', count($shopping_cart->cart_items) == 2);
testTrue('item count matches data', count($shopping_cart->cart_items) == $shopping_cart->line_item_count);
$tmp_ar = array_map(create_function('$x', 'return $x->cart_item_key;'), $shopping_cart->cart_items);
testTrue('first item OK', in_array($shopping_cart_items[0]->cart_item_key, $tmp_ar));
testTrue('last item OK', in_array($shopping_cart_items[2]->cart_item_key, $tmp_ar));
testTrue('item_count OK', $shopping_cart->item_count == array_reduce($shopping_cart->cart_items,
      create_function('$a,$b', 'return $a+$b->quantity;')));
testTrue('total_item_cost OK', $shopping_cart->total_item_cost == round(array_reduce($shopping_cart->cart_items,
      create_function('$a,$b', 'return $a+$b->quantity*$b->unit_price;')), 2));

// echo $shopping_cart->dump('After deleting item 1');


testReport();
