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
require('test_functions.php');
// require_once('includes.php');
require_once('dbaccess.php');
global $dbaccess;

echo "$dbaccess\n";

require_once('RequestRouter.php');

global $dbaccess;

echo "$dbaccess\n";
$class_instance = AClass::get_class_instance('RequestRouter');
$class_instance->create_table($dbaccess);
// echo $class_instance->dump();
testNoDBError("Created Table for RequestRouter", $dbaccess);

$test_data = array(
    'routing_key' => 'article_page',
    'resource_name' => 'Articles',
    'script_name' => 'DisplayArticle.tpl',
    'path_map' => 'article/page',
    'required_authority' => array('ANY'),
    'path_map' => 'article/page',
  );
$obj = new RequestRouter($dbaccess, $test_data);
// echo $obj->dump();
testTrue('$obj->save()', $obj->save());

foreach ($test_data as $key => $expect) {
  testTrue("obj->$key == '$expect'", $obj->$key == $expect);
}

$tmp = new RequestRouter($dbaccess, $test_data['routing_key']);
$cmp = new AClassCmp(implode(',', $obj->attribute_names));
testTrue('$obj == retreived obj', $obj == $tmp);
if (!testTrue('cmp($obj, retrieved obj) == 0', $cmp($obj, $tmp) == 0)) {
  echo $obj->dump('obj - original object');
  echo $tmp->dump('tmp - retreived object');
}

require_once('request_cleaner.php');
Globals::$rc = new RequestCleaner();
testTrue('testing map_pathinfo()', $obj->map_pathinfo('foo/bar') == array('article' => 'foo', 'page' => 'bar'));
testTrue('issest(router->page)', isset($obj->page));
testTrue('testing router->article == foo', $obj->article == 'foo');
testTrue('testing router->page == bar', $obj->page == 'bar');

testTrue('testing map_pathinfo()', $obj->map_pathinfo('foo') == array('article' => 'foo', 'page' => FALSE));
testTrue('isset(router->article)', isset($obj->article));
testTrue('isset(router->page)', isset($obj->page));
testTrue('testing router->article == foo', $obj->article == 'foo');
testTrue('testing router->page === FALSE', $obj->page === FALSE);
testFalse('router->page is FALSE', $obj->page);

$mgr_obj = new RequestRouterManager($dbaccess);

ob_start();
$mgr_obj->render_form(Globals::$rc);
$tmp = ob_get_clean();
testTrue('Management form renders something', $tmp);

testReport();
