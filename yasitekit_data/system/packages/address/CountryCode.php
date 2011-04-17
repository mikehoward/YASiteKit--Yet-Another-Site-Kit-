<?php
/*
#doc-start
h1. CountryCode.php - Country Codes

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a hack to provide _select_ element access to Country Codes and
to allow Country Codes to be maintained via the Management Interface

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('CountryCode', 'country_code', 
  array( // field definitions
    array('country_code', 'char(2)', 'CountryCode'),
    array('country_name', 'varchar(255)', 'Country Name'),
  ),
  NULL);
// end global variables

// class definitions
class CountryCode extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('CountryCode', $dbaccess, $attribute_values);
  } // end of __construct()

  public function __toString()
  {
    return $this->country_name;
  } // end of __toString()
}


class CountryCodeManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'CountryCode', 'country_name');
  } // end of __construct()
}
?>
