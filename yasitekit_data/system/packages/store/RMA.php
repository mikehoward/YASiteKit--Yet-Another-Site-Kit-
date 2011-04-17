<?php
/*
#doc-start
h1. Rma.php - RMA tracking for returned items

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


#end-doc
*/

// global variables
require_once('aclass.php');

$test_rma_values = array(
  'rma_number' => 'RMA012345',
  'order_number' => 'ABC-12345',
  'email' => 'email@address',
  'product_name' => 'Product Name',
  'buyer_name' => 'Buyer Name',
  'purchase_date' => '2010-2-1',
  'rma_date' => '2010-2-14',
  'receive_date' => '2010-2-21',
  'received_condition' => 'B',
  'notes' => 'Notes on RMA',
  'received_photo' => 'path-to-photo',
  );
AClass::define_class('RMA', 'rma_number', 
  array( // field definitions
    array('rma_number', 'varchar(40)', 'RMA Number'),
    array('order_number', 'varchar(40)', 'Order Number'),
    array('email', 'email', 'Email Address'),
    array('product_name', 'varchar(255)', 'Product Name'),
    array('buyer_name', 'varchar(80)', 'Buyer\' Name'),
    array('purchase_date', 'date', 'Purchase Date'),
    array('rma_date', 'date', 'RMA Approval Date'),
    array('receive_date', 'date', 'Local Receipt Date'),
    array('received_condition', 'enum(A,B,C,F)', 'Received Condition - A=Perfect,B=Damaged Frame,C=Damaged Matte,F=Damaged Print'),
    array('notes', 'text', 'Notes'),
    array('received_photo', 'file(images/rma_photos/{rma_number},private)', 'Received Condition Photo')
  ),
  array(// attribute definitions
    'email' => 'encrypt',
    'buyer_name' => 'encrypt',
    'notes' => 'encrypt'));
// end global variables

// class definitions
class RMA extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('RMA', $dbaccess, $attribute_values);
  } // end of __construct()
}


class RMAManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'RMA', 'rma_number');
  } // end of __construct()
}
?>
