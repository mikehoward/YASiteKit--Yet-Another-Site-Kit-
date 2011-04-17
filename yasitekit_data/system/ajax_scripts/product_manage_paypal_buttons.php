<?php
/*
#doc-start
h1. product_manage_paypal_buttons.php - AJAXifies PayPal Button Creation/Update and Delete

bq. Copyright Mike Howard, 2010-04-19. All rights reserved

This module implements creating, updating, and deleting paypal buttons
for Product products.

This program accepts one POST argument named _paypal_button_ajax_
which is a string of the form: 'func_name:product_name', where:

* func_name - string - required - is the name of the Product class method to call
without arguments
* product_name - string - required - is the name of an existing Product object.

All paypal actions are performed using methods defined in the Product class:
_create_paypal_buy_now_button(), update_paypal_buy_now_button()_,
_delete_paypal_buy_no_button()_, _create_paypal_add_to_cart_button()_,
_update_paypal_add_to_cart_button()_, and _delete_paypal_add_to_cart_button()_.

Returns a JSON object with the following fields fields.

* result - always returns one of:
** no-authority - insufficient authority to create paypal buttons for the supplied image
** no-image - specified image _name_ does not exist in database
** success - button created, updated, or deleted
** failure - button not created, update or deleted successfully. Requires further
investigation by administrator.
** internal-error - a bad thing was detected in the program
* explanation - some hopefully helpful text expanding on the content of _result_
* paypal_buy_now_buttonid - only returned if image object is defined and sufficient
authority is available to perform the action. Thus, only defined if the _result_
key has the value _success_ or _failure_. If returned will _always_ contain the
value of the _paypal_buy_now_buttonid_ field of the image object instance.
* paypal_add_to_cart_buttonid - similar to paypal_buy_now_buttonid
* diag - diagnostic information - which may or may not be null (a mysterious
anomoly)

#doc-end
*/

// Begin Global Variables
require_once('Account.php');
require_once('Product.php');
$product_obj = NULL;
$product_name = NULL;
$paypal_func_name = NULL;

// end Global Variabls

// begin function defintions

function ajax_set_required_authority()
{
  global $product_obj;
  
  if (!$product_obj || !(Globals::$account_obj instanceof Account)) {
    return (Globals::$web_service->required_authority = 'FORBIDDEN');
  }

  // get the product instance
  switch (Globals::$account_obj->authority) {
    case 'S':
    case 'X':
      Globals::$web_service->required_authority = 'S,X';
      break;
    default:
      // product_owner is a 'select' field - the value is a url encoded, serialization of the key/value array
      $key_value = Globals::$account_obj->encode_key_values();
      if ($key_value == $product_obj->product_owner) {
        Globals::$web_service->required_authority = Globals::$account_obj->authority;
      } else {
        Globals::$web_service->required_authority = 'FORBIDDEN';
      }
      break;
  }
} // end of ajax_set_required_authority()

function ajax_content()
{
  global $product_obj;
  global $paypal_func_name;
  // here we assume we have authority

  $rsp = $product_obj->$paypal_func_name();
  $ar = array('result' => $result,
    'explanation' => $explanation,
    'product_name' => $product_name,
    'func_name' => $func_name,
    );
  if ($rsp['result'] == 'failure') {
    Globals::$web_service->failure($rsp);
    return;
  } else {
    switch (Globals::$web_service->data_format) {
      case 'json':
      case 'xml':
      case 'text':
      case 'html':
        $ar['paypal_buy_now_buttonid'] = $product_obj->paypal_buy_now_buttonid;
        $ar['paypal_add_to_cart_buttonid'] = $product_obj->paypal_add_to_cart_buttonid;
        $ar['create_buy_now'] = $product_obj->sold == 'N' && !$product_obj->paypal_buy_now_buttonid
          ? 'inline-block' : 'none';
        $ar['update_buy_now'] = $product_obj->sold == 'N' && $product_obj->paypal_buy_now_buttonid
          ? 'inline-block' : 'none';
        $ar['delete_buy_now'] = $product_obj->paypal_buy_now_buttonid ? 'inline-block' : 'none';
        $ar['create_add_to_cart'] = $product_obj->sold == 'N' && !$product_obj->paypal_add_to_cart_buttonid
          ? 'inline-block' : 'none';
        $ar['update_add_to_cart'] = $product_obj->sold == 'N' && $product_obj->paypal_add_to_cart_buttonid
          ? 'inline-block' : 'none';
        $ar['delete_add_to_cart'] = $product_obj->paypal_add_to_cart_buttonid ? 'inline-block' : 'none';
        // Construct an array containing your return data
        Globals::$web_service->success($ar);
        return;
      case 'script':
      case 'jsonp':
      default:
        Globals::$web_service->failure("Illegal AJAX request: data form cannot be " . Globals::$web_service->data_format);
        return;
    }
  }
} // end of ajax_content()

// initialization

// check request parameters and set globals used in this module
if (isset(Globals::$rc->safe_request_paypal_button_ajax)) {
  list($paypal_func_name, $product_name) = explode(':', Globals::$rc->safe_request_paypal_button_ajax);
  if (AnInstance::existsP('Product', Globals::$dbaccess, $product_name)) {
    $product_obj = new Product(Globals::$dbaccess, $product_name);
  }
}
?>
