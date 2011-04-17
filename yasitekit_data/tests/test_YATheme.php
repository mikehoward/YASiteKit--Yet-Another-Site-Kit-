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
set_include_path('..' . PATH_SEPARATOR
  . './yatheme_tests' . PATH_SEPARATOR
  . './yatheme_templates' . PATH_SEPARATOR
  . get_include_path());
require_once('config.php');
require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
global $dbaccess;

// echo "$dbaccess\n";
require_once('YaTheme.php');

foreach (array('YAThemeFiles', 'YATheme') as $cls) {
  $cls_instance = AClass::get_class_instance($cls);
  $cls_instance->create_table($dbaccess);
}

function test_expected($test_str, $expected_path) {
  $expected_str = file_get_contents($expected_path);
  return $expected_str == $test_str;
} // end of test_expected()

$help_msg =<<<EOT
$progname [help | options] test.tpl ...

Default action is to test against expected results file or render
to screen, if expected results file does not exist.

Runs as many tests as are specified.

Test file names are stripped to basename so tests can be anywhere, but
expected results are in 'yatheme_tests/expected_results/'

Option                 Meaning
-v/--verbose           display processed test file. Do not test or create expected results
                       Use for development
-p/--process           process resulting rendered file and display to stdout
-r/--rebuild-results   create expected results file(s). Use after it passes
-c/--caching <on|off|compress>    set's caching
-D/--diag              Dump object when finished
EOT;

// control flags
$verbose = FALSE;
$process = FALSE;
$caching = 'compress';
$diagnostics = FALSE;
$rebuild_results = FALSE;
$test_count = 0;
$pass_count = 0;
$fail_count = 0;

while ($argv) {
  $arg = array_shift($argv);
  switch ($arg) {
    case 'help': echo $help_msg . "\n" ; exit(0);
    case '-D': case '--diag': $diagnostics = TRUE ; break;
    case '-r': case '--rebuild-results': $rebuild_results = TRUE; break;
    case '-v': case '--verbose': $verbose = TRUE; break;
    case '-p': case '--process': $process = TRUE ; break;
    case '-c': case '--caching': $caching = array_shift($argv); break;
    default: continue 2;
  }
}

if (!$argv) {
  $argv = array_map(create_function('$x', 'return "yatheme_tests/" . $x;'),
    $x = array_filter(scandir('yatheme_tests'), create_function('$x', 'return preg_match(\'/\.tpl$/\', $x);')));
}

while (($filename = basename(array_shift($argv)))) {
      echo "\nTesting {$filename}\n";
      $yatheme_obj = new YATheme($dbaccess, $filename);
      $yatheme_obj->caching = $caching;
      if ($yatheme_obj->file_exists == 'N') {
        echo "Test file $filename does not exist\n";
      } else {
        $expected_path = "yatheme_tests/expected_results/$filename";
        if ($verbose) {
          echo $yatheme_obj->render();
        } elseif ($process) {
          echo $yatheme_obj->render();
        } elseif ($rebuild_results) {
          file_put_contents($expected_path, $yatheme_obj->render());
        } elseif (file_exists($expected_path)) {
          echo "Comparing $filename to expected\n";
          $test_count += 1;
          if (test_expected($yatheme_obj->render(), $expected_path)) {
            echo "Passed\n";
            $pass_count += 1;
          } else {
            echo "!!!!!! Failed\n";
            $fail_count += 1;
          }
        } else {
          echo "\nCannot Test $filename: No expected results\n";
          echo $yatheme_obj->render();
        }
      }
      if ($diagnostics) {
        echo $yatheme_obj->dump('Diagnostic Dump');
      }
}

echo "\n$test_count Tests: Passed: $pass_count, Failed: $fail_count\n";