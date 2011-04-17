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
    'Link') as $object_name) {
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
$test_data = array(
  array(
    // 'link_id' => 0,
    'title' => 'Link Test',
    'uri' => '/sample/parm1/value1',
    'id' => 'id-name',
    'classes' => 'class1 class1',
    'follow' => 'Y',
    'lastmod' => '1234-12-03',
    'changefreq' => 'always',
    'priority' => '0.4'
  ),
  array(
    // 'link_id' => 0,
    'title' => 'Link Test 2',
    'uri' => '/sample/parm1/value2',
    'id' => 'id-name2',
    'classes' => 'class1 class3',
    'follow' => 'N',
    'lastmod' => '1234-12-03',
    'changefreq' => 'never',
    'priority' => '0.4'
  ),
);

foreach ($test_data as $attr_ar) {
  $link = new Link($dbaccess, $attr_ar);
  $link->save();
  echo $link->dump('new link');
  echo "\n{$link->link()}\n\n";
  echo "\n{$link->site_map_entry()}\n";
}

require_once('ObjectInfo.php');
$obj_info = new ObjectInfo($dbaccess, 'Link');

echo Globals::dump();
