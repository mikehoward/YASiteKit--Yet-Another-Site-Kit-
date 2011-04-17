<?php
/*
#doc-start
h1. CurrencyCode.php - Currency Codes

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a hack to provide _select_ element access to Currency Codes and
to allow Currency Codes to be maintained via the Management Interface

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('CurrencyCode', 'currency_code', 
  array( // field definitions
    array('currency_code', 'char(3)', 'CurrencyCode'),
    array('country_name', 'varchar(255)', 'Country Name'),
  ),
  array(// attribute definitions
      ));
// end global variables

// class definitions
class CurrencyCode extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('CurrencyCode', $dbaccess, $attribute_values);
  } // end of __construct()
}


class CurrencyCodeManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'CurrencyCode', 'country_name');
  } // end of __construct()
}
?>
