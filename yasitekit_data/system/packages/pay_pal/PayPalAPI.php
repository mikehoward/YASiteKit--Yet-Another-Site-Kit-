<?php
/*
#doc-start
h1.  PayPalAPI - Base object implementing the PayPal Name-Value-Pair API

Created by  on 2010-03-26.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

The *PayPalAPI* object implements an incredibly easy to use interface
to paypal.com's Name-Value-Pair API.


* PayPalAPI(api_username, api_password, api_signature, live = FALSE) -
creates a raw PayPal API object object. It provides the basic connection
and diagnostic functions.

h2. PayPalAPI Class

$paypalapi = new PayPalAPI(api_username, api_password, api_signature, live = FALSE)
creates a new instance. If _live_ is TRUE, then the connections will be made
to the real Paypal site. Otherwise, connections go to the sandbox

h2. Attributes

* api_password - string  - API password - supplied by PayPal
* api_signature - string  - API signature hash - supplied by PayPal
* api_username - string  - API user name - supplied by PayPal
* endpoint - string - either api-3t.paypal.com or api-3t.sandbox.paypal.com
* live - boolean - TRUE or FALSE. If TRUE, then connections go to the live site
* verbose - boolean - not implemented


h2. Class Methods

h2. Instance Methods

* post(method, args-array) - writes to the PayPal API. Returns a fully parsed
query result as an "ACurlData":/doc.d/system-includes/acurl.html#acurldata object. 
** method is the value of the METHOD variable
** args-array is an associative array where the keys are PayPal API and HTML
variable names _in lower case_ and the values are the values of those variables.
* dump(msg = NULL) - returns a string which displays all kinds of stuff.
If _msg_ is not NULL, then it is prepended to the string.

#end-doc
*/

// global variables
require_once('acurl.php');

// end global variables

// class definitions
class PayPalAPIException extends Exception {}

class PayPalAPI {
  const LIVE_ENDPOINT = 'https://api-3t.paypal.com/nvp';
  const SANDBOX_ENDPOINT = 'https://api-3t.sandbox.paypal.com/nvp';
  const VERSION = '51.0';
  
  private static $attribute_names = array(
    'api_password',
    'api_signature',
    'api_username',
    'endpoint',
    'live',
    'post_data',
    'verbose',
    );
  
  private $live = FALSE;
  private $api_username = NULL;
  private $api_password = NULL;
  private $api_signature = NULL;
  private $endpoint = NULL;
  private $verbose = FALSE;
  private $post_data = NULL;
  
  public function __construct($api_username, $api_password, $api_signature, $live = FALSE)
  {
    $this->live = $live;
    $this->api_username = $api_username;
    $this->api_password = $api_password;
    $this->api_signature = $api_signature;
    $this->endpoint = $live ? PayPalAPI::LIVE_ENDPOINT : PayPalAPI::SANDBOX_ENDPOINT;
    // create an ACurl object with default agent name and using HTTPS
    $this->acurl = new ACurl($this->endpoint, NULL, TRUE);
  } // end of __construct()
  
  public function __toString()
  {
    return "PayPalAPI($this->api_username, $this->api_password, $this->api_signature, $this->endpoint)";
  } // end of __toString()
  
  public function __get($name)
  {
    if (in_array($name, PayPalAPI::$attribute_names)) {
      return $this->$name;
    } else {
      throw new PayPalAPIException("PayPalAPI::__get($name): unknown/illegal attribute name: '$name'");
    }
  } // end of __get()

  public function post($method_name, $args = NULL)
  {
    $this->post_data = new ACurlData(
      'upper',
      'USER', $this->api_username,
      'PWD', $this->api_password,
      'SIGNATURE', $this->api_signature,
      'VERSION', PayPalAPI::VERSION,
      'METHOD', $method_name
      );
    if ($args instanceof ACurlData) {
      $this->post_data->merge($args);
    } elseif (is_array($args)) {
      $this->post_data->parse_array($args);
    } elseif (is_string($args)) {
      $tmp = new ACurlData('upper');
      $tmp->parse_query_string($args);
      $this->post_data->merge($tmp);
    } elseif ($args) {
      throw new PayPalAPIException("PayPalAPI::post($method_name, args): Illegal args type");
    }
    $rsp = $this->acurl->post_data($this->endpoint, $this->post_data->asString());

    // parse result into an ACurlData object
    $tmp = new ACurlData('upper');
    // parameter 2 = TRUE to apply urldecode() to returned string
    $tmp->parse_query_string($rsp, TRUE);
    return $tmp;
  } // end of post()
  
  public function dump($msg)
  {
    $str = $msg ? $msg : '';
    $str .= 'PayPalAPI Dump\n';
    foreach (PayPalAPI::$attribute_names as $attr) {
      $str .= "  $attr: {$this->$attr}\n";
    }
    return $str;
  } // end of dump()
}

?>
