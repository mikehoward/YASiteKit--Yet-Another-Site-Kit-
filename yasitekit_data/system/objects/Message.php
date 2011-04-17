<?php
/*
#doc-start
h1.  Message.php - an Email Message

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Message objects encasulate an e-mail message form

h2. Instantiation

pre. $msg = new Message($dbaccess, args);

where args can be:

* a string - which is taken as _email_ address
* NULL - in which case an empty, undated message is created
* an array - in which case the key _email_ must be defined

h2. Attributes

* email - string - required - email address of sender. This must be a valid return address as well
* timestamp - datetime - required and auto-generated -
this is a read-only attribute used to time-tag when the message
was generated
* name - string - required - name of correspondent
* ordernumber - string - optional - used if message refers to an order
* subject - string - required - it's the subject
* message - text - optional - what it's all about

h2. Class Methods

None

h2. Instance Methods

* form($top_half = NULL, $bottom_half = NULL, $actions = array('Send', 'Cancel')) - extends
the AnInstance _form()_ function slightly to include a reCAPTCHA guard
* mail($to_email) - formats an e-mail message from _this_ and sends it to _$to_email_.

#end-doc
*/

// global variables
require_once('aclass.php');

$test_message_values = array(
  'email' => 'email-address',
  'timestamp' => '12-9-2010 12:51:13',
  'name' => 'My Name',
  'ordernumber' => 'A123456',
  'subject' => 'This is a subject',
  'message' => 'A message of little or no consequence',
  );
$message_class = AClass::define_class('Message', 'message_id',
  array(
    array('message_id', 'int', 'Message Id'),
    array('recipient', 'enum(info,support,webmaster)', 'Recipient'),
    array('email', 'email', 'Email Address'),
    array('timestamp', 'datetime', 'Access Timestamp'),
    array('name', 'varchar(255)', 'Full Name'),
    array('ordernumber', 'varchar(255)', 'Order Number'),
    array('subject', 'varchar(255)', 'Subject'),
    array('message', 'text', 'Message'),),
  array(
    'message_id' => 'immutable',
    'email' => array('form_classes' => 'first-focus', 'encrypt', 'required'),
    'timestamp' => 'readonly',
    'name' => 'required',
    'subject' => 'required',
    ));
// end global variables

// class definitions
class Message extends AnInstance {
  static $parameters = FALSE;
  public function __construct($dbaccess, $attribute_values = array())
  {
    if (is_string($attribute_values)) {
      $attribute_values = array('message_id' => intval($attribute_values));
    } elseif ($attribute_values && !is_array($attribute_values)) {
      throw new MessageException("Message::__construct($dbaccess, ...): Illegal attribute values type");
    }
    if (!array_key_exists('timestamp', $attribute_values)) {
      $attribute_values['timestamp'] = new DateTime('now');
    }
    parent::__construct('Message', $dbaccess, $attribute_values);

    if (!Message::$parameters) {
      require_once('Parameters.php');
      Message::$parameters = new Parameters($this->dbaccess, 'Message');
      if (!isset(Message::$parameters->next_message_id)) {
        Message::$parameters->next_message_id = 1;
      }
    }
    $this->message_id = Message::$parameters->next_message_id;
    Message::$parameters->next_message_id += 1;
  } // end of __construct()
  
  public function form($form_action = NULL, $top_half = NULL, $bottom_half = NULL, $actions = array('Send', 'Cancel'))
  {
    $bottom_half = $bottom_half ? $bottom_half . "\n" : '';
    $bottom_half .= "<li style=\"clear:both;\"><span style=\"float:right;\">";

    require_once('ReCaptcha.php');
    $recaptcha = new ReCaptcha(Globals::$recaptcha_domain, Globals::$recaptcha_pub_key,
      Globals::$recaptcha_priv_key, Globals::$recaptcha_theme, ReCaptcha::HTTP);
    if ($recaptcha->valid) {
      if (!$recaptcha->verify(Globals::$rc)) {
        if ($recaptcha->error_code) $bottom_half .=  "Please Try the Curvy Letters Again";
      }
      $bottom_half .= $recaptcha->render();
      $bottom_half .= "</span><p>SPAM Stopper!!! Please Fill in this Form</p></li>\n";
    } else {
      $bottom_half .= "</span></li>\n";
    }

    return parent::form($form_action, $top_half, $bottom_half, $actions);
  } // end of form()
  
  public function mail()
  {
    $ajoin = AJoin::get_ajoin(Globals::$dbaccess, 'Message', 'CookieTrack');
    $ajoin->add_to_join($this, Globals::$cookie_track);
    switch ($this->recipient) {
      case 'info':
        $to_email = Globals::$info_email;
        break;
      case 'support':
        $to_email = Globals::$support_email;
        break;
      case 'webmaster':
        $to_email = Globals::$webmaster;
        break;
    }
    return mail($to_email, $this->subject, $this->message, "From: $this->email");
  } // end of mail()
}
// end class definitions
?>
