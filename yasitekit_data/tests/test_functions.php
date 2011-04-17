<?php
/*
#doc-start
h1.  test_functions.php - Functions to make unit testing easier

Created by  on 2010-06-19.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved. Licensed under the terms of the GNU Lesser
GNUL License, version 3.  See http://www.gnu.org/licenses/ for details.

bq. THIS SOFTWARE HAS NO WARRANTEE OR REPRESENTATION FOR FITNESS OF PURPOSE.
USE AT YOUR OWN RISK.

This module defines some functions to use with testing. This is really a
simpler version of a  unit testing framework - without all the extra
stuff.

h2. How to Use It

# require_once('test_functions.php');
# if you're testing variants in a loop, you probably want to call testReset()
at the top of the loop to reset the test and error counters
# do your tests - picking test functions to report and count tests and errors.
# at the end (or bottom of the loop or wherevery you want), call testReport()
to get a quick summary.

h2. Reference

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

#end-doc
*/

require_once('includes.php');

// function definitions
function ignore_exception($exception)
{
  return;
}

function test_helper($msg, $value)
{
  global $error_count, $test_count, $failure_messages;
  
  $test_count += 1;
  if ($value) {
    echo "PASSED $msg\n";
    return TRUE;
  } else {
    $error_count += 1;
    echo ">>>FAILED $msg\n";
    $failure_messages[] = $msg;
    return FALSE;
  }
}
function testTrue($msg, $value)
{
  return test_helper("TRUE Test: $msg", $value);
} // end of testTRUE()

function testFalse($msg, $value)
{
  return test_helper("FALSE Test: $msg", !$value);
}

function testNoDBError($msg, $dbaccess)
{
  return test_helper("No DB Error: $msg", !$dbaccess->errorP());
}

function testDBError($msg, $dbaccess)
{
  return test_helper("DB Error: $msg", $dbaccess->errorP());
}

// evals '$code' in a try-catch loop. Don't forget the semi-colons and make sure not
//   to include php escapes. To access global variables, prefix the code with 'global $foo;'
function testException($msg, $code)
{
  global $error_count, $test_count, $failure_messages;

  $test_count += 1;
  try {
    eval($code);
    echo "FAILED Exception Test: $msg\n";
    $error_count += 1;
    $failure_messages[] = $msg;
    return FALSE;
  } catch (Exception $e) {
    echo "PASSED Exception Test: $msg\n";
    return TRUE;
  }
} // end of testException()

function testNoException($msg, $code)
{
  global $error_count, $test_count, $failure_messages;

  $test_count += 1;
  try {
    eval($code);
    echo "PASSED No Exception Test: $msg\n";
    $failure_messages[] = $msg;
    return TRUE;
  } catch (Exception $e) {
    echo "FAILED No Exception Test: $msg - $e\n";
    $error_count += 1;
    return FALSE;
  }
} // end of testException()


function testReset()
{
  global $error_count, $test_count, $failure_messages;

  $error_count = 0;
  $test_count = 0;
  $failure_messages = array();
}

function testReport()
{
  global $error_count, $test_count, $failure_messages;
  
  echo "\nPerformed $test_count Tests\n";
  echo $error_count ? "FAILED $error_count Tests\n" : "PASSED All Tests\n";
  foreach ($failure_messages as $msg) {
    echo "FAILURE: $msg\n";
  }
}

// end function definitions

// initial processing of POST data
testReset();

?>
