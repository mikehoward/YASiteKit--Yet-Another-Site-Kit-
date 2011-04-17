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


foreach (array(
    // 'CountryCode',
    // 'CurrencyCode',
    'ObjectInfo') as $object_name) {
  require_once($object_name . ".php");
  $class_instance = AClass::get_class_instance($object_name);
  $class_instance->create_table($dbaccess);
  testNoDBError("Created table for $object_name", $dbaccess);
}

// $incl_save = get_include_path();
// set_include_path( '..' . DIRECTORY_SEPARATOR . 'installation' . DIRECTORY_SEPARATOR
//   . 'sub_systems' . PATH_SEPARATOR . $incl_save);
// include('codes_for_currency_country.php');
// set_include_path($incl_save);

$cmp = new AClassCmp('object_name,valid,path,source,source_name,manageable,management_url');
foreach (array('Account', 'Article', 'ArticleGroup') as $object_name) {
  echo "\nTesting using object $object_name\n";
  $obj = new ObjectInfo($dbaccess, $object_name);
  foreach (array(
        array('object_name', $object_name),
        array('valid', 'Y'),
        array('path', Globals::$system_objects . DIRECTORY_SEPARATOR . "{$object_name}.php"),
        array('source', 'system'),
        array('source_name', ''),
        array('manageable', 'N'),
        array('management_url', ''),
      ) as $row) {
    list($name, $expect) = $row;
    testTrue("obj->$name == '$expect'", $obj->$name == $expect);
  }
  testTrue("map entry matches obj", ObjectInfo::$object_map[$object_name] == $obj);
  testTrue("map entry matches new obj", $cmp(ObjectInfo::$object_map[$object_name], ($tmp = new ObjectInfo($dbaccess, $object_name))) == 0);

  if (!class_exists($object_name)) {
    testTrue("ObjectInfo::do_require_once($object_name) works", ObjectInfo::do_require_once($object_name));
  }
  testTrue("class_exists($object_name)", class_exists($object_name));
}

testTrue("3 entries in object_map", count(ObjectInfo::$object_map) == 3);

testReport();