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
require_once('aparser.php');
require_once('athemer.php');

$progname = basename(array_shift($argv));
$verbose = FALSE;
$test_attribute_scanner = FALSE;
$test_attribute_parser = FALSE;
$test_yat_scanner = FALSE;
$test_yat_parser = FALSE;
$display_grammars = FALSE;
$test_file_name = FALSE;
$hlp = "$progname [--display_grammars]\n"
  . "[--attribute_scanner]\n"
  . "[--attribute_parser]\n"
  . "[--yat_scanner]\n"
  . "[--yat_parser]\n"
  . "[--test_parser]\n"
  . "[--test_file <filename>]"
  . "[--all_yat (turns on yat_scanner, yat_parser, and test_parsers)]\n"
  . "[--all (turns on all tests)]";

if (!$argv) {
  echo "$hlp";
  exit(0);
}

while ($argv) {
  switch (($arg = array_shift($argv))) {
    case '-h': case '--help':; echo $hlp ; exit(0);
    case '--all': $test_attribute_scanner = $test_attribute_parser = $test_yat_scanner = $test_yat_parser = $test_parser = TRUE ; break;
    case '--all_yat': $test_yat_scanner = $test_yat_parser = $test_parser = TRUE ; break;
    case '--attribute_scanner': $test_attribute_scanner = TRUE; break;
    case '--attribute_parser': $test_attribute_parser = TRUE; break;
    case '--yat_scanner': $test_yat_scanner = TRUE; break;
    case '--yat_parser': $test_yat_parser = TRUE; break;
    case '--test_parser': $test_parser = TRUE; break;
    case '--test_file': $test_parser = TRUE; $test_file_name = array_shift($argv); break;
    case '--display_grammars': $display_grammars = TRUE ; break;
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
  
  $stdout = '';
  while ($tmp = fread($pipes[1], 8192)) {
    $stdout .= $tmp;
  }
  fclose($pipes[1]);
  $stderr = '';
  while ($tmp = fread($pipes[2], 8192)) {
    $stderr .= $tmp;
  }
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

  list($result, $stdout, $stderr) = run_process("/opt/local/bin/php", $str);
  testTrue('PHP 5.3 Execution Check', $result == 0);

  echo "\n=======PHP Processed Output==========\n$stdout\n$stderr\n============End Processed Output=========\n";
}

if ($display_grammars) {
  foreach (array(YAThemeParser::$yatheme_grammar, YAThemeParser::$ya_phpvar_grammar, YAThemeParser::$ya_phpvar_grammar)
      as $grammar) {
    echo "\n======================================\n";
    $lang = new ParserLangDefParser($grammar);
    echo "$lang";
  }
  echo "\n======================================\n";
}

if ($test_attribute_scanner) {
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
    array('\name\A::$x', array('T_NS_SEPARATOR', 'T_STRING', 'T_NS_SEPARATOR', 'T_STRING', 'T_DOUBLE_COLON', 'T_VARIABLE')),
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

if ($test_yat_scanner) {
  echo "Testing YATheme scanner\n";
  $yatheme_data = array(
    array('<p>some html</p>', array(
      array('HTML', '<p>some html</p>'),
    )),
    array("<\x3fphp echo \"foo\n\";\x3f>", array(
      array('PHP', "<\x3fphp echo \"foo\n\";\x3f>"),
    )),

    // compound statements
    array('<p>some html</p>' . "<\x3fphp echo \"foo\n\";\x3f>",
     array(
       array('HTML', '<p>some html</p>'),
       array('PHP', "<\x3fphp echo \"foo\n\";\x3f>"),
    )),
    array("<\x3fphp echo \"foo\n\";\x3f>" . '<p>some html</p>', array(
      array('PHP', "<\x3fphp echo \"foo\n\";\x3f>"),
      array('HTML', '<p>some html</p>'),
    )),
    
    // yatheme statements
    // yatheme on is only recognized in a complete parse. It is an error it not preceeded by a 'yatheme off'
    array('{: yatheme off :} foo ' . "<\x3fphp echo 'foo'; \x3f>" . ' {:yatheme on:} bar ' . "<\x3fphp echo 'foo'; \x3f>",
     array(
      array('Y_TEXT', " foo <\x3fphp echo 'foo'; \x3f> "),
      array('HTML', ' bar '),
      array('PHP', "<\x3fphp echo 'foo'; \x3f>")
    )),
      
    // guards
    array('{: guards on :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_GUARDS', 'guards'),
      array('Y_TEXT', 'on'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: guards off :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_GUARDS', 'guards'),
      array('Y_TEXT', 'off'),
      array('Y_CLOSE_YBRACE', ':}')
    )),

    // attributes
    array('{: Foo::$a  :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', 'Foo::$a'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{: test Foo::$a  :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_TEST', 'test'),
      array('Y_TEXT', 'Foo::$a'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    
    // yatemplate file and content
    array('{: yatemplate-content :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_YATEMPLATE_CONTENT', 'yatemplate-content'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    
    // yatemplate file and content
    array('{: yatemplate ./a+b/file name.tpl :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_YATEMPLATE', 'yatemplate'),
      array('Y_YATEMPLATE_FILE', './a+b/file name.tpl'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    
    // authority / A|C|V|M|S|X|ANY
    array('{: authority  A :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'A'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority C :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'C'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority V :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'V'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority M :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'M'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority S :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'S'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority X :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'X'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority ANY :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'ANY'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority A,C :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'A,C'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority A , C :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'A , C'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: authority A,C,V,M,S,X :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_AUTHORITY', 'authority'),
      array('Y_TEXT', 'A,C,V,M,S,X'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    
    // error handling
    array('{: errors display :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ERRORS', 'errors'),
      array('Y_TEXT', 'display'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: errors ignore :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ERRORS', 'errors'),
      array('Y_TEXT', 'ignore'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: errors email foo@bar.com :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ERRORS', 'errors'),
      array('Y_EMAIL', 'email'),
      array('Y_TEXT', 'foo@bar.com'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
      
    // include
    array('{: include .a/foo-bar.tpl :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_INCLUDE', 'include'),
      array('Y_INCLUDE_FILE', '.a/foo-bar.tpl'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    
    // attributes
    array('{: Foo::$bar :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', 'Foo::$bar'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: $bar :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', '$bar'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: Foo::$bar | default value:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', 'Foo::$bar'),
      array('Y_TEXT', 'default value'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: $bar |  default   value   :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', '$bar'),
      array('Y_TEXT', 'default   value'),
      array('Y_CLOSE_YBRACE', ':}')
    )),
    array('{: $bar |  default   value   :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_ATTRIBUTE', '$bar'),
      array('Y_TEXT', 'default   value'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    
    // metadata and links
    array('{:meta content-type fluid:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_META', 'content-type'),
      array('Y_TEXT', 'fluid'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:css screen.css:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_CSS', 'screen.css'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:css screen.css print , handheld:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_CSS', 'screen.css'),
      array('Y_TEXT', 'print , handheld'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:javascript javascript/foo-bar.js:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_JAVASCRIPT', 'javascript/foo-bar.js'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    
    array('{:render meta:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_RENDER', 'meta'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:render css :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_RENDER', 'css'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:render   javascript   :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_RENDER', 'javascript'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    
    // php-prefix
    array('{:php-prefix:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_PHP_PREFIX', 'php-prefix'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:php-prefix:}$foo = barf;{:end-php-prefix:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_PHP_PREFIX', 'php-prefix'),
      array('Y_CLOSE_YBRACE', ':}'),
      array('Y_TEXT', '$foo = barf;'),
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_END_PHP_PREFIX', 'end-php-prefix'),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
    array('{:php-prefix:}'. "<\x3fphp \$foo = 'barf';\x3f>" . '{:end-php-prefix:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_PHP_PREFIX', 'php-prefix'),
      array('Y_CLOSE_YBRACE', ':}'),
      array('error', 'file -:near line 1: Syntax Error: illegal PHP escape encountered in php-prefix: \'<?php $foo = \'barf\';?>{:end-php-prefix:}\''),
    )),
    array('{:php-prefix:}$foo = "barf";{:$foo:}{:end-php-prefix:}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('Y_PHP_PREFIX', 'php-prefix'),
      array('Y_CLOSE_YBRACE', ':}'),
      array('error', 'file -:near line 1: Syntax Error: illegal YATheme escape encountered in php-prefix: \'{:$foo:}{:end-php-prefix:}\''),
    )),
    
    // array('', array(
    //   array('Y_OPEN_YBRACE', '{:'),
    //   array('Y_CLOSE_YBRACE', ':}')
    // )),
    array('{: barf er :}', array(
      array('Y_OPEN_YBRACE', '{:'),
      array('error', "file -:near line 1: Syntax Error: ' barf er :}'"),
      array('Y_CLOSE_YBRACE', ':}'),
    )),
  );
  include('./grammars/yat_scanner.inc');
  $scanner = new YAScanner('init', $yat_states);
  // $scanner = new YAScanner('init', YAThemeParser::$yatheme_states);
  // $scanner->verbose = TRUE;
  foreach ($yatheme_data as $row) {
    list($str, $expect_ar) = $row;
    echo "Checking '$str'\n";
    $scanner->process($str);
    foreach ($expect_ar as $expect) {
      $tmp = $scanner->token();
      // strip line number
      array_pop($tmp);
      if (!testTrue("Looking for $expect[0], $expect[1]", $tmp == $expect)) {
        @list($tag, $value, $line_no) = $scanner->token();
        echo "Expected {$expect[0]}, '{$expect[1]}'\nReceived {$tag} / '{$value}'\n";
      }
      $scanner->advance();
    }
    testTrue("Checked All Tokens", ($tmp = $scanner->token()) === FALSE || $tmp[0] == 'error');
    var_dump($tmp);
  }
  testReport();
}

if ($test_attribute_parser) {
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

    // $test_func = function($expect, $received) {
    //   $test_ar = array_map(create_function('$x', '$a=explode(": ", $x); return $a[0];'), $received);
    //   $ret = TRUE;
    //   foreach ($expect as $v) {
    //     if (!in_array($v, $test_ar)) {
    //       echo "Expected $v not present\n";
    //       $ret = FALSE;
    //     }
    //   }
    //   foreach ($test_ar as $v) {
    //     if (!in_array($v, $expect)) {
    //       echo "Extra $v found\n";
    //       $ret = FALSE;
    //     }
    //   }
    //   return $ret;
    // };
    $test_func = create_function('$expect,$received',
      '$test_ar = array_map(create_function(\'$x\', \'$a=explode(": ", $x); return $a[0];\'), $received);
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
      '
    );

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
      // $new_parser = new Parser(YAThemeParser::$ya_phpvar_grammar, new YAPHPVarScanner('', ''), $themer);
      $parser = $themer->phpvar_parser;
      if (testTrue("parsing $str", $parser->parse($str))) {
        echo "   -------------------\n";
        if (!testTrue("round trip with new parser: $str", ($rendered = $parser->render()) == $str)) {
          echo "Expected: '$str'\n";
          echo "Received: '$rendered'\n";
        }
        testTrue("variable stack test for $str", call_user_func($test_func, $var_ar, $themer->variable_names));
        testTrue("class stack test for $str", call_user_func($test_func, $class_ar, $themer->class_names));
        // $themer->display_stacks('test_phpvar_grammar.php: ' . __LINE__);
      } else {
        $parser->verbose = TRUE;
        $parser->parse($str);
        $parser->verbose = FALSE;
      }
// FIXME: Remove the comment, clean the tests and make it work with paranoidness
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


if ($test_yat_parser) {
  $test_data = array(
    array('<p>This is HTML</p>', '<p>This is HTML</p>'),
    array("<\x3fphp echo 'this is php'; \x3f>", "<\x3fphp echo 'this is php'; \x3f>"),
    array('{:yatheme off:}{:comment this is a comment:}{:yatheme on:}', '{:comment this is a comment:}'),
    array('{:guards on:}', '', 'return $themer->guards == "on";'),
    array('{:guards off:}', '', 'return $themer->guards == "off";'),
    array('{:yatemplate template-file.tpl:}', '', 'return $themer->template_file == "template-file.tpl";'),
    array('{:yatemplate-content:}', '{: yatemplate-content :}', 'return $themer->yatemplate_content == "";'),
    array('{:authority A,C,X:}', '', 'return $themer->authority == "A,C,X";'),
    array('{:errors ignore:}', '', 'return $themer->errors == "ignore";'),
    array('{:errors display:}', '', 'return $themer->errors == "display";'),
    array('{:errors email foo@example.com:}', '', 'return $themer->errors == "email" && $themer->errors_email == "foo@example.com";'),
    array('{:include include-file.tpl:}', "<h1>This is an included file</h1>\n"),

    array('{:$foo:}',  "<\x3fphp echo \$foo; ?>"),
    array('{:$foo | default value:}',  "<\x3fphp echo (isset(\$foo) ? \$foo : 'default value'); ?>"),
    array("<\x3fphp \$foo=\"barf\";\x3f>" . '{:$foo:}', "<\x3fphp \$foo=\"barf\";\x3f><\x3fphp echo \$foo; \x3f>"),
    array("<\x3fphp \$foo=\"barf\";\x3f>" . '{:guards on:}{:errors display:}{:$foo:}', "<\x3fphp \$foo=\"barf\";\x3f><\x3fphp echo (isset(\$foo) ? \$foo : '<div class=\"yatheme-error\"><p>Error: variable \'\$foo\' is not set</p></div>'); \x3f>"),
    
    array('{:test A::$b:}'),

    array("<\x3fphp class A{static \$b;}\x3f>" . '{:guards off:}{:A::$b:}',
        "<\x3fphp class A{static \$b;}\x3f>" . "<\x3fphp echo A::\$b; \x3f>"),
    array("<\x3fphp class A{static \$b;}\x3f>" . '{:guards on:}{:A::$b:}',
        "<\x3fphp class A{static \$b;}\x3f>" . "<\x3fphp echo (isset(A::\$b) ? A::\$b : '<div class=\"yatheme-error\"><p>Error: variable \'A::\$b\' is not set</p></div>'); \x3f>"),
    // array('{:guards paranoid:}{:A::$b:}', "<\x3fphp echo A::\$b; \x3f>"),
    array('{:guards off:}<p>This is a foo: {:$foo:}</p>' . "<\x3fphp echo \"php barf\n\";\x3f>", 
      '<p>This is a foo: ' . "<\x3fphp echo \$foo; \x3f>" . '</p>' . "<\x3fphp echo \"php barf\n\";\x3f>"),
    array('{:meta content-type eggplant:}{:render meta:}', '  <meta http-equiv="content-type" content="eggplant">' . "\n"),
    array('{:css /css/foo.css:}{:render css:}', '<link rel="stylesheet" href="/css/foo.css" type="text/css" media="all" charset="utf-8">' . "\n"),
    array('{:javascript javascript/js.js:}{:render javascript:}', '<script type="text/javascript" src="javascript/js.js" charset="utf-8"></script>' . "\n"),
  );

  echo "Testing YATheme Parser\n";
  class A {
    static $b;
  }
  
  $include_path = get_include_path();
  set_include_path('./yatheme_tests' . PATH_SEPARATOR . $include_path);
  foreach ($test_data as $row) {
    $themer = new YAThemeParser();
    $themer->guards = 'off';
    // $parser = $themer->yatheme_parser;
    // $scanner = $themer->yatheme_scanner;
    // $parser->verbose = $verbose;
    include('./grammars/yat_scanner.inc');
    $scanner = new YAScanner('init', $yat_states);
    $parser = new Parser(file_get_contents('./grammars/yat_grammar.y'), $scanner, $themer, $verbose);

    if (is_array($row)) {
      @list($str, $expect, $extra) = $row;
    } else {
      $str = $row;
      $expect = FALSE;
    }

    echo "\nTesting '$str'\n";
    if (testTrue("parsing: '$str'", $parser->parse($str))) {
      // echo $parser->root->dump() . "\n";
      echo $parser->render() . "\n";
      pass_thru_php($parser->render());

      // testTrue(" PHP accepts rendering", $tmp !== FALSE);
      if ($expect !== FALSE) {
        $expect = preg_replace(array('/\n */', '/ +/'), array("\n", ' '), $expect);
        $received = preg_replace(array('/\n */', '/ +/'), array("\n", ' '), $parser->render());
        if (!testTrue("Looking for '" . substr($expect, 0, 25) . " . . .'", $expect == $received)) {
          echo "Expected '{$expect}'\nReceived '{$received}'\n";
        }
        if ($extra) {
          testTrue("checking '$extra'", eval($extra));
        }
      } // else {
      //   echo "Successfully parsed '$str'\n";
      //   echo "\$parser->execute(): {$parser->execute()}\n";
      // }
    } else {
      $parser->verbose = TRUE;
      $parser->parse($str);
      $parser->verbose = FALSE;
    }
  }
  set_include_path($include_path);
  
  testReport();
}  // end test_yat_parser


if ($test_parser) {
  echo "Testing Entire Parser on template files\n";
  echo "Not Ready to Test Yet\n";
  testReport();
  return;
  // $test_data = array(
  //   array("<p>foo: {:\$foo:} trailer</p>",
  //       "<p>foo: <\x3fphp echo (isset($foo) ? $foo : \'<div class=\"yatheme-error\"><p>Error: variable \\\'$foo\\\' is not set</p></div>\'); \x3f> trailer</p>"),
  // );
  // foreach ($test_data as $row) {
  //   @list($str, $expect) = $row;
  //   $parser->parse($str);
  //   if ($expect) {
  //     if (!testTrue("rendering '$str'", $expect == $parser->render())) {
  //       echo "Expected: '$expect'\n";
  //       echo "Received: '{$parser->render()}'\n";
  //     }
  //   } else {
  //     echo "No Test Specified:\n  rendered: '{$parser->render()}'";
  //   }
  // }
  
  $include_path = get_include_path();
  set_include_path('./yatheme_tests' . PATH_SEPARATOR . './yatheme_templates' . PATH_SEPARATOR . $include_path);
  if ($test_file_name) {
    $scan_dir = array(stream_resolve_include_path($test_file_name));
  } else {
    $scan_dir = scandir('./yatheme_tests');
  }
  foreach ($scan_dir as $fname) {
    if (!preg_match('/.tpl$/', $fname)) {
      continue;
    }
    echo "\n========================\nReading and Rendering '$fname'\n";
    $parser = new YAThemeParser($fname, $verbose);
    testTrue("parsing $fname", $parser->parse_result);
    $rendering = $parser->render();
    echo "^&*)&Rendered Content: $rendering\n&*((^%))\n";
    pass_thru_php($rendering);
    echo "\nFiles Read: " . implode(',', $parser->all_file_names) . "\n";

    echo "===================start===============\n";
    $line_no = 1;
    foreach (preg_split('/\n/', $rendering) as $line) {
      printf("%3d: %s\n", $line_no++, $line);
    }
    echo "====================end================\n";
    echo "\n===================start===============\n";
    echo $rendering;
    echo "====================end================\n";
    // echo $parser->yatheme_parser->display_parser_state(__FILE__ . ': ' . __LINE__);

// break;
  }
  set_include_path($include_path);
}

TestReport();
