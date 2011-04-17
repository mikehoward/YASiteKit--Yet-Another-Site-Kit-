<?php
/*
#doc-start
h1.  DownloadAuthorization.php - Manages Download Authorization for Downloadable For-Sale Items

Created by  on 2010-04-27.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

A *DownloadAuthorization* manages the right to access some resource or product
a specified number of times within a specific time frame.

h2. Hacking Instructions

About all there is to change without serious work is the expiration time and number of
uses. These are set as Class Constants in the DownloadAuthorization class defination.

* MAX_USES - 3
* MAX_DAYS - 5 days - but the value is in seconds, so it's 5 * 86400

h2. Instantiation

To create a new DownloadAuthorization, simply:

pre. $foo = new DownloadAuthorization($dbaccess);
$foo->email = 'email address'
$foo->product_key = 'product key'

If you need to change the number of uses or expiration date, from the defaults,
then:

pre. $foo->add_uses(2);
$foo->extend_expiration(10);

h2. Attributes

* auth_number - string - immutable - random hex digit string created automatically
* email - string - required - email address of customer receiving the authorization
* product_key - string - required and immutable - must be supplied or set programatically
* expires_at - DateTime - required - initialized automatically and can be changed programatically
* uses_left - int - required - initialized to 3. May be changed through management interface
or programatically.
* expired- Y or N - required and readonly- managed automatically - 

h2. Class Methods

None

h2. Instance Methods

* use_once() - checks to see if the DownloadAuthorization instance can be used. Returns
TRUE if it can be. Else FALSE if
** the number of uses left are 0
** it has expired
* expire() - expires the authority.
* add_uses(uses_to_add = 1) - increments the number of _uses_left_ by _uses_to_add_.
NOTE: _uses_to_add_ can be 0 or negative - nobody checks. If _uses_left_ becomes
0 or less, this thing calls _expire()_.
* extend_expiration($days = 1) - extends the number of days until expiration by
_days_ from now. If _$days_ <= 0, then this calls _expire()_.

#end-doc
*/
// global variables
require_once('aclass.php');
require_once('Parameters.php');

$test_rma_values = array(
  'auth_number' =>200,
  'product_key' => 'foo',
  'expires_at' => new DateTime('now'),
  'expired' => 'N',
  'uses_left' => 2,
  );
AClass::define_class('DownloadAuthorization', 'auth_number', 
  array( // field definitions
    array('auth_number', 'char(32)', 'Authorization Number'),
    array('email', 'email', 'Email Address of User'),
    array('product_key', 'varchar(255)', 'Product Key'),
    array('expires_at', 'datetime', 'Expiration Date'),
    array('expired', 'enum(N,Y)', 'Expired'),
    array('uses_left', 'int', 'Uses Left'),
  ),
  array(
    'email' => array('encrypt', 'required'),
    'product_key' => array('immutable', 'required'),
    'expires_at' => 'immutable',
    'expired' => array('readonly', 'default' => 'N'),
    ));
// end global variables

// class definitions
class DownloadAuthorization extends AnInstance {
  const MAX_USES = 3;
  const MAX_DAYS = 5;
  static public $parameters_obj = NULL;
  
  public function __construct($dbaccess, $attribute_values = array())
  {
    if (!DownloadAuthorization::$parameters_obj) {
      DownloadAuthorization::$parameters_obj = new Parameters($dbaccess, 'DownloadAuthorization');
      if (!isset(DownloadAuthorization::$parameters_obj->max_uses)) {
        DownloadAuthorization::$parameters_obj->max_uses = DownloadAuthorization::MAX_USES;
        DownloadAuthorization::$parameters_obj->max_days = DownloadAuthorization::MAX_DAYS;
      }
    }
    parent::__construct('DownloadAuthorization', $dbaccess, $attribute_values);
    if (!isset($this->auth_number)) {
      // initialize this in case we are going to use it.
      $this->auth_number = strtoupper(md5(chr(rand(32, 122))  . chr(rand(32, 122))
          . time() . chr(rand(32, 122)) . chr(rand(32, 122))));
      $this->uses_left = DownloadAuthorization::$parameters_obj->max_uses;
      $expires_in_seconds = DownloadAuthorization::$parameters_obj->max_days * 86400;
      $this->expires_at = new DateTime(strftime("%c", time() + $expires_in_seconds));
      $this->mark_saved();
    }
  } // end of __construct()
  
  public function use_once()
  {
    if ($this->expired == 'Y')
      return FALSE;
    if ($this->uses_left <= 0 || $this->expires_at < new DateTime('now')) {
      $this->expired = 'Y';
      $this->save();
      return FALSE;
    }
    $this->uses_left -= 1;
    $this->save();
    return TRUE;
  } // end of use()
  
  public function expire()
  {
    $this->uses_left = 0;
    $this->expired = 'Y';
    $this->expires_at = new DateTime('1900-01-01');
  } // end of expire()
  
  public function add_uses($uses_to_add = 1)
  {
    $this->uses_left += $uses_to_add;
    if ($this->uses_left <= 0) {
      $this->expire();
    }
    if ($this->expires_at > new DateTime('now')) {
      $this->expired = 'N';
    }
    $this->save();
  } // end of add_uses()
  
  public function extend_expiration($days = 1)
  {
    if ($days <= 0) {
      $this->expire();
    } else {
      $expires_in_seconds = 86400 * $days;
      $this->expires_at = new DateTime(strftime("%c", time() + $expires_in_seconds));
      $this->expired = 'N';
      $this->save();
    }
  } // end of extend_expiration()
  
  public function dump($msg = '')
  {
    $str = parent::dump($msg);
    $str .= " default max uses: " . DownloadAuthorization::$parameters_obj->max_uses . "\n";
    $str .= " default max days: " . DownloadAuthorization::$parameters_obj->max_days . "\n";
    return $str;
  } // end of dump()
}


class DownloadAuthorizationManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'DownloadAuthorization', 'auth_number');
  } // end of __construct()
}
?>
