<?php
/*
#doc-start
h1.  ProductOrder.php - Accounting record of a product order

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/
// global variables
require_once('aclass.php');

$keys_list = array('order_number',);
$attribute_defs = array(
  array('order_number', 'varchar(40)', 'Order Number'),
  array('email', 'email', 'Email Address'),
  array('product_name', 'varchar(255)', 'Product Name'),
  array('buyer_name', 'varchar(80)', 'Buyer\' Name'),
  array('shipping_address', 'text', 'Shipping Address'),
  array('payment_method', 'varchar(255)', 'Payment Method'),
  array('invoice', 'text', 'Text of Invoice'),
  array('purchase_date', 'date', 'Purchase Date'),
  array('ship_date', 'date', 'Ship Date'),
  array('receive_date', 'date', 'Receipt Date'),
  );
$test_product_order_values = array(
  'order_number' => 'ABC-12345',
  'email' => 'email@address',
  'product_name' => 'Product Name',
  'buyer_name' => 'Buyer Name',
  'shipping_address' => 'Shipping Address',
  "payment_method" => 'Payment Method',
  'invoice' => 'Invoice Text',
  'purchase_date' => '2010-2-1',
  'ship_date' => '2010-2-14',
  'receive_date' => '2010-2-21',
  );

AClass::define_class('ProductOrder', $keys_list, $attribute_defs,
    array('buyer_name' => 'encrypt', 'email' => 'encrypt',
    'shipping_address' => 'encrypt', 'payment_method' => 'encrypt'));
// end global variables

// class definitions
class ProductOrder extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('ProductOrder', $dbaccess, $attribute_values);
  } // end of __construct()
}

class ProductOrderManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'ProductOrder', 'order_number');
  } // end of __construct()
}
// end class definitions
?>
