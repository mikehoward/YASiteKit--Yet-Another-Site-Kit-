<?php
/*
#doc-start
h1. Event.php - Event object for Event Calendars

Created by Mike on 2011-02-16
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2011.
All Rights Reserved.

h2. Instantiation

h2. Attributes

* event_name - string - Unique Event Key
* title - string - Display Title
* category - category(event) - Event Category
* start_time - datetime - Start Time
* end_time - datetime - End Time
* location - join(Address.title) - Location
* cost - float - Cost to Attend
* early_bird_date  -  date - Early Bird Date
* early_bird_discount  -  float - Early Bird Discount (per cent)
* rsvp_req  -  enum(N,Y) - Rsvp Required
* max_attendees  -  int - Limit of Attendees
* current_rsvp  -  int - Current Yes RSVPs
* description  -  text - Description
* contact_name  -  varchar(255) - Contact Name
* contact_email  -  email - Contact Email

h2. Class Methods

* event_list($start_date, $end_date, $category = 'event') - returns an array of events
falling within the start and end dates and in the supplied category. Both _start_date_
and _end_date_ must be in the format "YYYY-MM-DD".

h2. Instance Methods

* rsvp($event_attendee, $reservation_date) - adds _$event_attendee_ to the attendee
list for _$this_ event.

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('Event', 'event_name', 
  array( // field definitions
    array('event_name', 'varchar(255)', 'Event Name'),
    array('event_owner', 'link(Account.userid)', 'Event Owner'),
    array('title', 'varchar(255)', 'Title'),
    array('category', 'category(event)', 'Event Category'),
    array('start_time', 'datetime', 'Start Time'),
    array('end_time', 'datetime', 'End Time'),
    array('location', 'join(Address.title)', 'Location'),
    array('cost', 'float', 'Cost to Attend'),
    array('early_bird_date', 'date', 'Early Bird Date'),
    array('early_bird_discount', 'float', 'Early Bird Discount (per cent)'),
    array('rsvp_req', 'enum(N,Y)', 'Rsvp Required'),
    array('max_attendees', 'int', 'Limit of Attendees'),
    array('current_rsvp', 'int', 'Current Yes RSVPs'),
    array('description', 'text', 'Description'),
    array('contact_name', 'varchar(255)', 'Contact Name'),
    array('contact_email', 'email', 'Contact Email'),
  ),
  array(// attribute definitions
    'event_name' => array('filter' => '[a-z][a-z_0-9]+')
      ));
// end global variables

// class definitions
class Event extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Event', $dbaccess, $attribute_values);
  } // end of __construct()

  public function event_list($start_date, $end_date, $category)
  {
    return $this->get_objects_where("start_time between '$start_date' and '$end_date' or end_time between '$start_date' and '$end_date'",
      'start_time');
  } // end of display_event()
  
  public function rsvp($event_attendee, $reservation_date)
  {
    // FIXME
  } // end of rsvp()
}


class EventManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Event', 'event_timestamp');
  } // end of __construct()
}
?>
