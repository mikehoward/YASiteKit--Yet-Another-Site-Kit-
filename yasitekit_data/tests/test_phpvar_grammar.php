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
set_include_path('..' . PATH_SEPARATOR . './yatparse_tests' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
// require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
// global $dbaccess;
$verbose = FALSE;

require_once('ascanner.php');
require_once('athemer.php');

$progname = basename(array_shift($argv));
$verbose = FALSE;
$test_all = FALSE;
$display_grammars = FALSE;
$test_attribute_scanner = FALSE;
$test_attribute_parser = FALSE;
$hlp = "$progname\n"
  . "[--display_grammars]\n"
  . "[--attribute_scanner]\n"
  . "[--attribute_parser]\n"
  . "[--all (turns on all tests)\n";

if (!$argv) {
  echo "$hlp";
  exit(0);
}

while ($argv) {
  switch (($arg = array_shift($argv))) {
    case '-h': case '--help':; echo $hlp ; exit(0);
    case '--all': $test_all = TRUE; break;
    case '--display_grammars': $display_grammars = TRUE; break;
    case '--attribute_scanner': $test_attribute_scanner = TRUE; break;
    case '--attribute_parser': $test_attribute_parser = TRUE; break;
    case '-v': case '--verbose': $verbose = TRUE ; break;
    default:
      echo "unknown arg: $arg\n";
      echo $hlp;
      exit(1);
  }
}

function run_process($cmd, $data) {
  $pipes = NULL; // just to be sure
  $descriptors = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "w"),
  );
  $resource = proc_open($cmd, $descriptors, $pipes);
  fwrite($pipes[0], $data);
  fclose($pipes[0]);
  
  $stdout = fread($pipes[1], 8192);
  fclose($pipes[1]);
  $stderr = fread($pipes[2], 8192);
  fclose($pipes[2]);
  return array(proc_close($resource), $stdout, $stderr);
} // end of run_process()

function pass_thru_php($str, $add_php_escape = FALSE) {
  if ($add_php_escape) {
    $str = "<\x3fphp " . $str;
  }
  // php syntax checks
  list($result, $stdout, $stderr) = run_process("/usr/local/bin/php52 -l", $str);
  if (!testTrue('PHP 5.2 Syntax Check', $result == 0)) {
    echo "$stdout\n$stderr\n";
  }

  list($result, $stdout, $stderr) = run_process("/opt/local/bin/php -l", $str);
  if (!testTrue('PHP 5.3 Syntax Check', $result == 0)) {
    echo "$stdout\n$stderr\n";
  }
}



  // the php grammar from the Zend engine doesn't work well for identifying
  //  all uses of variables in a complex variable specification. The problems
  //  I've had to work around have been:
  //   - loss of indirection - this was solved by removing the simple_indirect_reference
  //     non-terminal
  //   - various left recursions - solved by re-writing the productions in standard ways
  //   - inability to distinguish static class variables from non-statics resulting
  //     in attempting to check a non-existent variable. e.g. A::$foo generates
  //     tests for both A::$foo and $foo; the test for $foo is wrong - no work around
  //   - inability to identify interior attributes of an instance variable of an
  //     instance variable. e.g. $a->b->c generates tests for $a and $a->b->c, but
  //     not $a->b - no work around
  //   - does not identify base variable of an array referrence. e.g. $a['foo']
  //     generates a test for $['foo'], but not $a; $a[$b][$c] does not generate
  //     tests for $a or $a[$b] - no work around (yet)
  
  // so, we are working on an alternate PHP variable parsing grammar
$phpvar_grammar = file_get_contents('grammars/php_var_grammar2.y');

if ($display_grammars) {
  $themer = new YAThemeParser();
  $parser = new Parser($phpvar_grammar, new YAPHPVarScanner('', ''), $themer);
  echo $parser->language;
}

if ($test_all || $test_attribute_scanner) {
  echo "Testing YAPHPVarScanner\n";
  $php_var_data = array(
    array('$v',array(
      'T_VARIABLE',
    )),
    array('$$v',array('T_DOLLAR_SIGN', 'T_VARIABLE')),
    array('${$v}', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE')),
    array('$a->b', array('T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_STRING')),
    array('$$v->b', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_STRING')),
    array('${$v->b}', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_STRING', 'T_RBRACE')),
    array('${$v}->b', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE', 'T_OBJECT_OPERATOR', 'T_STRING')),
    array('$a->$b', array('T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE')),
    array('$$a->$b', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE')),
    array('${$v}->$b', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE', 'T_OBJECT_OPERATOR', 'T_VARIABLE')),
    array('${$v->$b}', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE', 'T_RBRACE')),
    array('$$v->$b', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE')),
    array('$$v->$$b', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_DOLLAR_SIGN', 'T_VARIABLE')),
    array('$$v->$b', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE')),
    array('${$v}->$$b', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE', 'T_OBJECT_OPERATOR', 'T_DOLLAR_SIGN', 'T_VARIABLE')),
    array('${$v->$$b}', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_DOLLAR_SIGN', 'T_VARIABLE', 'T_RBRACE')),
    array('${$v->${$b}}', array('T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE', 'T_RBRACE')),
    array('$$v->${$b}', array('T_DOLLAR_SIGN', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_DOLLAR_SIGN', 'T_LBRACE', 'T_VARIABLE', 'T_RBRACE')),
    array('$a->b->c', array('T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_STRING', 'T_OBJECT_OPERATOR', 'T_STRING')),
    array('$a[1]', array('T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET')),
    array('$a[\'foo\']', array('T_VARIABLE', 'T_LBRACKET', 'T_CONSTANT_ENCAPSED_STRING', 'T_RBRACKET')),
    array('$a["foo"]', array('T_VARIABLE', 'T_LBRACKET', 'T_CONSTANT_ENCAPSED_STRING', 'T_RBRACKET')),
    array('$a["foo" . "bar"]', array('T_VARIABLE', 'T_LBRACKET', 'T_CONSTANT_ENCAPSED_STRING', 'T_WHITESPACE', 'T_OP', 'T_WHITESPACE', 'T_CONSTANT_ENCAPSED_STRING', 'T_RBRACKET')),
    array('$a[$b]', array('T_VARIABLE', 'T_LBRACKET', 'T_VARIABLE', 'T_RBRACKET')),
    array('$a[$b][$c]', array('T_VARIABLE', 'T_LBRACKET', 'T_VARIABLE', 'T_RBRACKET', 'T_LBRACKET', 'T_VARIABLE', 'T_RBRACKET')),
    array('$a[2]', array('T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET')),
    array('$a[0]->$b[\'foo\']->{bar}', array('T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET', 'T_OBJECT_OPERATOR',
        'T_VARIABLE', 'T_LBRACKET', 'T_CONSTANT_ENCAPSED_STRING', 'T_RBRACKET',
        'T_OBJECT_OPERATOR', 'T_LBRACE', 'T_STRING', 'T_RBRACE')),
    array('$a[$b->$c]', array('T_VARIABLE', 'T_LBRACKET', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE', 'T_RBRACKET')),
    array('$a[$b->$c[1]]', array('T_VARIABLE', 'T_LBRACKET', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET', 'T_RBRACKET')),
    array('$a[$b->$c[1][2]]', array('T_VARIABLE', 'T_LBRACKET',
        'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET',
      'T_RBRACKET')),
    array('A::$s', array('T_STRING', 'T_DOUBLE_COLON', 'T_VARIABLE')),
    array('A::$a->b', array('T_STRING', 'T_DOUBLE_COLON', 'T_VARIABLE', 'T_OBJECT_OPERATOR', 'T_STRING')),
    array('A::$a[1]', array('T_STRING', 'T_DOUBLE_COLON', 'T_VARIABLE', 'T_LBRACKET', 'T_LNUMBER', 'T_RBRACKET')),
    array('{A}', array('T_LBRACE', 'T_STRING', 'T_RBRACE')),
    array('$a::$b', array('T_VARIABLE', 'T_DOUBLE_COLON', 'T_VARIABLE')),
    array('" "', array('T_CONSTANT_ENCAPSED_STRING')),
    array('"a "', array('T_CONSTANT_ENCAPSED_STRING')),
    array('" b"', array('T_CONSTANT_ENCAPSED_STRING')),
    array('"a b"', array('T_CONSTANT_ENCAPSED_STRING')),
    array('\' \'', array('T_CONSTANT_ENCAPSED_STRING')),
    array('"$foo"', array('T_DOUBLE_QUOTE', 'T_VARIABLE', 'T_DOUBLE_QUOTE')),
    array('"${foo}"', array('T_DOUBLE_QUOTE', 'T_DOLLAR_OPEN_CURLY_BRACES', 'T_STRING_VARNAME', 'T_RBRACE', 'T_DOUBLE_QUOTE')),
    array('\'$foo\'', array('T_CONSTANT_ENCAPSED_STRING')),
    array('\'${foo}\'', array('T_CONSTANT_ENCAPSED_STRING')),
    
    array('"foo$bar"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE', 'T_VARIABLE', 'T_DOUBLE_QUOTE')),
    array('"foo $bar baz"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE', 'T_VARIABLE', 'T_ENCAPSED_AND_WHITESPACE', 'T_DOUBLE_QUOTE')),
    array('"foo ${bar} baz"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE',
      'T_DOLLAR_OPEN_CURLY_BRACES', 'T_STRING_VARNAME', 'T_RBRACE',
      'T_ENCAPSED_AND_WHITESPACE', 'T_DOUBLE_QUOTE')),
    
    array('"foo $bar->a baz"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE', 'T_VARIABLE', 'T_OBJECT_OPERATOR',
        'T_STRING', 'T_ENCAPSED_AND_WHITESPACE', 'T_DOUBLE_QUOTE')),
    array('"foo ${bar->a} baz"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE',
        'T_DOLLAR_OPEN_CURLY_BRACES', 'T_STRING_VARNAME', 'T_OBJECT_OPERATOR', 'T_STRING', 'T_RBRACE',
        'T_ENCAPSED_AND_WHITESPACE', 'T_DOUBLE_QUOTE')),
    array('"foor {$bar} baz"', array('T_DOUBLE_QUOTE', 'T_ENCAPSED_AND_WHITESPACE', 'T_CURLY_OPEN', 'T_VARIABLE',
        'T_RBRACE', 'T_ENCAPSED_AND_WHITESPACE', 'T_DOUBLE_QUOTE')),
    array('"foo" . "$bar"', array('T_CONSTANT_ENCAPSED_STRING', 'T_WHITESPACE', 'T_OP', 'T_WHITESPACE',
        'T_DOUBLE_QUOTE', 'T_VARIABLE', 'T_DOUBLE_QUOTE', )),
  );

  $scanner =  new YAPHPVarScanner('', '');
  foreach ($php_var_data as $row) {
    if (is_array($row)) {
      list($var, $expect_ar) = $row;
      $scanner->process($var);
      while (($tmp = $scanner->token())) {
        $expect = array_shift($expect_ar);
        if (!testTrue("$var: $expect", $tmp[0] == $expect)) {
          echo "  expected: {$expect}'\n";
          echo "  received: {$tmp[0]} / '{$tmp[1]}'\n";
        }
        $scanner->advance();
      }
    } else {
      $scanner->process($row);
      echo $scanner->dump($row);
    }
  }
  
}

if ($test_all || $test_attribute_parser) {
  $test_data = array(
    array('$v', array('$v'), array()),
    array('${"foo"}', array('${"foo"}'), array()),
    array('$$v', array('$v', '$$v'), array()),
    array('$$$v', array('$v', '$$v', '$$$v'), array()),
    array('$$$$v', array('$v', '$$v', '$$$v', '$$$$v'), array()),
    array('${$v}', array('$v', '${$v}'), array()),
    array('$${"foo"}', array('${"foo"}', '$${"foo"}'), array()),
    array('$${"foo" . $bar}', array( '$bar', '${"foo" . $bar}', '$${"foo" . $bar}'), array()),
    array('$${"foo{$bar}"}', array('$bar', '${"foo{$bar}"}', '$${"foo{$bar}"}'), array()),
    
    array('${$idx . 1}', array('$idx', '${$idx . 1}'), array()),
    array('${$idx + 1}', array('$idx', '${$idx + 1}'), array()),
    array('${$idx - 1}', array('$idx', '${$idx - 1}'), array()),
    array('${$idx * 1}', array('$idx', '${$idx * 1}'), array()),
    array('${$idx / 1}', array('$idx', '${$idx / 1}'), array()),
    array('${$idx + $a - $b}', array('$idx', '$a', '$b', '${$idx + $a - $b}'), array()),
    
    array('${foo()}', array('${foo()}'), array()),
    array('${foo($a)}', array('${foo($a)}', '$a'), array()),
    array('${foo($a,12,$b)}', array('${foo($a,12,$b)}', '$a', '$b'), array()),
    
    array('$a->b', array('$a', '$a->b'), array()),
    array('$a->b->c', array('$a', '$a->b', '$a->b->c'), array()),
    array('$a->b->c->d', array('$a', '$a->b', '$a->b->c', '$a->b->c->d'), array()),
    array('$$v->b', array('$v', '$$v', '$$v->b'), array()),
    array('${$v->b}', array('$v', '$v->b', '${$v->b}'), array()),
    array('${$v}->b', array('$v', '${$v}', '${$v}->b'), array()),
    array('$a->$b', array('$a', '$b', '$a->$b'), array()),
    array('$a->$b->$c', array('$a', '$b', '$c', '$a->$b', '$a->$b->$c'), array()),
    array('$a[1]->$b[2][3]->$c[0]', array('$a', '$a[1]', '$b', '$b[2]', '$b[2][3]', '$c', '$c[0]', '$a[1]->$b[2][3]', '$a[1]->$b[2][3]->$c[0]'), array()),
    array('$$a->$b', array('$a', '$$a', '$b', '$$a->$b'), array()),
    array('${$v}->$b', array('$v', '$b', '${$v}', '${$v}->$b'), array()),
    array('${$v->$b}', array('$v', '$b', '$v->$b', '${$v->$b}'), array()),
    array('$$v->$b', array('$v', '$$v', '$b', '$$v->$b'), array()),
    array('$$v->$$b', array('$v', '$$v', '$b', '$$b', '$$v->$$b'), array()),
    array('$$v->$$$b', array('$v', '$$v', '$b', '$$b', '$$$b', '$$v->$$$b'), array()),
    array('${$v}->$$b', array('$v', '${$v}', '$b', '$$b', '${$v}->$$b'), array()),
    array('${$v->$$b}', array('$v', '$b', '$$b', '$v->$$b', '${$v->$$b}'), array()),
    array('${$v->${$b}}', array('$v', '$b', '${$b}', '$v->${$b}', '${$v->${$b}}'), array()),
    array('$$v->${$b}', array('$v', '$$v', '$b', '${$b}', '$$v->${$b}'), array()),
    
    array('$a[1]', array('$a', '$a[1]'), array()),
    array('$a[1][2][3][4]', array('$a', '$a[1]', '$a[1][2]', '$a[1][2][3]', '$a[1][2][3][4]'), array()),
    array('$a[\'foo\']', array('$a', '$a[\'foo\']'), array()),
    array('$a["foo"]', array('$a', '$a["foo"]'), array()),
    array('$a["foo" . "bar"]', array('$a', '$a["foo" . "bar"]'), array()),
    array('$a["foo" . "$bar"]', array('$a', '$bar', '$a["foo" . "$bar"]'), array()),
    array('$a[$b]', array('$a', '$b', '$a[$b]'), array()),
    array( '$a[$b][$c]', array('$a', '$b', '$c', '$a[$b]', '$a[$b][$c]'), array()),
    array( '$a[2]', array('$a', '$a[2]'), array()),
     
    array('$a[0]->b', array('$a', '$a[0]', '$a[0]->b'), array()),
    array('$a[0]->b[1]->c[2]->d[3]', array('$a', '$a[0]', '$a[0]->b', '$a[0]->b[1]', '$a[0]->b[1]->c', '$a[0]->b[1]->c[2]', '$a[0]->b[1]->c[2]->d', '$a[0]->b[1]->c[2]->d[3]'), array()),
    array('$a->b[0]', array('$a', '$a->b', '$a->b[0]'), array()),
    array('$a->b[0][1][2]', array('$a', '$a->b', '$a->b[0]', '$a->b[0][1]', '$a->b[0][1][2]'), array()),
    
    array('$a->$b[0]', array('$a', '$b', '$b[0]', '$a->$b[0]'), array()),
    array('$a->$b[0]->$c[1]->$d[2]', array('$a', '$b', '$b[0]', '$c', '$c[1]', '$d', '$d[2]', '$a->$b[0]', '$a->$b[0]->$c[1]', '$a->$b[0]->$c[1]->$d[2]'), array()),
    
    array('$a[0]->$b', array('$a', '$a[0]', '$b', '$a[0]->$b'), array()),
    array('$a[0]->$b[1]', array('$a', '$a[0]', '$b', '$b[1]', '$a[0]->$b[1]'), array()),
    array('$a[\'bar\']->$b[0]', array('$a', '$b', '$b[0]', '$a[\'bar\']', '$a[\'bar\']->$b[0]'), array()),
    array('$a[\'bar\']->$b[\'baz\']', array('$a', '$b', '$b[\'baz\']', '$a[\'bar\']', '$a[\'bar\']->$b[\'baz\']'), array()),
    array('$a[\'bar\']->$b[\'baz\']->c', array('$a', '$b', '$b[\'baz\']', '$a[\'bar\']', '$a[\'bar\']->$b[\'baz\']', '$a[\'bar\']->$b[\'baz\']->c'), array()),
    array('$a[\'bar\']->$b[\'baz\']->$c', array('$a', '$b', '$b[\'baz\']', '$c', '$a[\'bar\']', '$a[\'bar\']->$b[\'baz\']', '$a[\'bar\']->$b[\'baz\']->$c'), array()),
    array( '$a[0]->$b[\'foo\']->{bar}', array('$a', '$a[0]', '$b', '$b[\'foo\']', '$a[0]->$b[\'foo\']', '$a[0]->$b[\'foo\']->{bar}'), array()),
    array('$a[$b->$c]', array('$a', '$b', '$c', '$b->$c', '$a[$b->$c]'), array()),
    array('$a[$b->$c[1]]', array('$a', '$b', '$c', '$c[1]', '$b->$c[1]', '$a[$b->$c[1]]'), array()),
    array('$a[$b->$c[1][2]]', array('$a', '$b', '$c', '$c[1]', '$c[1][2]', '$b->$c[1][2]', '$a[$b->$c[1][2]]'), array()),
    array('A::$s', array('A::$s'), array('A')),
    array('A::$a->b', array('A::$a', 'A::$a->b'), array('A')),
    array('A::$a[1]', array('A::$a', 'A::$a[1]'), array('A')),
    array( '$a::$b', array('$a', '$a::$b'), array('$a')),
    array('$$a::$b', array('$a', '$$a', '$$a::$b'), array('$$a')),
    array('${"foo" . $a}::$b', array('$a', '${"foo" . $a}', '${"foo" . $a}::$b'), array('${"foo" . $a}')),

    // namespace stuff
    array('\name\A::$x', array('\name\A::$x'), array('\name\A')),
    array('\name\name2\A::$x', array('\name\name2\A::$x'), array('\name\name2\A')),
    array('name\A::$x', array('name\A::$x'), array('name\A')),
    array('name\name2\A::$x', array('name\name2\A::$x'), array('name\name2\A')),

     // the following are not variables and aren't expected to parse
     // '" "',
     //  '"a "',
     //  '" b"',
     //  '"a b"',
     //  '\' \'',
     //  '"$foo"',
     //  '"${foo}"',
     //  '\'$foo\'',
     //  '\'${foo}\'',
     //  
     //  '"foo$bar"',
     //  '"foo $bar baz"',
     //  '"foo ${bar} baz"',
     //  
     //  '"foo $bar->a baz"',
     //  '"foo ${bar->a} baz"',
     //  '"foor {$bar} baz"',
    );

  $test_func = function($expect, $received) {
    $test_ar = array_map(create_function('$x', '$a=explode(": ", $x); return $a[0];'), $received);
    $ret = TRUE;
    foreach ($expect as $v) {
      if (!in_array($v, $test_ar)) {
        echo "Expected $v not present\n";
        $ret = FALSE;
      }
    }
    foreach ($test_ar as $v) {
      if (!in_array($v, $expect)) {
        echo "Extra $v found\n";
        $ret = FALSE;
      }
    }
    return $ret;
  };

  // echo "$new_parser->language";
  foreach ($test_data as $row) {
    if (is_array($row)) {
      list($str, $var_ar, $class_ar) = $row;
    } else {
      $str = $row;
      $class_ar = $var_ar = NULL;
    }

    echo "\n-------------------------\nTESTING $str\n";
    // php syntax checks
    pass_thru_php($str);

    // parse test
    $themer = new YAThemeParser();
    $themer->verbose = $verbose;
    ParserNode::$trace = $verbose;
    $new_parser = new Parser($phpvar_grammar, new YAPHPVarScanner('', ''), $themer);
    if (testTrue("parsing $str", $new_parser->parse($str))) {
      echo "   -------------------\n";
      if (!testTrue("round trip with new parser: $str", ($rendered = $new_parser->render()) == $str)) {
        echo "Expected: '$str'\n";
        echo "Received: '$rendered'\n";
      }
      testTrue("variable stack test for $str", $test_func($var_ar, $themer->variable_names));
      testTrue("class stack test for $str", $test_func($class_ar, $themer->class_names));
      // $themer->display_stacks('test_phpvar_grammar.php: ' . __LINE__);
    } else {
      $new_parser->verbose = TRUE;
      $new_parser->parse($str);
      $new_parser->verbose = FALSE;
    }
continue;

    $code =  $themer->paranoid_guards($str);
    if ($var_ar) {
      $class_errors =  implode(',', array_merge(array_diff($class_ar, $themer->stacks['class_name_stack']),
            array_diff($themer->stacks['class_name_stack'], $class_ar)));
      $var_errors = implode(',', array_merge(array_diff($var_ar, $themer->stacks['variable_name_stack']),
            array_diff($themer->stacks['variable_name_stack'], $var_ar)));
      if (!testFalse("No Missed Variables", $class_errors || $var_errors)) {
        echo "expected variable_name_stack: "; var_dump($var_ar);
        echo "achieved variable_name_stack: "; var_dump($themer->stacks['variable_name_stack']);
      }
      echo "Returned code:\n$code";
    } else {
      echo "No Test Defined:\n";
      echo "code: ", var_dump($code);
      echo "var_ar:", var_dump($themer->stacks['variable_name_stack']);
      echo "class_ar:", var_dump($themer->stacks['class_name_stack']);
    }
  }
}

testReport();