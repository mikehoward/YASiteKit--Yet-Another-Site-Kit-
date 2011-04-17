<?php
/*
#doc-start
h1. EventAttendee.php - Event Attendee tracking

Created by Mike on 2011-02-16
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2011.
All Rights Reserved.

h2. Instantiation

h2. Attributes

* event_attendee_id - ', 'int', 'EventAttendee Id
* event_name - ', 'join(Event.event_name)', 'Event Name
* reservation_date - ', 'date', 'Date of Reservation
* payment_date - ', 'date', 'Date of Payment
* paid - ', 'enum(N,Y)', 'Paid?
* payment_amount - ', 'float', 'Payment Amount
* payment_method - ', 'enum(Paypal,Check,Cash,Amex,Visa,Mastercard,Discover)', 'Payment Method
* email - ', 'email', 'Email Address
* address_id - ', 'join(Address.address_id)', 'Address


h2. Class Methods

None

h2. Instance Methods

None

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('EventAttendee', 'event_attendee_id', 
  array( // field definitions
    array('event_attendee_id', 'int', 'EventAttendee Id'),
    array('event_name', 'join(Event.event_name)', 'Event Name'),
    array('reservation_date', 'date', 'Date of Reservation'),
    array('payment_date', 'date', 'Date of Payment'),
    array('paid', 'enum(N,Y)', 'Paid?'),
    array('payment_amount', 'float', 'Payment Amount'),
    array('payment_method', 'enum(Paypal,Check,Cash,Amex,Visa,Mastercard,Discover)', 'Payment Method'),
    array('email', 'email', 'Email Address'),
    array('address_id', 'join(Address.address_id)', 'Address'),
  ),
  array(// attribute definitions
      'email' => 'encrypt',
      ));
// end global variables

// class definitions
class EventAttendee extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('EventAttendee', $dbaccess, $attribute_values);
  } // end of __construct()
}


class EventAttendeeManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'EventAttendee', 'event_attendee_id');
  } // end of __construct()
}
?>
