<?php
$required_packages = '';
$required_objects = 'Account,Address,Category';

$install_data = array(
  array(
    'routing_key' => 'event_display',
    'resource_name' => 'Event Details',
    'script_name' => 'DisplayEvent.tpl',
    'path_map' => 'event_name/start_date/start_time',
    'required_authority' => 'A,M,V,S,X',
  ),
  array(
    'routing_key' => 'event_list',
    'resource_name' => 'List Events',
    'script_name' => 'ListEvents.tpl',
    'path_map' => 'start_date/start_time',
    'required_authority' => 'A,M,V,S,X',
  ),
  array(
    'routing_key' => 'event_calender',
    'resource_name' => 'Event Details',
    'script_name' => 'DisplayEventCalendar.tpl',
    'path_map' => 'month/year',
    'required_authority' => 'A,M,V,S,X',
  ),
);

$management = array(
  array(
    'object_names' => 'Event',
    'routing_key' => 'event_manage',
    'resource_name' => 'Event Management',
    'script_name' => 'ManageEvents.tpl',
    'path_map' => '',
    'required_authority' => 'A,M,V,S,X',
  ),
  array(
    'object_names' => 'EventAttendee',
    'routing_key' => 'event_manage_attendee',
    'resource_name' => 'Event Atteendee Management',
    'script_name' => 'ManageEventAttendee.tpl',
    'path_map' => 'event_attendee_id',
    'required_authority' => 'A,M,V,S,X',
  ),
);