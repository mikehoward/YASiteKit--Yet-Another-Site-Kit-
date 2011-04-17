<?php
/*
#doc-start
h1. ShoppingCartItem.php - Shopping Cart Item Container

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

A *ShoppingCartItem* is a container for an item. It coordinates a
product and deliverable with a specific shopping cart.

h2. Instantiation

ShoppingCartItems are not instantiated directly. They are instantiated
by the ShoppingCart method: "_add_to_cart()_":/doc.d/system-objects/ShoppingCart.html

h2. Attributes

* cart_item_key - int - automatically generated key
* order_number - join(ShoppingCart.order_number) - links to ShoppingCart instance
* line_item_number - int - ordinal number in shopping cart
* product - join(Product.title) - link to a Product
* deliverable - join(Deliverable.title) - link to a Deliverable

* item_info - returns the string "product->title / deliverable-> title"

* quantity - int - number of Deliverables ordered
* unit_price - float - price per Deliverable
* total_price - float - unit_price * quantity

* delivery_method - enum(Download,Physical) - Delivery method

* shipped - enum(N,Y) -  Shipped Flag
* delivered - enum(N,Y) - Delivered Flag

h2 Class Methods

None

h2. Instance Methods

None

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('ShoppingCartItem', 'cart_item_key', 
  array( // field definitions
    array('cart_item_key', 'int', 'Key'),
    array('order_number', 'link(ShoppingCart.order_number)', 'Order Number'),
    array('line_item_number', 'int', 'Line Item Number'),
    array('product', 'link(Product.title)', 'Product'),
    array('deliverable', 'link(Deliverable.title)', 'Deliverable'),
    
    array('quantity', 'int', 'Quantity'),
    array('unit_price', 'float', 'Unit Price'),
    array('total_price', 'float', 'Total Price'),
    
    array('delivery_method', 'enum(Download,Physical)', 'Delivery Method'),

    array('shipped', 'enum(N,Y)', 'Shipped Flag'),
    array('delivered', 'enum(N,Y)', 'Delivered Flag'),
  ),
  array(// attribute definitions
    'cart_item_key' => 'readonly',
    'total_price' => 'readonly',
    'line_item_number' => 'readonly',
      ));
// end global variables

// class definitions
class ShoppingCartItem extends AnInstance {
  static private $parameters = NULL;
  public function __construct($dbaccess, $attribute_values = array())
  {
    if (!ShoppingCartItem::$parameters) {
      require_once('Parameters.php');
      ShoppingCartItem::$parameters = new Parameters($dbaccess, 'ShoppingCartItem');
      if (!isset(ShoppingCartItem::$parameters->next_cart_item_key)) {
        ShoppingCartItem::$parameters->next_cart_item_key = 1;
      }
    }
    parent::__construct('ShoppingCartItem', $dbaccess, $attribute_values);
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'item_info':
        return $this->link_value_of('product') . ' / ' . $this->link_value_of('deliverable');
      default:
        return parent::__get($name);
    }
  } // end of __get()
  
  public function save()
  {
    if (!$this->cart_item_key) {
      $this->cart_item_key = ShoppingCartItem::$parameters->next_cart_item_key;
      ShoppingCartItem::$parameters->next_cart_item_key += 1;
    }
    parent::save();
  } // end of save()
}


class ShoppingCartItemManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'ShoppingCartItem', 'order_number,line_item_number,product');
  } // end of __construct()
}
?>
