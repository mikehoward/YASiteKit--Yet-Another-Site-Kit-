<?php
/*

h3. Test Functions

* testReset() - resets test count and error count to zero
* testReport() - prints a two line summary - number of tests and number
of failures.

The following functions print a test result message and increment the counters.

* testTrue(message, value) - print Pass if _value_ is TRUE else Fail followed by
message.
* testFalse(message, value) - prints Pass if _value_ is FALSE, else Fail
* testNoDBError(message, $dbaccess) - prints Pass message if $dbaccess->errorP()
returns TRUE - indicating that the last database operating completed successfully.
Else Fail
* testDBError(message, $dbaccess) - reverses testNoDBError()
* testException(message, $code) - executes _$code_ using eval() inside a try ... catch
construct. Prints Pass if _$code_ generates an exception, otherwise Fail. Couple
of Gotchas:
** $code must be syntactically correct PHP - including semicolons
** $code must NOT include and php escapes (&lt;?php)
** $code must include 'global' directives if you need to access a global variable,
like: "global $dbaccess;$dbaccess->method();"
* testNoException(message, $code) - the reverse of testException(). Same considerations
apply.

Utilities

* test_helper(message, value) - does the actual work for most of the test result functions.
Use if you want to add a test so we keep all the message headers and counters in one place.
* ignore_exception() - an exception handler which does nothing. Useful if you have some
exception handling buried deep enough that a try ... catch ... be able to clean up
any undesired output. If you use it, follow with a _restore_exception_handler()_ as
soon as possible to avoid losing interesting error reports.

*/
set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
global $dbaccess;

echo "$dbaccess\n";

foreach (array('Package', 'RequestRouter', 'ObjectInfo', 'YATheme', 'YAThemeFiles') as $name) {
  require_once($name . '.php');
  $class_instance = AClass::get_class_instance($name);
  $class_instance->create_table($dbaccess);
  testNoDBError("Created table for $name", $dbaccess);
}

$test_data = array(
  array('Foo', 'foo'),
  array('AxBx', 'ax_bx'),
  array('ShoppingCartItem', 'shopping_cart_item'),
  // array('', ''),
);

foreach ($test_data as $row) {
  list($package, $dir) = $row;
  testTrue("IncludeUtilities::camel_to_words($package) == $dir", IncludeUtilities::camel_to_words($package) == $dir);
  testTrue("IncludeUtilities::words_to_camel($dir) == $package", IncludeUtilities::words_to_camel($dir) == $package);
  testTrue("IncludeUtilities::words_to_camel(IncludeUtilities::camel_to_words($package)) == $package", IncludeUtilities::words_to_camel(IncludeUtilities::camel_to_words($package)) == $package);
  testTrue("IncludeUtilities::camel_to_words(IncludeUtilities::words_to_camel($dir)) == $dir", IncludeUtilities::camel_to_words(IncludeUtilities::words_to_camel($dir)) == $dir);
}

$install_package_obj = Package::install($dbaccess, 'Event');

$event_package_obj = new Package(Globals::$dbaccess, 'Event');
testTrue("event_package_obj == $install_package_obj", $install_package_obj == $event_package_obj);
foreach (array(
        array('package_name', 'Event'),
        array('package_dir', 'event'),
        array('package_abs_path', Globals::$system_packages . DIRECTORY_SEPARATOR . 'event'),
        array('enabled', 'N'),
        array('required_packages', ''),
        array('required_objects', 'Account,Address,Category'),
        array('objects', 'Event,EventAttendee'),
      ) as $row) {
  list($attr, $expect) = $row;
  if (!testTrue("package->$attr == '$expect'", $event_package_obj->$attr == $expect)) {
    echo "Expected: $expect\n";
    echo "Received: {$event_package_obj->$attr}\n";
  }
}

$obj_info_data = array(
  'Event' => array(
    array('object_name', 'Event'),
    array('valid', 'Y'),
    array('path', Globals::$system_packages . DIRECTORY_SEPARATOR . 'event'. DIRECTORY_SEPARATOR . "Event.php"),
    array('source', 'system_package'),
    array('source_name', 'Event'),
    array('manageable', 'Y'),
    array('management_url', '/event_manage'),
  ),
  'EventAttendee' => array(
    array('object_name', 'EventAttendee'),
    array('valid', 'Y'),
    array('path', Globals::$system_packages . DIRECTORY_SEPARATOR . 'event' . DIRECTORY_SEPARATOR . "EventAttendee.php"),
    array('source', 'system_package'),
    array('source_name', 'Event'),
    array('manageable', 'Y'),
    array('management_url', '/event_manage_attendee/event_attendee_id'),
  ),
);

foreach (array('Event', 'EventAttendee') as $object_name) {
  $obj_info = new ObjectInfo($dbaccess, $object_name);
  foreach ($obj_info_data[$object_name] as $row) {
    list($attr, $expect) = $row;
    if (!testTrue("obj_info->$attr == '$expect'", $obj_info->$attr == $expect)) {
      echo "Expected: $expect\n";
      echo "Received: {$obj_info->$attr}\n";
    }
  }
}

$theme_obj = new YATheme($dbaccess, 'DisplayEvent.tpl');
$now = new DateTime();
foreach (array(
  array('file_name', 'DisplayEvent.tpl'),
  array('file_path', Globals::$system_packages . DIRECTORY_SEPARATOR . 'event' . DIRECTORY_SEPARATOR . 'DisplayEvent.tpl'),
  array('file_exists', 'Y'),
  array('refresh_timestamp', $now->format('c')),
  array('access_flag', 'Public OK'),
  array('php_path', 'php'),
  // array('', ''),
) as $row) {
  list($attr, $expect) = $row;
  if ($theme_obj->$attr instanceof DateTime) {
    if (!testTrue("theme_obj->$attr == '$expect'", $theme_obj->$attr->format('c') == $expect)) {
      echo "Expected: $expect\n";
      echo "Received: {$theme_obj->$attr}\n";
    }
  } else {
    if (!testTrue("theme_obj->$attr == '$expect'", $theme_obj->$attr == $expect)) {
      echo "Expected: $expect\n";
      echo "Received: {$theme_obj->$attr}\n";
    }
  }
}
// echo $theme_obj->dump();

testReport();