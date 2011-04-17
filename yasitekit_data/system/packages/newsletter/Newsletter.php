<?php
/*
#doc-start
h1.  Newsletter.php - a newsletter - similar to an article - possibly identical

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


#end-doc
*/
// global variables
require_once('aclass.php');

$keys_list = array('letter_date');
$attribute_defs = array(
  array('letter_date', 'date', 'Newsletter Date'),
  array('headline', 'varchar(255)', 'Headline'),
  array('description', 'text', 'Description'),
  array('newsletter_body', 'text', 'Body'),
  );
$test_newsletter_values = array(
  'letter_date' => '2010-2-10',
  'headline' => 'Newsletter Title',
  'description' => 'sample newsletter',
  'newsletter_body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
  incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation
  ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in
  voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
  proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  );
AClass::define_class('Newsletter', $keys_list, $attribute_defs, 
    array(
      'letter_date' => 'public',
      'headline' => 'public',
      'description' => 'public',
      'newsletter_body' => 'public',
      ));
// end global variables

// class definitions
class Newsletter extends AnInstance {
  public function __construct($dbaccess, $attribute_values = NULL)
  {
    parent::__construct('Newsletter', $dbaccess, $attribute_values);
  } // end of __construct()
}

class NewsletterManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Newsletter', 'letter_date', array('orderby' => 'order by letter_date desc'));
  } // end of __construct()
}
// end class definitions
?>
