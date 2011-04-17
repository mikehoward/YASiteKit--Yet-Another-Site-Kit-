<?php
/*
#doc-start
h1. tax_authority_template.php - template to copy for YASiteKit AnInstance tax_authoritys

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a Bare Bones AnInstance tax_authority template.

To create a new tax_authority, copy this and hack.

Remember to replace:

* TaxAuthority with your tax_authority's tax_authority_id
* tax_authority with your tax_authority's lower case tax_authority_id

#end-doc
*/

// global variables
require_once('aclass.php');
ObjectInfo::do_require_once('Address.php');

AClass::define_class('TaxAuthority', 'tax_authority_id', 
  array( // field definitions
    array('tax_authority_id', 'int', 'TaxAuthority Id'),
    array('title', 'varchar(255)', 'Tax Authority Title'),
    array('description', 'varchar(255)', 'Written Description'),
    array('rate', 'float', 'Tax Rate'),
    array('tax_type', 'enum(Sales,VAT,Duty)', 'Tax Type'),
    array('authority_type', 'enum(Nation,State_Province,Region,City,Other)', 'Authority Type'),
    array('city', 'varchar(255)', 'City'),
    array('state_province', 'varchar(255)', 'State / Province / District'),
    array('region', 'varchar(255)', 'Region'),
    array('country_code', 'join(CountryCode.country_name)', 'Country Code'),
    array('postal_code_list', 'text', 'Postal Code List (Comma separate)'), // comma separated list of postal codes
  ),
  array(// attribute definitions
    'tax_authority_id' => array('readonly', 'default' => array('country_code' => 'US')),
      ));
// end global variables

// class definitions
class TaxAuthority extends AnInstance {
  static private $parameters = NULL;
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('TaxAuthority', $dbaccess, $attribute_values);
    // if (!$this->tax_authority_id) {
    //   $this->assign_join_value('country_code', array('country_code' => 'US'));
    //   $this->mark_saved();
    // }
  } // end of __construct()

  public function save()
  {
    if ($this->dirtyP() && !$this->address_id) {
      if (!TaxAuthority::$parameters) {
        require_once('Parameters.php');
        TaxAuthority::$parameters = new Parameters($this->dbaccess, 'TaxAuthority');
        if (!isset(TaxAuthority::$parameters->next_tax_authority_id)) {
          TaxAuthority::$parameters->next_tax_authority_id = 1;
        }
      }
      $this->address_id = TaxAuthority::$parameters->next_tax_authority_id;
      TaxAuthority::$parameters->next_tax_authority_id = TaxAuthority::$parameters->next_tax_authority_id + 1;
    }
    return parent::save();
  } // end of save()
}


class TaxAuthorityManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'TaxAuthority', 'title');
  } // end of __construct()
}
?>
