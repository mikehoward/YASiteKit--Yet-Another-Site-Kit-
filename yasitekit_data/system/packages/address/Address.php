<?php
/*
#doc-start
h1. Address.php - Address Object

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a generic address object. It can be attached as necessary to various things using the
_address_id_ field

h2. Instantiation

Typically:

$foo = new Address($dbaccess, address_id);

h2. Attributes

* address_id - int  - integer key into address table
* addressee -  string - name of recipient at address
* address1 -  string - first line of address field
* address2 -  string - second line of address field - if needed
* city - string  - City
* state_province - string  - State, Province, or District name
* postal - string  - Postal code
* country_code - char(2)  - Two character Country Code. See _select_country_code()_ for a list.
* tax_authorities - text - serialized array of tax authorities

h2. Class Methods

* select_country_code(name, cc == NULL) - returns a _select_ element with name attribute _name_. If
_cc_ is defined and a legitemate country code, then that is the selected code.

h2. Instance Methods



#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('Address', 'address_id', 
  array( // field definitions
    array('address_id', 'int', 'Address Number'),
    array('address_name', 'varchar(255)', 'Address Name'),
    array('addressee', 'varchar(255)', 'Addressee'),
    array('address1', 'varchar(255)', 'Address Line 1'),
    array('address2', 'varchar(255)', 'Address Line 2'),
    array('city', 'varchar(255)', 'City'),
    array('state_province', 'varchar(255)', 'State/Province'),
    array('postal', 'varchar(255)', 'Postal Code'),
    array('country_code', 'link(CountryCode.country_name)', 'Country Code'),
    array('tax_authorities', 'text', 'Tax Authority List'), // serialized array of tax authority objects keys
  ),
  array(// attribute definitions
    'address_id' => array('readonly'),
    'tax_authorities' => array('invisible'),
    'country_code' => array('default' => 'US'),
    ));
// end global variables

// class definitions
class Address extends AnInstance {
  private static $parameters = NULL;
  
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Address', $dbaccess, $attribute_values);
  } // end of __construct()
  
  public function render($top = NULL, $bottom = NULL)
  {
    $str = "<ul class=\"address\">\n";
    if ($top) {
      $str .= $top;
    }
    $str .= "  <li>$this->addressee</li>\n";
    $str .= "  <li>$this->address1</li>\n";
    if ($this->address2) {
      $str .= "  <li>$this->address2</li>\n";
    }
    $str .= "  <li>{$this->city}, {$this->state_province} {$this->postal}</li>\n";
    $str .= "  <li>{$this->country_code}</li>\n";
    if ($bottom) {
      $str .= $bottom;
    }
    $str .= "</ul>\n";
    return $str;
  } // end of render()
  
  public function save()
  {
echo $this->dump('Address::save()');
debug_print_backtrace();

    if ($this->dirtyP() && !$this->address_id) {
      if (!Address::$parameters) {
        require_once('Parameters.php');
        Address::$parameters = new Parameters($this->dbaccess, 'Address');
        if (!isset(Address::$parameters->next_address_id)) {
          Address::$parameters->next_address_id = 1;
        }
      }
      $this->address_id = Address::$parameters->next_address_id;
      Address::$parameters->next_address_id = Address::$parameters->next_address_id + 1;
    }
    return parent::save();
  } // end of save()
}


class AddressManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Address', 'address_id');
  } // end of __construct()
}
?>
