<?php
echo "events.php is disabled\n";
return;

require_once('Event.php');
require_once('EventAttendee.php');

foreach (array('Event', 'EventAttendee') as $obj_name) {
  $obj = new $obj_name(Globals::$dbaccess);
  if (!Globals::$dbaccess->table_exists($obj->tablename)) {
    $tmp = AClass::get_class_instance($obj_name);
    $tmp->create_table(Globals::$dbaccess);
  }
}

// do your initialization
$attendee_data = array(
  array(    ),
  );
  
$event_data = array(
    array(),
  );

foreach (array(array('EventAttendee', $attendee_data), array('Event', $event_data)) as $row) {
  list($object_name, $object_data) = $row;

  foreach ($object_data as $tmp) {
    $obj = new $object_name(Globals::$dbaccess, $tmp);
    foreach ($tmp as $key => $val) {
      if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
        continue; // skip
      }
      $obj->$key = $val;
    }
    $obj->save();
    // $obj_ar[$tmp['key-name']] = $obj;
  }}
