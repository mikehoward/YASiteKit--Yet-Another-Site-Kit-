<?php
/*
#doc-start
h1. Deliverable.php - Generic Deliverable

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a Bare Bones AnInstance Deliverable.

If you need more details, copy to your site _objects_ directory and modify.

h2. Instantiation

$foo = new Deliverable(Globals::$dbaccess, 'deliverable_name')

h2. Attributes

* name - varchar(40) - Deliverable Name
* title - varchar(255) - Title
* deliverable_category - category(deliverable) - Deliverable Categories
* unit_price - float - Unit Price
* currency_code - link(CurrencyCode.country_name) Currency Code
* downloadable - enum(Y,N) - Downloadable
* available - enum(Y,N) - Available
* on_hand - int - Units on Hand
* lead_time - int Lead Time (days)

for fixed dimension downloadables, these are the height and width dimensions

for non-downloadables, this is the footprint required within a box

* width - float(2) - Width (footprint or pixel width)
* height - float(2) - Height (footprint or pixel height)

Third dimension for physical products

* length_units - enum(in,cm, px) - Length Units
* length - float(2) - Length (non-downloadables)

Shipping weight for physical products

* weight - float(2) - Weight (physical product only)
* weight_units - enum(lb,kg) - Weight Units

PayPal button code for PayPal Website Payments Standard (is this the right name?)

* paypal_buy_now_buttonid - varchar(255) - PayPal Buy Now Button Id
* paypal_buy_now_websitecode - text - PayPal Buy Now Website Code
* paypal_buy_now_emaillink - varchar(255) - PayPal Buy Now Email Link
* paypal_add_to_cart_buttonid - varchar(255) - PayPal Add-to-Cart Button Id
* paypal_add_to_cart_websitecode - text - PayPal Add-to-Cart Website Code
* paypal_add_to_cart_emaillink - varchar(255) - PayPal Add to Cart Email Link

h2. Class Methods

None

h2. Instance Methods

* form()
* process_form($rc) - 
* add_to_cart_form($product, $action) - returns a form which will add 0 or more deliverables
for _$product_ to cart. _$action_ is the HTML form element _action_ attribute.

The following six methods communicate with Paypal, manage buttons, and populate
the instance paypal attributes. They all return TRUE on success and FALSE on failure.

* create_paypal_buy_now_button()
* delete_paypal_buy_now_button()
* update_paypal_buy_now_button()
* create_paypal_add_to_cart_button()
* delete_paypal_add_to_cart_button()
* update_paypal_add_to_cart_button()

#end-doc
*/

// global variables
require_once('aclass.php');
// require_once('Category.php');

AClass::define_class('Deliverable', 'name', 
  array( // field definitions
    array('name', 'varchar(40)', 'Deliverable Name'),
    array('title', 'varchar(255)', 'Title'),
    array('deliverable_category', 'category(deliverable)', 'Deliverable Categories'),
    array('unit_price', 'float', 'Unit Price'),
    array('currency_code', 'link(CurrencyCode.country_name)', 'Currency Code'),
    array('downloadable', 'enum(Y,N)', 'Downloadable'),
    array('available', 'enum(Y,N)', 'Available'),
    array('on_hand', 'int', 'Units on Hand'),
    array('lead_time', 'int', 'Lead Time (days)'),
    
    // for fixed dimension downloadables, these are the height and width dimensions
    // for non-downloadables, this is the footprint required within a box
    array('width', 'float(2)', 'Width (footprint or pixel width)'),
    array('height', 'float(2)', 'Height (footprint or pixel height)'),

    array('length_units', 'enum(in,cm, px)', 'Length Units'),
    array('length', 'float(2)', 'Length (non-downloadables)'),

    array('weight', 'float(2)', 'Weight (physical product only)'),
    array('weight_units', 'enum(lb,kg, no)', 'Weight Units'),

    array('paypal_buy_now_buttonid', 'varchar(255)', 'PayPal Buy Now Button Id'),
    array('paypal_buy_now_websitecode', 'text', 'PayPal Buy Now Website Code'),
    array('paypal_buy_now_emaillink', 'varchar(255)', 'PayPal Buy Now Email Link'),
    array('paypal_add_to_cart_buttonid', 'varchar(255)', 'PayPal Add-to-Cart Button Id'),
    array('paypal_add_to_cart_websitecode', 'text', 'PayPal Add-to-Cart Website Code'),
    array('paypal_add_to_cart_emaillink', 'varchar(255)', 'PayPal Add to Cart Email Link'),
  ),
  array(// attribute definitions
    'name' => array('filter' => '[a-z][_a-z0-9]*'),
    'unit_price' => array('format' => '\$%0.2f'),
    'weight_units' => array('default' => 'lb'),
    'length_units' => array('default' => 'in'),
    'currency_code' => array('default' => 'USD'),
    'deliverable_category' => 'category_deep',

    'paypal_buy_now_buttonid' => array('invisible', 'encrypt',),
    'paypal_buy_now_websitecode' => array('invisible', 'encrypt',),
    'paypal_buy_now_emaillink' => array('invisible', 'encrypt',),
    'paypal_add_to_cart_buttonid' => array('invisible', 'encrypt',),
    'paypal_add_to_cart_websitecode' => array('invisible', 'encrypt',),
    'paypal_add_to_cart_emaillink' => array('invisible', 'encrypt',),
      ));
// end global variables

// class definitions
class Deliverable extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Deliverable', $dbaccess, $attribute_values);
  } // end of __construct()
  
  public function form($form_action, $top_half = NULL, $bottom_half = NULL, $actions = NULL)
  {
    $display_create_buy_now = $this->on_hand > 0 && !$this->paypal_buy_now_buttonid
      ? 'inline-block' : 'none';
    $display_update_buy_now = $this->on_hand > 0 && $this->paypal_buy_now_buttonid
      ? 'inline-block' : 'none';
    $display_delete_buy_now = $this->paypal_buy_now_buttonid ? 'inline-block' : 'none';
    $display_create_add_to_cart = $this->on_hand > 0 && !$this->paypal_add_to_cart_buttonid
      ? 'inline-block' : 'none';
    $display_update_add_to_cart = $this->on_hand > 0 && $this->paypal_add_to_cart_buttonid
      ? 'inline-block' : 'none';
    $display_delete_add_to_cart = $this->paypal_add_to_cart_buttonid ? 'inline-block' : 'none';
    
    $bottom_half = "<li class=\"do-paypal-api\">
      <button id=\"create_buy_now\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_create_buy_now}\" type=\"button\" value=\"create_paypal_buy_now_button:{$this->name}\">Create Buy Now Button</button>
      <button id=\"update_buy_now\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_update_buy_now}\" type=\"button\" value=\"update_paypal_buy_now_button:{$this->name}\">Update Buy Now Button</button>
      <button id=\"delete_buy_now\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_delete_buy_now}\" type=\"button\" value=\"delete_paypal_buy_now_button:{$this->name}\">Delete Buy Now Button</button>
      <button id=\"create_add_to_cart\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_create_add_to_cart}\" type=\"button\" value=\"create_paypal_add_to_cart_button:{$this->name}\">Create Add to Cart Button</button>
      <button id=\"update_add_to_cart\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_update_add_to_cart}\" type=\"button\" value=\"update_paypal_add_to_cart_button:{$this->name}\">Update Add to Cart Button</button>
      <button id=\"delete_add_to_cart\" name=\"paypal_button_ajax\" class=\"do-paypal-api\" style=\"display:{$display_delete_add_to_cart}\" type=\"button\" value=\"delete_paypal_add_to_cart_button:{$this->name}\">Delete Add to Cart Button</button>
    </li>";
    return parent::form($form_action, $top_half, $bottom_half, $actions);
  } // end of form()
  
  public function process_form($rc)
  {
    if (!Globals::$paypal_api_username) {
      $rc->safe_post_paypal_buy_now_buttonid = '';
      $rc->safe_post_paypal_add_to_cart_buttonid = '';
    }

    parent::process_form($rc);
  } // end of process_form()
  
  public function add_to_cart_form($product, $action)
  {
    $str = "<tr>\n";
    $str .= "  <td>\n";
    $str .= "  <form action=\"$action\" method=\"post\" accept-charset=\"utf-8\">\n";
    $str .= "    <input type=\"hidden\" name=\"product_name\" value=\"$product->name\">\n";
    $str .= "    <input type=\"hidden\" name=\"deliverable_name\" value=\"$this->name\">\n";
    $str .= "    <input type=\"hidden\" name=\"shopping_cart_action\" value=\"add_to_cart\">\n";
    $str .= "    $this->title\n";
    $str .= "  </td>\n";
    $str .= "  <td>\n";
    $str .= "    Quantity: <input class=\"filtered\" filter=\"\\d*\" type=\"text\" name=\"quantity\" value=\"0\" size=\"5\" maxlength=\"5\">\n";
    $str .= "  </td>\n";
    $str .= "  <td>\n";
    $str .= "    <input type=\"submit\" name=\"submit\" value=\"Add to Cart\">\n";
    $str .= "  </form>\n";
    $str .= "  </td>\n";
    $str .= "</tr>";
    return $str;
  } // end of add_to_cart_form()
  
  public function create_paypal_buy_now_button()
  {
    if (Globals::$paypal_api_username && !$this->paypal_buy_now_buttonid) {
      ObjectInfo::do_require_once('PayPalButtonAPI.php');
      // do the stuff to define the button
      $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
          Globals::$paypal_api_password, Globals::$paypal_api_signature,
          Globals::$paypal_api_live);
        
      $rsp = $paypal_button_api->button_create(
          'BUTTONCODE', 'HOSTED',
          'BUTTONTYPE', 'BUYNOW',
          'BUTTONSUBTYPE', 'PRODUCTS',
          'BUTTONIMAGE', 'CC',
          'L_BUTTONVAR0', "amount=" . sprintf("%0.2f", $this->price),
          'L_BUTTONVAR1', "item_name={$this->title}",
          'L_BUTTONVAR2', "item_number={$this->name}",
          'L_BUTTONVAR3', "quantity={$this->quantity}",
          'L_BUTTONVAR4', "weight={$this->weight}",
          'L_BUTTONVAR5', "weight_unit=lbs",
          'L_BUTTONVAR6', 'cbt=Return to YASiteKit.org',
          'L_BUTTONVAR7', 'cancel_return=http://' . $_SERVER['HTTP_HOST'] . "/DisplayProduct.php?image=$this->name",
          'L_BUTTONVAR8', "return=http://"     . $_SERVER['HTTP_HOST'] . '/' . "ThankYou.php?image=$this->name",
          'L_BUTTONVAR9', 'rm=2'
        );
      if (strtolower($rsp->ack) == 'success') {
        $this->paypal_buy_now_buttonid = $rsp->hostedbuttonid;
        $this->paypal_buy_now_websitecode = $rsp->websitecode;
        $this->paypal_buy_now_emaillink = $rsp->emaillink;
        return $this->save();
      } else {
        Globals::add_message($paypal_button_api->dump());
        Globals::add_message($rsp->dump());
        return FALSE;
      }
    } else {
      return FALSE;
    }
  } // end of create_paypal_buy_now_button()
  
  public function delete_paypal_buy_now_button()
  {
    if (Globals::$paypal_api_username && $this->paypal_buy_now_buttonid) {
      ObjectInfo::do_require_once('PayPalButtonAPI.php');
      $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
        Globals::$paypal_api_password, Globals::$paypal_api_signature,
        Globals::$paypal_api_live);
      $rsp = $paypal_button_api->button_delete($this->paypal_buy_now_buttonid);
      if (strtolower($rsp->ack) == 'success'
        || $rsp->l_errorcode0 == '11951') {
        $this->paypal_buy_now_buttonid = NULL;
        $this->paypal_buy_now_websitecode = NULL;
        $this->paypal_buy_now_emaillink = NULL;
        $this->save();
        return TRUE;
      } else {
        Globals::add_message($rsp->dump());
        IncludeUtilities::report_bad_thing("Cannot Delete PayPal Button {$this->paypal_buy_now_buttonid} for image $this->name",
          $rsp->dump("paypal button api button_delete result"));
        return FALSE;
      }
    } else {
      return TRUE;
    }
  } // end of delete_paypal_buy_now_button()
  
  public function update_paypal_buy_now_button()
  {
    if (!Globals::$paypal_api_username) {
      return FALSE;
    }
    if (!$this->paypal_buy_now_buttonid) {
      return $this->create_paypal_buy_now_button();
    }

    // update button as required
    ObjectInfo::do_require_once('PayPalButtonAPI.php');
    $ar = array(
      'html-var', 'amount', sprintf("%0.2f", $this->price),
      'html-var', 'item_name', $this->title,
      'html-var', 'item_number', $this->name,
      'html-var', 'quantity', $this->quantity,
      'html-var', 'weight', $this->weight,
      'html-var', 'weight_unit', 'lbs',
      'html-var', 'cbt', 'Return to YASiteKit.org',
      'html-var', 'cancel_return', 'http://' . $_SERVER['HTTP_HOST'] . '/' . Globals::$page_name
        . "?image=$this->name" ,
      'html-var', "return", "http://"     . $_SERVER['HTTP_HOST'] . '/' . "ThankYou.php?image=$this->name",
      'html-var', 'rm', '2',
      );
    $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
        Globals::$paypal_api_password, Globals::$paypal_api_signature,
        Globals::$paypal_api_live);
    $rsp = $paypal_button_api->button_update($this->paypal_buy_now_buttonid, 'BUYNOW', $ar);
    if (strtolower($rsp->ack) == 'success') {
      $this->paypal_buy_now_buttonid = $rsp->hostedbuttonid;
      $this->paypal_buy_now_websitecode = $rsp->websitecode;
      $this->paypal_buy_now_emaillink = $rsp->emaillink;
      return TRUE;
    } elseif ($rsp->l_errorcode0 == '11951') {
      $this->paypal_buy_now_buttonid = FALSE;
      $this->paypal_buy_now_websitecode = FALSE;
      $this->paypal_buy_now_emaillink = FALSE;
      return FALSE;
    } else {
      IncludeUtilities::report_bad_thing("Cannot Update Paypal Button {$this->paypal_buy_now_buttonid} for image $this->name",
        $rsp->dump("paypal button api button_update result"));
      return FALSE;
    }
  } // end of update_paypal_buy_now_button()
  
  public function create_paypal_add_to_cart_button()
  {
    if (Globals::$paypal_api_username && !$this->paypal_add_to_cart_buttonid) {
      ObjectInfo::do_require_once('PayPalButtonAPI.php');
      // do the stuff to define the button
      $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
          Globals::$paypal_api_password, Globals::$paypal_api_signature,
          Globals::$paypal_api_live);
        
      $rsp = $paypal_button_api->button_create(
          'BUTTONCODE', 'HOSTED',
          'BUTTONTYPE', 'CART',
          'BUTTONSUBTYPE', 'PRODUCTS',
          'BUTTONIMAGE', 'SML',
          'L_BUTTONVAR0', "amount=" . sprintf("%0.2f", $this->price),
          'L_BUTTONVAR1', "item_name={$this->title}",
          'L_BUTTONVAR2', "item_number={$this->name}",
          'L_BUTTONVAR3', "quantity={$this->quantity}",
          'L_BUTTONVAR4', "weight={$this->weight}",
          'L_BUTTONVAR5', "weight_unit=lbs",
          'L_BUTTONVAR6', 'cbt=Return to YASiteKit.org',
          'L_BUTTONVAR7', 'cancel_return=http://' . $_SERVER['HTTP_HOST'] . "/DisplayProduct.php?image=$this->name" ,
          'L_BUTTONVAR8', "return=http://"     . $_SERVER['HTTP_HOST'] . '/' . "ThankYou.php?image=$this->name",
          'L_BUTTONVAR9', 'rm=2',
          'L_BUTTONVAR10', 'shopping_url=http://' . $_SERVER['HTTP_HOST'] . '/index.php'
        );
      if (strtolower($rsp->ack) == 'success') {
        $this->paypal_add_to_cart_buttonid = $rsp->hostedbuttonid;
        $this->paypal_add_to_cart_websitecode = $rsp->websitecode;
        $this->paypal_add_to_cart_emaillink = $rsp->emaillink;
        return $this->save();
      } else {
        Globals::add_message($paypal_button_api->dump());
        Globals::add_message($rsp->dump());
        return FALSE;
      }
    } else {
      return FALSE;
    }
  } // end of create_paypal_add_to_cart_button()
  
  public function delete_paypal_add_to_cart_button()
  {
    if (Globals::$paypal_api_username && $this->paypal_add_to_cart_buttonid) {
      ObjectInfo::do_require_once('PayPalButtonAPI.php');
      $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
        Globals::$paypal_api_password, Globals::$paypal_api_signature,
        Globals::$paypal_api_live);
      $rsp = $paypal_button_api->button_delete($this->paypal_add_to_cart_buttonid);
      if (strtolower($rsp->ack) == 'success'
        || $rsp->l_errorcode0 == '11951') {
        $this->paypal_add_to_cart_buttonid = NULL;
        $this->paypal_add_to_cart_websitecode = NULL;
        $this->paypal_add_to_cart_emaillink = NULL;
        $this->save();
        return TRUE;
      } else {
        Globals::add_message($rsp->dump());
        IncludeUtilities::report_bad_thing("Cannot Delete PayPal Button {$this->paypal_add_to_cart_buttonid} for image $this->name",
          $rsp->dump("paypal button api button_delete result"));
        return FALSE;
      }
    } else {
      return TRUE;
    }
  } // end of delete_paypal_add_to_cart_button()
  
  public function update_paypal_add_to_cart_button()
  {
    if (!Globals::$paypal_api_username) {
      return FALSE;
    }
    if (!$this->paypal_add_to_cart_buttonid) {
      return $this->create_paypal_add_to_cart_button();
    }

    // update button as required
    ObjectInfo::do_require_once('PayPalButtonAPI.php');
    $ar = array(
      'html-var', 'amount', sprintf("%0.2f", $this->price),
      'html-var', 'item_name', $this->title,
      'html-var', 'item_number', $this->name,
      'html-var', 'quantity', $this->quantity,
      'html-var', 'weight', $this->weight,
      'html-var', 'weight_unit', 'lbs',
      'html-var', 'cbt', 'Return to YASiteKit.org',
      'html-var', 'cancel_return', 'http://' . $_SERVER['HTTP_HOST'] . '/' . Globals::$page_name
        . "?image=$this->name" ,
      'html-var', "return", "http://"     . $_SERVER['HTTP_HOST'] . '/' . "ThankYou.php?image=$this->name",
      'html-var', 'rm', '2',
      'html-var', 'shopping_url', 'http://' . $_SERVER['HTTP_HOST'] . '/index.php'
      );
    $paypal_button_api = new PayPalButtonAPI(Globals::$paypal_api_username,
        Globals::$paypal_api_password, Globals::$paypal_api_signature,
        Globals::$paypal_api_live);
    $rsp = $paypal_button_api->button_update($this->paypal_add_to_cart_buttonid, 'BUYNOW', $ar);
    if (strtolower($rsp->ack) == 'success') {
      $this->paypal_add_to_cart_buttonid = $rsp->hostedbuttonid;
      $this->paypal_add_to_cart_websitecode = $rsp->websitecode;
      $this->paypal_add_to_cart_emaillink = $rsp->emaillink;
      return TRUE;
    } elseif ($rsp->l_errorcode0 == '11951') {
      $this->paypal_add_to_cart_buttonid = FALSE;
      $this->paypal_add_to_cart_websitecode = FALSE;
      $this->paypal_add_to_cart_emaillink = FALSE;
      return FALSE;
    } else {
      IncludeUtilities::report_bad_thing("Cannot Update Paypal Button {$this->paypal_add_to_cart_buttonid} for image $this->name",
        $rsp->dump("paypal button api button_update result"));
      return FALSE;
    }
  } // end of update_paypal_add_to_cart_button()
}


class DeliverableManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Deliverable', 'name');
  } // end of __construct()
}
?>
