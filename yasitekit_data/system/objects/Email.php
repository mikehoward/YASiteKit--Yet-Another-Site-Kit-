<?php
/*
#doc-start
h1. Email.php - an Email record object

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. Instantiation

h2. Attributes

* email_id - int - Email id
* userid - link(Account.userid) - User ID
* email - email - Email Address
* title - varchar(255) - Title - user visible name for forms and display
* image - file(images/email_pic/{user_id}_{email_id},private) - Avatar - a photo or cartoon
* create_timestamp - datetime - Creation Timestamp

h2. Class Methods

None

h2. Instance Methods

Normal AnInstance methods

#end-doc
*/

// global variables
require_once('aclass.php');
require_once('Parameters.php');

AClass::define_class('Email', 'email_id', 
  array( // field definitions
    array('email_id', 'int', 'Email id'),
    array('userid', 'link(Account.userid)', 'User ID'),
    array('email', 'email', 'Email Address'),
    array('title', 'varchar(255)', 'Title'),
    array('image', 'file(images/email_pic/{user_id}_{email_id},private)', 'Avatar'),
    array('create_timestamp', 'datetime', 'Creation Timestamp'),
  ),
  array(// attribute definitions
      'email' => 'encrypt',
      'create_timestamp' => 'immutable',
      ));
// end global variables

// class definitions
class Email extends AnInstance {
    static $parameters = FALSE;
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Email', $dbaccess, $attribute_values);
    if (!Email::$parameters) {
      require_once('Parameters.php');
      Email::$parameters = new Parameters($this->dbaccess, 'Email');
      if (!isset(Email::$parameters->next_email_id)) {
        Email::$parameters->next_email_id = 1;
      }
    }
    $this->email_id = Email::$parameters->next_email_id;
  } // end of __construct()
  
  public function save()
  {
    if ($this->dirtyP() && !$this->create_timestamp) {
      $this->create_timestamp = new DateTime('now');
    }
    parent::save();
  } // end of save()
}


class EmailManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Email', 'email_id');
  } // end of __construct()
}
?>
