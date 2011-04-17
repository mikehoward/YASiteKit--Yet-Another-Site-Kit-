<?php
/*
#doc-start
h1.  PayPalButtonAPI.php - Object implementation of PayPal WebPayments Standard NVP API

Created by  on 2010-04-01.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module defines two classes:

* the *PayPalButtonAPI* - extends the "PayPalAPI":PayPalAPI.html object
to implement the Name-Value-Pair interface
to PayPal's Web Standard Button Manager and provides the following features:
"(see below)":#paypalbuttonapi 
** create a new encrypted button and return string to embed in button element
** display an encrypted button given saved encrypted button data
** receive returned information from paypal.com after a user has clicked an
encrypted buy-button
* the *PayPalButtonDetails* - which helps manage parameters for the *button_update()*
method of the PayPalButtonAPI class. "(see below)":#paypalbuttondetails
It is good to IGNORE this class.

h2(#paypalbuttonapi). PayPalButtonAPI Class

$paypalbuttonapi = new PayPalButtonAPI(api_username, api_password, api_signature, live = FALSE)
returns an instance of PayPalButtonAPI. It is an extension of PayPalAPI and supports
button creation, deletion, listing, and updating.

h3. Testing Status

Most functions have been tested pretty well. There are still bugs, but
probably nothing significant.

Most errors encountered will be operational errors caused by misspelling
PayPal field names or using incompatable paypal parameter combinations.
Paypal seems to be pretty good at detecting this stuff and moderate to
poor at supplying useable error messages. This class tries to compensate
for that, but not always.

All of the _button_...()_ methods return an ACurlData object. You should _always_
verify that the _ack_ attribute has the value Success. Anything else is an error.
Look at attributes:

* errorcode<n> - usually ERRORCODE0 - contains the numeric error code for
the paypal detected error
* L_SHORTMESSAGE<n> - usually L_SHORTMESSAGE0 - a short hint
* L_LONGMESSAGE<n> - usually L_LONGMESSAGE0 - a longer error hint
* L_SEVERITYCODE<n> - usually L_SEVERITYCODE0 - whether the world is ending or not

There is some more stuff, but you should read the PayPal NVP API Developer's guide
for that.

h3. Attributes

Inherited readonly attributes from "PayPalAPI":PayPalAPI.html

h2. Class Methods

h2. Instance Methods

Inherited methods

* post(method, args-array) - inherited from PayPalAPI

Non-Button specific methods

* dump(msg = NULL) - returns a string describing the internal state of the object

Button Specific methods. See PayPal WPS Button Manager API doc for details and
arguments.

NOTE: there is not a direct 1-1 name relationship between these methods and paypal
Button Manager API METHOD values. I've changed the names to something I thought
was more appropriate for BMManageButtonStatus (which only deletes) and
BMUpdateButton (which replaces all button data, deleting anything not specified)

In all of these functions, the paramter _args_ is a sequence of key, value pairs which can
be passed to an ACurlData object. For example, 'buttontype', 'BUYNOW'

* button_create(args) - Creates a button. See doc for details.
* button_details(hostedbuttonid) - returns crud for specified buttonn
* button_get_inventory(hostedbuttonid, args) - see book
* button_delete(hostedbuttonid) - delete specified button. This uses the BMManageButtonStatus
command - which only supports 'DELETE' at this time.
* button_update(hostedbuttonid, buttontype, args) - This is a special case. See "below":#button_update
* button_search(startdate, args) - start date MUST be YYYY-MM-DDTHH:MM:SSZ format
or PayPal will error out. Defaults to 2009-01-01T00:00:00Z. Returns lots of stuff.
Only arg permitted is ENDDATE
* button_set_inventory(hostedbuttonid, trackinv, trackpnl, args) - see book

h4. RETURNs

All the button_...() functions return the result of the PayPalAPI::post()
method. "see":PayPalAPI.html for details.

h3(#button_update). Button Update

Frankly, the method BMUpdateButton drove me nuts. I couldn't get it to
work without retrieving the current button details and then merging
the button updates into the existing details and then applying the button,

This gets a little harder because the variable and option numberings used
in L_BUTTONVARn (html variables) are not preserved by paypal on their system.

Usage: $rsp = $paypal_button_api->button_update(hostedbuttonid, buttontype, args), where

* hostedbuttonid - is the button id of the hosted button.
* buttontype - is the type of button it is. Must match what paypal has or update
will fail. [Failure can be a mystery because 'illegal button type' seems to be
returned for a lot of other errors]. Legal button types are BUYNOW

So, _button_update()_ allows you to:

* add, redefine, and delete HTML variables, such as _item_name_, _amount_, _quantity_, etc
** html-var - format: 'html-var', 'name', 'value' - adds or re-defines specified html
variable
** del-html - format: 'rm-html', 'name', - deletes specified html variable, if
it exsits
* add, redefine, and delete text fields 0 and 1
** text-n - format 'text-0', 'value' - adds or redefines Text field 0. Similarly for
Text Field 1.
** del-text-n - format 'del-text-1' - deletes Text field 1.
* add, redefine, and delete option select elements
** option-n - format 'option-0', 'name', 'number of selects', 'select0', 'select1', ... -
defines or replaces OPTION0 values with the supplied name and select options.
** option-price-0 format 'option-0', 'name', 'number of selects', 'select0', 'price0',
select1', 'price1', ... - defines or replaces OPTION0 with the supplied name, selects,
and pricing
** del-option-n - removes option-n - where n is 0, 1, ... 9. NOTE: only options
0 through 4 are allowed for HOSTED buttons.
* redefine any other PayPal legally defineable variable.
** field 'field', 'BUTTONIMAGE', 'SML' - adds or redefines a 'field' value - These are upper case
words and must be followed by their value, as in 'BUTTONIMAGE', 'CC'
** del-field - 'del-field', 'BUTTONIMAGEURL' - removes the value of the specified field

h2. How to Use

Create an object to hold your product or service data. Include a couple of
fields to hold the paypal data.

The values of api-username, api-password, and api-signature are the super-secret
credentials you get from PayPal. The _live_flag_ is a boolean which determines
which paypal site the code talks to: _live_flag_ FALSE goes to the sandbox;
_live_flag_ TRUE goes to the real site.

pre. $ppbm_obj = new PayPallButtonAPI(api-username, api-password, api-signature, $live_flag);
$rsp = $paypal_button_api->button_create('BUTTONTYPE', $buttontype = 'BUYNOW',
    'BUTTONCODE', 'HOSTED',
    'BUTTONSUBTYPE', 'PRODUCTS',
    'BUTTONIMAGE', 'CC',
    'L_BUTTONVAR0', 'amount=1,234.95',
    'L_BUTTONVAR1', 'item_name=Fancy Widget',
    'L_BUTTONVAR2', 'item_number=FWDG123',
    'L_BUTTONVAR3', 'quantity=1',
    'L_BUTTONVAR4', 'shipping=5.00',
    'L_BUTTONVAR5', 'weight=100',
    'L_BUTTONVAR6', 'weight_unit=lbs',
    'OPTION0NAME', 'Color',
    'L_OPTION0SELECT0', 'Red',
    'L_OPTION0SELECT1', 'Blue',
    'L_OPTION0SELECT2', 'Yellow',
    'L_OPTION0SELECT3', 'Cyan',
    'L_OPTION0SELECT4', 'Black'
    );
$hostedbuttonid = $rsp->hostedbuttonid;

* hostedbuttonid - defined if BUTTONCODE is 'HOSTED' - holds value of HOSTEDBUTTONID
* website_code - holds value of WEBSITECODE
* emaillink - text for an anchor - value of EMAILLINK. This is not always present.
It appears paypal omits this text if anything which requires a form is specified -
such as options values or input text

h2(#paypalbuttondetails). PayPalButtonDetails Class

This class is fragile. It only does the most cursory sanity checks and - at present
- ONLY supports variables useful for  the _button_update()_ method of the PayPalButtonAPI
class. It is NOT useful for inventory management.

h3. Attributes

All attributes are publically available, so you can mess them up as much
as you want. All are associative arrays.

* $_html_var - HTML array is keyed by the HTML variable name - generally lower case
* $_text_box - Text box arrays may only have indicies '0' and '1'
* $_field - field arrays are indexed by the field name - converted to upper case
* option arrays may only have indicies '0', '1', '2', '3', and '4'.
The values of option arrays are:
** $_option_name - string value - name of option list. e.g. _option_name['0'] = 'A Fooness'
** $_option_select - array of selection values. e.g. _option_select['1'] = array('foo', 'bar')
** $_option_price - array of prices. e.g. _option_prices['0'] = array('12.50', '1,000.00')


h3. Class Methods

None

h3. Instance Methods

* toACurlData() - returns an ACurlData object containing all of the data
in the instance using appropriate PayPal button API names
* html_var(name, value) - adds or redefines an HTML variable, which will have
a name of the form L_BUTTONVARn
* del_html(name) - deletes the specified HTML variable
* text_n(n, value) - adds or redefines text box string 'n'
* del_text_n(n) - deletes text box string 'n'
* option_n(n, name, num, sel1, sel2, ...) - adds or redefines option 'n'. 'num'
is the number of 'select' values
* option_price_n(n, name, num, sel1, pric1, sel2, price2, ...) - adds or redefines
option 'n' with pricing. NOTE: Paypal only allows 'n = 0'
* del_option_n(n) - deletes option 'n'
* field(name, value) - defines PayPal API field 'name' to be 'value'. Both 'name'
and 'value' are converted to upper case. 'name' must be a legal name
* del_field(name) - deletes the value of field 'name'. Again, it is converted to upper
case
* dump(msg = NULL) - returns a string displaying the contents of this instance.

#end-doc
*/

// global variables
ObjectInfo::do_require_once('PayPalAPI.php');

// end global variables

// class definitions

class PayPalButtonDetailsException extends Exception {}

class PayPalButtonDetails {
  public $_html_var = array();
  public $_text_box = array();
  public $_option_name = array();
  public $_option_select = array();
  public $_option_price = array();
  public $_field = array();
  public function __construct($arg)
  {
    if ($arg instanceof ACurlData) {
      foreach ($arg->keys() as $key) {
        $this->snarf_key_value($key, $arg->$key);
      }
    } elseif (is_array($arg)) {
      while (count($args) >= 2) {
        $key = array_shift($args);
        $value = array_shift($args);
        $snarf_key_value($key, $value);
      }
    } else {
      throw new PayPalButtonDetailsException("PayPalButtonDetails::__construct(arg): arg is neither ACurlData instance or an array");
    }
  } // end of __construct()
  
  public function __toString()
  {
    return (string)($this->toACurlData());
  } // end of __toString()
  
  public function toACurlData()
  {
    $idx = 0;
    $ar = array();
    if ($this->_html_var) {
      foreach ($this->_html_var as $key => $val) {
        $ar[] = "L_BUTTONVAR{$idx}";
        $ar[] = "\"$key=$val\"";
        $idx += 1;
      }
    }
    if (isset($this->_textbox)) {
      foreach ($this->_textbox as $key => $val) {
        $ar[] = "L_TEXTBOX{$key}";
        $ar[] = $val;
      }
    }
    if ($this->_option_name) {
      foreach ($this->_option_name as $key => $val) {
        $ar[] = "OPTION{$key}NAME";
        $ar[] = $val;
        foreach ($this->_option_select[$key] as $sel_key => $sel_val) {
          $ar[] = "L_OPTION{$key}SELECT{$sel_key}";
          $ar[] = $sel_val;
        }
        if (isset($this->_option_price[$key])) {
          foreach ($this->_option_price[$key] as $sel_key => $sel_val) {
            $ar[] = "L_OPTION{$key}PRICE{$sel_key}";
            $ar[] = "$sel_val";
          }
        }
      }
    }
    if ($this->_field) {
      foreach ($this->_field as $key => $val) {
        $ar[] = $key;
        $ar[] = $val;
      }
    }

    $acurldata = new ACurlData('upper');
    $acurldata->parse_array($ar);
    return $acurldata;
  } // end of toACurlData()
  
  private function snarf_key_value($key, $value)
  {
    if (preg_match('/^L_BUTTONVAR(\d+)$/', $key, $match_obj)) {
      preg_match('/^"([^"=]+)=(.*)"$/', $value, $m);
      $this->_html_var[$m[1]] = $m[2];
    } elseif (preg_match('/^L_TEXTBOX(\d+)$/', $key, $match_obj)) {
      $this->_textbox[$match_obj[1]] = $value;
    } elseif (preg_match('/^OPTION(\d+)NAME$/', $key, $match_obj)) {
      $this->_option_name[$match_obj[1]] = $value;
    } elseif (preg_match('/^L_OPTION(\d+)SELECT(\d+)$/', $key, $match_obj)) {
      if (!isset($this->_option_select[$match_obj[1]]))
        $this->_option_select[$match_obj[1]] = array();
      $this->_option_select[$match_obj[1]][$match_obj[2]] = $value;
    } elseif (preg_match('/^L_OPTION(\d+)PRICE(\d+)$/', $key, $match_obj)) {
      if (!isset($this->_option_price[$match_obj[1]]))
        $this->_option_price[$match_obj[1]] = array();
      $this->_option_price[$match_obj[1]][$match_obj[2]] = $value;
    } else {
      $this->_field[$key] = $value;
    }
  } // end of snarf_key_value()
  
  public function html_var($name, $value)
  {
    $this->_html_var[$name] = $value;
  } // end of html_var()
  
  public function del_html($name)
  {
    if (isset($this->_html_var[$name])) unset($this->_html_var[$name]);
  } // end of del_html()
  
  public function text_n($n, $value)
  {
    $n = (string)$n;
    if ($n == '0' || $n == '1') {
      $this->_textbox[$n] = $value;
    } else {
      throw new PayPalButtonDetailsException("PayPalButtonDetails::text_n($n, $value): $n is not 0 or 1");
    }
  } // end of text_n()
  
  public function del_text_n($n)
  {
    $n = (string)$n;
    if ($n == '0' || $n == '1') {
      if (isset($this->_textbox[$n])) unset($this->_textbox[$n]);
    } else {
      throw new PayPalButtonDetailsException("PayPalButtonDetails::del_text_n($n, $value): $n is not 0 or 1");
    }
  } // end of del_text_n()
  
  public function option_n($n, $name, $select_count, $argv)
  {
    if (!($n >= '0' && $n <= '9')) {
      throw new PayPalButtonDetailsException("PayPalButtonDetails::optin_n($n, $name, values): $n is not 0 or 1");
    }
    $this->_option_name[$n] = $name;
    $this->_option_select[$n] = array();
    while ($select_count > 0) {
      $this->_option_select[$n][] = array_shift($argv);
      $select_count -= 1;
    }
  } // end of option_n()
  
  public function option_price_n($n, $name, $select_count, $argv)
  {
    if ($n != '0')
      throw new PayPalButtonDetailsException("PayPalButtonDetails::option_price_n($n, $name,...): Index $n must be '0'");
    $this->_option_name['0'] = $name;
    $this->_option_select['0'] = array();
    $this->_option_price['0'] = array();
    while ($select_count > 0) {
      $this->_option_select['0'][] = array_shift($argv);
      $this->_option_price['0'][] = array_shift($argv);
      $select_count -= 1;
    }
  } // end of option_price_0()
  
  public function del_option_n($n)
  {
    if (!($n >= '0' && $n <= '9')) {
      throw new PayPalButtonDetailsException("PayPalButtonDetails::optin_n($n, $name, values): $n is not 0 or 1");
    }
    if (isset($this->_option_name[$n])) unset($this->_option_name[$n]);
    if (isset($this->_option_select[$n])) $this->_option_select[$n] = array();
    if (isset($this->_option_price[$n])) $this->_option_price[$n] = array();
  } // end of del_option_n()
  
  public function field($name, $value)
  {
    $this->_field[strtoupper($name)] = strtoupper($value);
  } // end of field()
  
  public function del_field($name)
  {
    if (isset($this->_field[strtoupper($name)])) unset($this->_field[$name]);
  } // end of del_field()
  
  public function dump($msg = NULL)
  {
    $str = ($msg ? $msg . "\n" : '') . "PayPal Button Details:\n";
    if ($this->_html_var) {
      $str .= "HTML Variables:\n";
      foreach ($this->_html_var as $key => $val) {
        $str .= "  $key: $val\n";
      }
    }
    if ($this->_textbox) {
      $str .= "Text Box Labels:\n";
      foreach ($this->_textbox as $key => $val) {
        $str .= "  Text Box $key: $val\n";
      }
    }
    if ($this->_option_name) {
      foreach ($this->_option_name as $key => $option_name) {
        $str .= "Option $key: $option_name\n";
        foreach ($this->_option_select[$key] as $select_key => $select_val) {
          if (isset($this->_option_price[$key])) {
            $str .= "  Sel $select_key: $select_val / {$this->_option_price[$key][$select_key]}\n";
          } else {
            $str .= "  Sel $select_key: $select_val\n";
          }
        }
      }
    }
    if ($this->_field) {
      $str .= "Other Fields:\n";
      foreach ($this->_field as $key => $val) {
        $str .= "  $key: $val\n";
      }
    }
    return $str;
  } // end of dump()
}

class PayPalButtonAPIException extends Exception {}

class PayPalButtonAPI extends PayPalAPI {
  
  private $post_args = NULL;

  private function process_args($args, $sanity_ar = NULL)
  {
    $acurldata = new ACurlData('upper');
    $acurldata->parse_array($args);
    // set defaults
    if ($sanity_ar) {
      foreach ($sanity_ar as $key => $val) {
        if (!isset($acurldata->$key)) {
          $acurldata->$key = $val[0];
        } elseif (!in_array($acurldata->$key, $val)) {
          throw new PayPalButtonAPIException("PayPalButtonAPI::process_args(): Illegal value for parameter $key: {$acurldata->$key} not one of " . implode(',',$val));
        }
      }
    }
    return $acurldata;
  } // end of process_args()

  // button manager methods
  public function button_create()
  {
    static $sanity_ar = array(
        'buttoncode' => array('HOSTED', 'ENCRYPTED', 'CLEARTEXT'),
        'buttontype' => array('BUYNOW', 'CART', 'GIFTCERTIFICATE', 'SUBSCRIBE', 'DONATE',
            'UNSUBSCRIBE', 'VIEWCART'),
        'buttonsubtype' => array('PRODUCTS', 'SERVICES'),
        'buttonimage' => array('REG', 'SML', 'CC',),
        'buynowtext' => array('BUYNOW', 'PAYNOW'),
        'subscribetext' => array('BUYNOW', 'PAYNOW'),
      );    // initialize array - copy unchecked stuff directly to post variable array
    $this->post_args = $this->process_args(IncludeUtilities::array_flatten(func_get_args()), $sanity_ar);

    return $this->post('BMCreateButton', $this->post_args);
  } // end of button_create()
  
  public function button_search($start_date)
  {
    $args = IncludeUtilities::array_flatten(func_get_args());
    if (!$start_date) {
      array_shift($args);
      array_unshift($args, '2009-01-01T00:00:00Z');
    }
    array_unshift($args, 'STARTDATE');
    
    $this->post_args = $this->process_args($args);
    
    return $this->post('BMButtonSearch', $this->post_args);
  } // end of button_search()
  
  public function button_details($hostedbuttonid)
  {
    $args = IncludeUtilities::array_flatten(func_get_args());
    if (!$hostedbuttonid) {
      throw new PayPalButtonAPIException("PayPalButtonAPI::button_details(): button id required");
    }
    array_unshift($args, 'HOSTEDBUTTONID');
    
    $this->post_args = $this->process_args($args);
    
    return $this->post('BMGetButtonDetails', $this->post_args);
  } // end of button_details()

  public function button_delete($hostedbuttonid)
  {
    // $args = func_get_args(); // only need the initial argument
    if (!$hostedbuttonid) {
      throw new PayPalButtonAPIException("PayPalButtonAPI::button_details(): button id required");
    }
    $this->post_args = new ACurlData('HOSTEDBUTTONID', $hostedbuttonid, 'BUTTONSTATUS', 'DELETE');
    
    return $this->post('BMManageButtonStatus', $this->post_args);
  } // end of button_details()

  public function button_update($hostedbuttonid, $buttontype)
  {
    $args = IncludeUtilities::array_flatten(array_slice(func_get_args(), 2));
    if (!$buttontype) {
      throw new PayPalButtonAPIException("PayPalButtonAPI::button_update(hostedbuttonid, buttontype): buttontype cannot be null");
    }
    if (!$hostedbuttonid) {
      throw new PayPalButtonAPIException("PayPalButtonAPI::button_update(hostedbuttonid, buttontype): hostedbuttonid cannot be null");
    }

    // get button details
    $button_details = new PayPalButtonDetails($this->button_details($hostedbuttonid));

    // remove some fields
    foreach (array('ack', 'version', 'build', 'timestamp', 'correlationid') as $field) {
      $button_details->del_field($field);
    }
    foreach (array('bn', 'lc') as $html_var) {
      $button_details->del_html($html_var);
    }

    // parse args into button_details
    while ($args) {
      $command = strtolower(array_shift($args));
      if (preg_match('/^(html-var|del-html|text-|del-text-|option-|option-price-|del-option-|field|del-field)(\d+)?$/', $command, $match_obj)) {
        switch ($match_obj[1]) {
          case 'html-var':
            $name = array_shift($args);
            $value = array_shift($args);
            $button_details->html_var($name, $value);
            break;
          case 'del-html':
            $name = array_shift($args);
            $button_details->del_html($name);
            break;
          case 'text-':
            $value = array_shift($args);
            $button_details->text_n($match_obj[2], $value);
            break;
          case 'del-text-':
            $button_details->del_text_n($match_obj[2]);
            break;
          case 'option-':
            $name = array_shift($args);
            $select_count = intval(array_shift($args));
            $select_args = array_slice($args, 0, $select_count);
            $args = array_slice($args, $select_count);
            $button_details->option_n($match_obj[2], $name, $select_count, $select_args);
            break;
          case 'option-price-':
            $name = array_shift($args);
            $select_count = intval(array_shift($args));
            $select_args = array_slice($args, 0, $select_count * 2);
            $args = array_slice($args, $select_count * 2);
            $button_details->option_price_n($match_obj[2], $name, $select_count, $select_args);
            break;
          case 'del-option-':
            $button_details->del_option_n($match_obj[2]);
            break;
          case 'field':
            $name = array_shift($args);
            $value = array_shift($args);
            $button_details->field($name, $value);
            break;
          case 'del-field':
            $name = array_shift($args);
            $button_details->del_field($name);
            break;
          default:
            throw new PayPalButtonAPIException("PayPalButtonAPI::button_update($hostedbuttonid, $buttontype): Internal Error: $command");
        }
      } else {
        throw new PayPalButtonAPIException("PayPalButtonAPI::button_update($hostedbuttonid, $buttontype): Update Command Error: '$command' not defined or misspelled");
      }
    }

    // convert to curl data
    $this->post_args = $button_details->toACurlData();

    return $this->post('BMUpdateButton', $this->post_args);
  } // end of button_update()
  
  public function button_set_inventory($hostedbuttonid, $trackinv, $trackpnl)
  {
    static $sanity_ar = array(
      'TRACKINV' => array('0', '1'),
      'TRACKPNL' => array('0', '1'),
      'REUSEDIGITALDOWNLOADKEYS' => array('0', '1'),
      'APPENDDITITALDOWNLOADKEY' => array('0', '1'),
      'OPTIONINDEX' => array('0', '1', '2', '3', '4', ),
      );
    $args = IncludeUtilities::array_flatten(array_slice(func_get_args(), 3));
    foreach (array('hostedbuttonid', 'trackinv', 'trackpnl') as $key) {
      if (!isset($$key) || $$key === NULL)
        throw new PayPalButtonAPIException("PayPalButtonAPI::button_set_inventory(hostedbuttonid, trackinv, trackpnl): $key cannot be null");
    }
    foreach (array('trackinv', 'trackpnl') as $key) {
      $$key = (string)$$key;
      if ($$key != '0' && $$key != '1')
        throw new PayPalButtonAPIException("PayPalButtonAPI::button_set_inventory(hostedbuttonid, trackinv, trackpnl): $key must be 0 or 1, not {$$key}");
    }
    array_unshift($args, 'HOSTEDBUTTONID', $hostedbuttonid, 'TRACKINV', $trackinv,
      'TRACKPNL', $trackpnl);

    $this->post_args = $this->process_args($args);

    return $this->post('BMSetInventory', $this->post_args);
  } // end of button_set_inventory()
  
  public function button_get_inventory($hostedbuttonid)
  {
    $args = IncludeUtilities::array_flatten(func_get_args());
    if (!$hostedbuttonid) {
      throw new PayPalButtonAPIException("PayPalButtonAPI::button_get_inventory(): button id required");
    }
    array_unshift($args, 'HOSTEDBUTTONID');
    
    $this->post_args = $this->process_args($args);
    
    return $this->post('BMGetInventory', $this->post_args);
  } // end of button_get_inventory()
  
  public function dump($msg)
  {
    $msg = ($msg ? $msg . "\n" : '') . "PayPalButtonAPI:\n";
    $str = parent::dump($msg);
    if ($this->verbose) {
      if ($this->post_data) $str .= $this->post_data->dump(' All Post Data');
    } else {
      if ($this->post_args) $str .= $this->post_args->dump(' Post Args');
    }
    return $str;
  } // end of dump()
}

// end class definitions

// function definitions
// a handy function for flattening arrays which come in as arguments when
//  we need a variable number of arguments, but need to originate it as an array
if (function_exists('IncludeUtilities::array_flatten')) {
  function array_flatten($ar) {
    return IncludeUtilities::array_flatten($ar);
  }
} else {
  function array_flatten($ar)
  {
    $ret_ar = array();
    foreach ($ar as $elt) {
      if (is_array($elt)) {
        $ret_ar = array_merge($ret_ar, IncludeUtilities::array_flatten($elt));
      } else {
        $ret_ar[] = $elt;
      }
    }
    return $ret_ar;
  } // end of IncludeUtilities::array_flatten()
}

// end function definitions

?>
