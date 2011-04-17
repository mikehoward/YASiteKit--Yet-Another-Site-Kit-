<?php
/*
#doc-start
h1.  ShoppingCart.php - Sketch of a Shopping Cart object - unfinished

Created by  on 2010-04-05.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*ShoppingCart* provides - surprisingly enough - a shopping cart structure.

Each _cart_ is essentially a centralized object which can have zero or more
*ShoppingCartItem* objects and 0 or 1 physical address.

Carts have a _state_ attribute which encodes their process state:

* Open - for newly openned carts - only one cart may be Open for a given email
address. Multiple carts may be Open for the same cookie.
* Confirmed - for carts which are closed and are ready to be purchsed
* Ordered - carts which have been paid for
* Shipped - carts for which all items have been shipped
* Closed - carts for which the RMA period has expired

They also maintain a collection of dates associated with these state transitions.
See attributes below for details.

Shopping carts may be retrieved by _order_number_, _email_, or _cookie_ value.

h2. Instantiation

h2. Attributes

Lots of attributes. We've broken them down by use to make this document a little
easier to digest.

h3. Infrastructure Data

* order_number - string - Order number consist of the leading 5 characters of the site
id, followed by a single hyphen, followed by a 12 digit integer.
* cookie_value - string - The cookie value of the current user OR NULL.
* email - string - email address of buyer
* state - enum - Open,Confirmed,Ordered,Shipped,Closed
* rma - string - comma separated list of Rma numbers

h3. Physical Address

* address_id - int - key to Address. 0 if no physical deliveries are required

h3. Order Details and Summaries

* line_item_count - int - Number of ShoppingCartItem objects in the cart
* item_count - int - total number of deliverables (sum of quatities of each line item)
* payment_method - enum - Paypal,CreditCard,Cash,Account
* total_item_cost - float - total cost of all deliverables
* handling - float - handling charge
* shipping - float - shipping charge
* tax - float - total tax to be collected
* total_cost - float - sum of total_item cost + handling + shipping + tax

h3.  Dates

* open_date - datetime - timestamp when cart was created
* commit_date - datetime - timestamp when cart payment was made and accepted
* production_start_date - datetime - timestamp when production started
* final_ship_date - datetime - timestamp when last shipment was made
* final_receipt_date - datetime - timestamp when final deliverable was delivered
* rma_close_date - datetime - timestamp when RMA period expires


h2. Class Methods

These methods return a list of all carts which satisfy a criterion:

* get_carts_by_where($where_ar, $dbaccess) - returns an array of ShoppingCart objects
which satisfy the array $where_ar
* get_carts_by_cookie($cookie, $dbaccess = NULL) - returns an array of ShoppingCart objects
which share the same _cookie_ value
* get_carts_by_email($email, $dbaccess = NULL) - returns an array of ShoppingCart objects
which share the same _email_ address

These methods return the unique Open cart which satisfies the criterion OR and empty
cart:

* get_open_cart_by_where($where_ar, $dbaccess) - returns an open ShoppingCart object
which satisfy the array $where_ar. If none exist, then one will be created.
* get_open_cart_by_cookie($cookie, $dbaccess = NULL) - returns an open ShoppingCart object
which has the _cookie_ value. If none exist, then one will be created.
* get_open_cart_by_email($email, $dbaccess = NULL) - returns an open ShoppingCart object
which has _email_ address. If none exist, then one will be created.

h2. Instance Methods

* add_to_cart($product, deliverable, $quantity) - creates a ShoppingCartItem and
adds to cart. Returns the new ShoppingCartItem created.
* delete_from_cart($shopping_cart_item) - deletes _$shopping_cart_item_ from cart
* update_cart_stats() - computes various totals and re-numbers line items.
* render_cart() - returns a string containing the cart contents and statistics
* form($top_half = NULL, $bottom_half = '', $actions = NULL) - returns a string
containing a _form_ element for the cart
* save() - saves the cart, address (if required) and all cart items.

#end-doc
*/

// global variables
require_once('aclass.php');
ObjectInfo::do_require_once('ShoppingCartItem.php');

// end global variables

// class definitions

$shopping_carts_class = AClass::define_class('ShoppingCart',
  // Key definition(s)
  'order_number',
  // attribute definitions
  array(
    array('order_number', 'char(18)', 'Order Number'),  // first five are site_id upper-case, padded with '-', a '-', and then decimal number
    array('cookie_value', 'varchar(255)', 'Cookie'),
    array('email', 'email', 'Email Address'),
    array('state', 'enum(Open,Confirmed,Ordered,Shipped,Closed)', 'Open,Confirmed,Ordered,Shipped,Closed'),
    array('rma', 'varchar(255)', 'RMA Number(s)'),
    
    // physical address
    array('address_id', 'int', 'Address Id'),

    // order details
    array('cart_items', 'join(ShoppingCartItem.item_info)', 'Cart Items'),
    array('line_item_count', 'int', 'Line Item Count'),
    array('item_count', 'int', 'Total Number Items'),
    array('payment_method', 'enum(Paypal,CreditCard,Cash,Account)', 'Payment Method'),
    array('total_item_cost', 'float(2)', 'Total Item Cost'),
    array('handling', 'float(2)', 'Handling Cost'),
    array('shipping', 'float(2)', 'Shipping Cost'),
    array('tax', 'float(2)', 'Tax'),
    array('total_cost', 'float(2)', 'Total Cost'),
    
    // RMA handling

    // dates
    array('open_date', 'date', 'Open Date'),
    array('commit_date', 'date', 'Commit Date'),
    array('production_start_date', 'date', 'Production Start Date'),
    array('final_ship_date', 'date', 'Final Ship Date'),
    array('final_receipt_date', 'date', 'Final Receipt Date'),
    array('rma_close_date', 'date', 'Last RMA Date'),
    ),
  // properties
    array(
      'cookie_value' => array('invisible', 'readonly'),
      'email' => 'required',
      'line_item_count' => array('readonly', 'default' => 0),
      'item_count' => array('readonly', 'default' => 0),
      'total_item_cost' => 'readonly',
      'total_cost' => 'readonly',
      'handling' => 'readonly',
      'shipping' => 'readonly',
      'tax' => 'readonly',
      'state' => 'readonly',
      'address_id' => array('default' => 0, 'invisible'),
      'open_date' => 'readonly',
      'commit_date' => 'readonly',
      'production_start_date' => 'readonly',
      'final_ship_date' => 'readonly',
      'final_receipt_date' => 'readonly',
      'rma_close_date' => 'readonly',
      ));
// end global variables

// class definitions
class ShoppingCart extends AnInstance {
  private static $parameters = NULL;
  private $address;
  public function __construct($dbaccess, $attribute_values = NULL)
  {
    if (!ShoppingCart::$parameters) {
      require_once('Parameters.php');
      ShoppingCart::$parameters = new Parameters($dbaccess, 'ShoppingCart');
      if (!isset(ShoppingCart::$parameters->next_order_number)) {
        ShoppingCart::$parameters->next_order_number = 1;
      }
    }
    parent::__construct('ShoppingCart', $dbaccess, $attribute_values);
  } // end of __construct()
  
  // Public Class Methods
  public static function get_open_cart_by_where($where_ar, $dbaccess = NULL)
  {
    $obj = new ShoppingCart($dbaccess ? $dbaccess : Globals::$dbaccess);
    $where_ar['state'] = 'Open';
    if (($list = $obj->get_objects_where($where_ar))) {
      return $list[0];
    } else {
      $obj->initialize_cart();
      foreach ($where_ar as $key => $value) {
        $obj->$key = $value;
      }
      return $obj;
    }
  } // end of get_open_cart_by_where()

  public static function get_carts_by_where($where_ar, $dbaccess = NULL)
  {
    $obj = new ShoppingCart($dbaccess ? $dbaccess : Globals::$dbaccess);
    return $obj->get_objects_where($where_ar);
  } // end of get_carts_by_where()
 
  public static function get_carts_by_cookie($cookie = NULL, $dbaccess = NULL)
  {
    if (!$cookie) {
      $cookie = Globals::$cookie_track->cookie;
    }
    return ShoppingCart::get_carts_by_where(array('cookie_value' => $cookie), $dbaccess);
  } // end of get_cart_by_cookie()
  
  public static function get_open_cart_by_cookie($cookie = NULL, $dbaccess = NULL)
  {
   if (!$cookie) {
     $cookie = Globals::$cookie_track->cookie;
   }
   return ShoppingCart::get_open_cart_by_where(array('cookie_value' => $cookie), $dbaccess);
  } // end of get_cart_by_cookie()

  public static function get_carts_by_email($email, $dbaccess = NULL)
  {
    if (!$email) {
      return FALSE;
    }
    return ShoppingCart::get_carts_by_where(array('email' => $email), $dbaccess);
  } // end of get_carts_by_email()
 
  public static function get_open_cart_by_email($email, $dbaccess = NULL)
  {
    if (!$email) {
      return FALSE;
    }
    return ShoppingCart::get_open_cart_by_where(array('email' => $email), $dbaccess);
  } // end of get_carts_by_email()
  // Magic methods
  public function __toString()
  {
    return $this->order_number;
  } // end of __toString()
  
  public function __get($name)
  {
    switch ($name) {
      case 'address':
        if ($this->address_id && !$this->address) {
          $this->address = new Address($this->dbaccess, $this->address_id);
        }
        return $this->address;
      default:
        return parent::__get($name);
    }
  } // end of __get()
  
  // Private Methods
  // This is a Hack to allow empty cart's to be created and discarded w/o creating database entries
  //  or incrementing the next_order_number parameter
  private function initialize_cart()
  {
    if ($this->order_number) {
      return;
    }
    
    if (!$this->order_number) {
      $this->order_number = $this->next_order_number();
      $this->state = 'Open';
      $this->open_date = new DateTime('now');
    }
    if ($this->address_id) {
      $this->address = new Address($dbaccess, $this->address_id);
    }
  } // end of initialize_cart()

  // order number generation - override as necessary
  private function next_order_number()
  {
    $ord =  sprintf("%5s-%012d", strtoupper(substr(Globals::$site_id, 0, 5)),
      ShoppingCart::$parameters->next_order_number);
    ShoppingCart::$parameters->next_order_number += 1;
    return $ord;
  } // end of next_order_number()
  
 
  // Instance Methods
  
  // date setting
  private function date_today($offset = 0)
  {
    $now = new DateTime();
    if ($offset) {
      $now->modify("+$offset day");
    }
    return $now->format('Y-m-d');
  } // end of date_today()
  
  public function set_commit_date()
  {
    $this->commit_date = $this->date_today();
    $this->save();
  } // end of set_commit_date()
  
  public function set_production_start_date()
  {
    $this->production_start_date = $this->date_today();
    $this->save();
  } // end of set_commit_date()
  
  public function set_final_ship_date()
  {
    $this->final_ship_date = $this->date_today();
    $this->save();
  } // end of set_commit_date()
  
  public function set_final_receipt_date()
  {
    $this->final_receipt_date = $this->date_today();
    $this->save();
  } // end of set_commit_date()
  
  public function set_rma_close_date($offset)
  {
    $this->rma_close_date = $this->date_today($offset);
    $this->save();
  } // end of set_commit_date()
  
  public function update_cart_stats()
  {
    $this->initialize_cart();
    foreach ($this->cart_items as $cart_item) {
      if ($cart_item->delivery_method == 'Physical' && !$this->address_id) {
        $this->address = new Address($this->dbaccess);
        break;
      }
    }
  } // end of update_cart_stats()
  
  public function add_to_cart($product, $deliverable, $quantity)
  {
    $this->initialize_cart();
    $new_cart_item = new ShoppingCartItem($this->dbaccess);
    $new_cart_item->order_number = $this->order_number;
    $new_cart_item->line_item_number = $this->item_count;
    $new_cart_item->product = $product;
    $new_cart_item->deliverable = $deliverable;
    $new_cart_item->quantity = $quantity;
    $new_cart_item->unit_price = $deliverable->unit_price;
    $new_cart_item->total_price = $quantity * $deliverable->unit_price;
    $new_cart_item->delivery_method = $deliverable->downloadable == 'Y' ? 'Download' : 'Physical';
    $new_cart_item->save();

    $this->add_to_join('cart_items', $new_cart_item);
    $this->line_item_count += 1;
    $this->item_count += $new_cart_item->quantity;
    $this->total_item_cost += $new_cart_item->total_price;
    $this->save();
    
    return $new_cart_item;
  } // end of add_to_cart()
  
  public function delete_from_cart($shopping_cart_item)
  {
    $this->initialize_cart();
    $this->line_item_count -= 1;
    $this->item_count -= $shopping_cart_item->quantity;
    $this->total_item_cost -= $shopping_cart_item->total_price;
    $this->delete_from_join('cart_items', $shopping_cart_item);
    $this->save();

    // finally delete item
    $shopping_cart_item->delete();
  } // end of delete_from_cart()

/*
#begin-doc
* adjust_cart($shopping_cart_item) - updates shopping cart for changes in
an item;
#end-doc
*/


  public function adjust_cart($shopping_cart_item)
  {
    foreach ($this->cart_items as $item) {
      if ($shopping_cart_item->cart_item_key == $item->cart_item_key) {
        if ($shopping_cart_item->quantity != $item->quantity) {
          $this->delete_from_cart($item);
          if ($shopping_cart_item->quantity > 0) {
            $this->add_to_cart($shopping_cart_item);
          }
        }
        return TRUE;
      }
    }
    return FALSE;
  } // end of adjust_cart()
  
  public function render_cart()
  {
    $this->initialize_cart();
    $str = "<h1 class=\"shopping_cart\">Shopping Cart: {$this->order_number} - for $this->email [$this->state]</h1>\n";
    
    $str .= "<ul class=\"shopping_cart\">\n";
    $str .= "<li>Cart contains $this->item_count items</li>\n";
      // 
      // // RMA handling
      // 
      // // dates
      // array('open_date', 'date', 'Open Date'),
      // array('commit_date', 'date', 'Commit Date'),
      // array('production_start_date', 'date', 'Production Start Date'),
      // array('final_ship_date', 'date', 'Final Ship Date'),
      // array('final_receipt_date', 'date', 'Final Receipt Date'),
      // array('rma_close_date', 'date', 'Last RMA Date'),
      
    if (($tmp_ar = $this->cart_items)) {
      $str .= "<li>Items:\n";
      usort($tmp_ar, new AClassCmp('line_item_number'));
      foreach ($tmp_ar as $cart_item) {
        $str .= $cart_item->render();
      }
      $str .= " </li>\n";
    } else {
      $str .= "<li>Cart is Empty</li>\n";
    }

    $str .= "<li>Payment Method Chosen: $this->payment_method</li>\n";
    $str .= "<li>Total Item Cost: $this->total_item_cost</li>\n";
    $str .= "<li>Handling Charge: $this->handling</li>\n";
    $str .= "<li>Shipping Charge: $this->shipping</li>\n";
    $str .= "<li>Tax: $this->tax</li>\n";
    $str .= "<li>Total Cost of All Items: $this->total_cost</li>\n";

    if ($this->address) {
      $str .= "<li>Ship To Address:\n";
      $str .= $this->address->render();
      $str .= "</li>\n";
    }

    $str .= "</ul>\n";
    
    return $str;
  } // end of render_cart()
  
  public function render_cart_as_text()
  {
    return preg_replace('/<[^>]*>/', '', $this->render_cart());
  } // end of render_cart_as_text()
  
  public function form($form_action, $top_half = NULL, $bottom_half = '', $actions = NULL)
  {
    $this->initialize_cart();
    $this->update_cart_stats();
    
    if ($this->address) {
      $bottom_half = $this->address->form() . $bottom_half;
    }
    
    return parent::form($form_action, $top_half, $bottom_half . $this->dump(), $actions);
  } // end of form()
  
  public function save()
  {
    $this->initialize_cart();
    if (!$this->dirtyP()) {
      return FALSE;
    }
    if (!$this->cookie_value) {
      require_once('CookieTrack.php');
      $this->cookie_value = Globals::$cookie_track instanceof CookieTrack ? Globals::$cookie_track->cookie : NULL;
    }
    if ($this->address) {
      $this->address->save();
      $this->address_id = $this->address->address_id;
    }

    if ($this->cart_items) {
      foreach ($this->cart_items as $cart_item) {
        $cart_item->save();
      }
    }

    parent::save();
  } // end of save()
  
  public function dump($msg = '')
  {
    $str = parent::dump($msg);
    
    if ($this->cart_items) {
      foreach ($this->cart_items as $item) {
        $str .= $item->dump($item->item_info);
      }
    } else {
      $str .= "Cart is Empty\n";
    }
    return $str;
  } // end of dump()
  
}

class ShoppingCartManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'ShoppingCart', 'order_number');
  } // end of __construct()
}

// end class definitions

// function definitions

// end function definitions

// initial processing of POST data

// dispatch actions

?>
