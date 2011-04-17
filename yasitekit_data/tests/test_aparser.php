<?php
/*
h1. test_aparser.php - unit tests for aparser.php

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

$progname = basename(array_shift($argv));
$verbose = FALSE;
$test_all = FALSE;
$test_error_handling = FALSE;
$test_execute = FALSE;
$test_first = FALSE;
$test_infinite_recursion = FALSE;
$test_languages = FALSE;
$test_quoted_strings = FALSE;
$test_scanner = FALSE;
$test_symbol_parser = FALSE;
$test_this_language = FALSE;
$test_this_sentence = FALSE;
$test_traps = FALSE;
while (($arg = array_shift($argv))) {
  switch ($arg) {
    case '-h': case '--help':
      echo "$progname [-v/--verbose] [-a/--all/--test_all] [-l/--language language sentence]\n"
          . " [--test_error_handling]\n"
          . " [--test_execute]\n"
          . " [--test_first]\n"
          . " [--test_infinite_recursion]\n"
          . " [--test_quoted_strings]\n"
          . " [--test_scanner]\n"
          . " [--test_symbol_parser]\n"
          . " [--test_traps]\n"
          ;
      exit(0);
    case '-v': case '--verbose': $verbose = TRUE; break;
    case '-a': case '--all': case '--test_all': $test_all = TRUE; break;
    case '-l': case '--language': $test_this_language = array_shift($argv); $test_this_sentence = array_shift($argv); break;
    case '--test_error_handling': $test_error_handling = TRUE; break;
    case '--test_execute': $test_execute = TRUE; break;
    case '--test_first': $test_first = TRUE ; break;
    case '--test_infinite_recursion': $test_infinite_recursion = TRUE ; break;
    case '--test_quoted_strings': $test_quoted_strings = TRUE; break;
    case '--test_scanner': $test_scanner = TRUE; break;
    case '--test_symbol_parser': $test_symbol_parser = TRUE ; break;
    case '--test_traps': $test_traps = TRUE ; break;
    default: echo "unknown arg: $arg\n"; break;
  }
}

// testing YAScanner
if ($test_all || ($test_scanner && !$test_this_language)) {
  echo "\nTesting Scanner Section:\n";

  function test_state_def($title, $states, $test_data) {
    global $verbose;
  
    echo "\n$title\n";
    $scanner = new YAScanner('init', $states);
    if ($verbose) {
      echo $scanner->dump(__LINE__);
    }
    foreach ($test_data as $row) {
        list($str, $expected) = $row;
        $scanner->process($str);
        echo "processing '$str'\n";
        foreach ($expected as $row) {
          list($token_tag, $token_syntactic_value, $line_no) = $scanner->token();
          if (is_array($row) && count($row) == 2) {
            if (!testTrue("found $token_tag / $token_syntactic_value", $token_tag == $row[0] && $token_syntactic_value == $row[1])) {
              echo "  Received: tag: '{$token_tag}'; syntactic value: '{$token_syntactic_value}'\n";
              echo "  Expected: tag: '{$row[0]}'; syntactic_value: '{$row[1]}'\n";
              // echo $scanner->dump();
            }
          } else {
            if (!testTrue("found $row", $token_tag == $row)) {
              echo "tag: $token_tag; syntactic value: $token_syntactic_value\n";
              echo $scanner->dump();
            }
          }
          $scanner->advance();
        }
    }
  }

  $parser_language_data = array(
    array(' TERMINAL ',
      array(
        array('terminal', 'TERMINAL'),
    )),
    array(' TERMINAL ( A )',
      array(
        array('terminal', 'TERMINAL'),
        array('semantic_name', 'A'),
    )),
    array(' (FOO)',
      array(
        array('semantic_name', 'FOO'),
    )),
    array(' (   FOO)',
      array(
        array('semantic_name', 'FOO'),
    )),
    array(' (FOO   )',
      array(
        array('semantic_name', 'FOO'),
    )),
    array('non_terminal_name',
      array(
        array('non_terminal', 'non_terminal_name'),
    )),
    array('%php{ php action }',
      array(
        array('php_action', ' php action '),
    )),
    array('%php{ php action \{ }',
      array(
        array('php_action', ' php action { '),
    )),
    array('%php{ php action \} }',
      array(
        array('php_action', ' php action } '),
    )),
    array('%php{ {php action} }',
      array(
        array('php_action', ' {php action} '),
    )),
    array('%php{ { php action } } ( A )',
      array(
        array('php_action', ' { php action } '),
        array('semantic_name', 'A'),
    )),
    array('%string{ string action }',
      array(
        array('string_action', ' string action '),
    )),
    array('%string{ {string action} }',
      array(
        array('string_action', ' {string action} '),
    )),
    array('%string{ { string action } } ( A )',
      array(
        array('string_action', ' { string action } '),
        array('semantic_name', 'A'),
    )),
    array('%str{ str action }',
      array(
        array('string_action', ' str action '),
    )),
    array('%str{ {str action} }',
      array(
        array('string_action', ' {str action} '),
    )),
    array('%str{ { string action } } ( A )',
      array(
        array('string_action', ' { string action } '),
        array('semantic_name', 'A'),
    )),
    array('foo (A): TERMINAL ( B ) foo (C) %php{ @@ = a_terminal; } (D)',
      array(
        array('production', 'foo'),
        array('semantic_name', 'A'),
        array('new_production', ':'),
        array('terminal', 'TERMINAL'),
        array('semantic_name', 'B'),
        array('non_terminal', 'foo'),
        array('semantic_name', 'C'),
        array('php_action', ' @@ = a_terminal; '),
        array('semantic_name', 'D'),
    )),
    array('foo: bar ;',
      array(
        array('production', 'foo'),
        array('new_production', ':'),
        array('non_terminal', 'bar'),
        array('production_end', ';'),
    )),
    array('foo (A): bar ( B ) | T | ; bar: Y foo ;',
      array(
        array('production', 'foo'),
        array('semantic_name', 'A'),
        array('new_production', ':'),
        array('non_terminal', 'bar'),
        array('semantic_name', 'B'),
        array('new_production', '|'),
        array('terminal', 'T'),
        array('new_production', '|'),
        array('production_end', ';'),
        array('production', 'bar'),
        array('new_production', ':'),
        array('terminal', 'Y'),
        array('non_terminal', 'foo'),
        array('production_end', ';'),
    )),
  );
  test_state_def("Testing parser language data", ParserLangDefParser::$parser_language_states, $parser_language_data);

  testReport();
}  // test state machine

// Parser Symbol Test
if ($test_all || ($test_symbol_parser && !$test_this_language)) {
  echo "Parser Symbol Test\n";

  foreach (array(
        'foo (A): TERMINAL ( B ) foo (C) %php{ @@ = a_terminal; } (D) ;',
        'foo (A): TERMINAL foo | TERMINAL ;',
        'empty_last (A): TERMINAL foo | ; foo: TERMINAL;',
        'foo (A): TERMINAL ( B ) foo (C) %php{ @@ = a_terminal; } (D); bar: T2;',
        '%start bar foo : | T | T foo |; bar: foo ;',
      ) as $lang_def) {
      
    $foo = new ParserLangDefParser($lang_def);
    echo "\n$foo";
    $foo->sort();
    $bar = new ParserLangDefParser("$foo");
    testTrue("round trip test for $lang_def", "$foo" == "$bar");
    echo $foo->dump("\ntest language dump");
  }

  testReport();
} // Test ParserLangDefParser()

if ($test_all || $test_traps) {
  echo "\nTesting consistency traps\n";
  $define_states =<<<EOT
  \$ersatz_states = array(
      array('init','emit_error.an error',
        array('/^.*/', 'init', 'discard_matched'),
      ),
    );
EOT;
  // bad start symbol
  $code = $define_states . 'new Parser("bar: A;foo: bar | B", new YAScanner("init", $ersatz_states));';
  testException('Expect unterminated definition exception',  $code );
  $code = $define_states . 'new Parser("%start foo bar: A;", new YAScanner("init", $ersatz_states));';
  testException('Expect exception on bad start symbol',  $code );
  $code = $define_states . 'new Parser("foo: bar A; barr: foo | barf;", new YAScanner("init", $ersatz_states));';
  testException('Expect undefined terminals exception', $code);

  testReport();
}

// function first() section
if ($test_all || $test_first) {
  $ersatz_states =<<< EOT
    array(
      array('init','emit_error.an error',
        array('/^.*/', 'init', 'discard_matched'),
      ),
    );
EOT;

  // array(language, array( of first's for each non-terminal ))
  $languages = array(
    array('foo1: T;', array('foo1' => array('T'),)),
        
    array('foo2: bar ; bar : T;',
      array(
        'foo2' => array('T'),
        'bar' => array('T'))),
        
    array('foo3: T F | bar F; bar: TT foo3 | TF;',
      array(
        'foo3' => array('T', 'TT', 'TF'),
        'bar' => array('TT', 'TF'),)),
    
    array('has_empty : has_empty T |;',
      array( 'has_empty' => TRUE),),
    
    array("expr: expr op expr | '(' expr ')' | NUM; op: '+' | '-';",
      array(
        'expr' => array('(', 'NUM', ),
        'op' => array('+', '-'),)),
    
    array("left_recursive: left_recursive item | item; item: THING left_recursive ;",
      array(
        'left_recursive' => array('THING'),
        'item' => array('THING'),)),

    // can't be tested. the parser picks up the mutual recursion and barfs
    // array('mut_recurs : bar; bar: mut_recurs;',
    //   array(
    //     'mut_recurs' => FALSE,
    //     'bar' => FALSE,
    //   ),
    // ),
  );
  
  foreach ($languages as $row) {
    $language = array_shift($row);
    $expected = array_shift($row);
    $parser = new ParserLangDefParser($language);
    echo "\nTesting first functions for: $language\n";
    foreach (array_keys($parser->language_ar) as $non_terminal) {
      if ($expected && $expected[$non_terminal]) {
        $first_tmp = $parser->first($non_terminal);
        if (!testTrue("first($non_terminal): {$parser->display_first($non_terminal)}", $first_tmp == $expected[$non_terminal])) {
          echo "First Failed to match Expected for $non_terminal\nfirst(): "; var_dump($first_tmp);
          echo "expected:"; var_dump($expected[$non_terminal]);
        }
      } else {
        echo $parser->display_first($non_terminal);
        // var_dump($parser->first($non_terminal));
      }
    }
  }
  
  testReport();
}

// Parser Section
if ($test_all || $test_languages || $test_this_language) {
  echo "\nLanguage Section\n";
  // Capitals are terminals; lower case are non-terminals; methods implement productions
  $languages = array(
    'c' => 'c : A /* a comment */;',
    't' => 't : A t | ;',
    'u' => 'u : A u | A;',
    'v' => 'v : A B v | A B;',
    'w' => 'w : A B C w | A B C;',
    'x' => 'x : A B C x | ;',
    'y' => 'y : A y C | X;',
  
    's' => 's : A s B | A B;',
  
    'a' => 'a : A b | X; b : B a;',
    'b' => '%start b a : A b | X; b : B a;',
  
    'lit' => "s : a s | a ; a : A a | A ';' ;",
  );

  $tests = array(
    'a' => array(
      array('X', TRUE),
      array('AX', FALSE),
      array('ABX', TRUE),
      array('ABAX', FALSE),
      array('ABABX', TRUE)
    ),
    'b' => array(
      array('BX', TRUE),
      array('BABX', TRUE),
    ),
    'c' => array(array('A', TRUE), array('B', FALSE), array('AA', FALSE)),
    's' => array(
      array('AB', TRUE),
      array('AABB', TRUE),
      array('AAABBB', TRUE),
      array('ABC', FALSE),
      array('AA', FALSE),
    ),
    't' => array(
      array('', TRUE),
      array('A', TRUE),
      array('AA', TRUE),
      array('AAA', TRUE),
      array('AAAA', TRUE),
      array('AB', FALSE),
    ),
    'u' => array(
      array('', FALSE),
      array('A', TRUE),
      array('AA', TRUE),
      array('AAA', TRUE),
      array('AAAA', TRUE),
      array('AB', FALSE),
    ),
    'v' => array(
      array('', FALSE),
      array('A', FALSE),
      array('B', FALSE),
      array('AB', TRUE),
      array('ABAB', TRUE),
      array('ABABAB', TRUE),
      array('ABC', FALSE),
    ),
    'w' => array(
      array('', FALSE),
      array('A', FALSE),
      array('B', FALSE),
      array('C', FALSE),
      array('AB', FALSE),
      array('ABAB', FALSE),
      array('ABABAB', FALSE),
      array('ABC', TRUE),
      array('ABCABC', TRUE),
      array('ABCABCABC', TRUE),
    ),
    'x' => array(
      array('', TRUE),
      array('A', FALSE),
      array('B', FALSE),
      array('AB', FALSE),
      array('ABC', TRUE),
      array('ABCAB', FALSE),
      array('ABCABCABC', TRUE),
    ),
    'y' => array(
      array('', FALSE),
      array('X', TRUE),
      array('AXC', TRUE),
      array('AXXC', FALSE),
      array('AXXXC', FALSE),
      array('AAXCC', TRUE),
      array('AAAXCCC', TRUE),
    ),
    'lit' => array(
      array('', FALSE),
      array('A', FALSE),
      array('A;', TRUE),
      array('AA;', TRUE),
      array('AAA;', TRUE),
      array('AAA;;', FALSE),
      array('AA;A;AAA;', TRUE),
    ),
    // 'Z' => array(),
  );

  $states = array(
    array('init', 'emit_error.no next state found',
      array('/^\s*([A-Z;])/', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
      array('/^\s*([a-z])/', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
      array('/^\s*(.)/', 'init', 'push_tag.error,add_matched.1,emit,pop_tag'),
    ),
  );
  $scanner = new YAScanner('init', $states);

  // single language / sentence test
  if ($test_this_language) {
    $parser = new Parser($languages[$test_this_language], $scanner);
    $parser->verbose = TRUE;
    $parser->parse($test_this_sentence);
    echo $parser->str_valid ? "$test_this_sentence is valid in $test_this_language\n" : "$test_this_sentence is NOT valid in $test_this_language\n";
    return;
  }

  // Otherwise:
  foreach ($languages as $lang => $grammar) {
    echo "\nParsing for language: $lang: $grammar\n";
    $parser = new Parser($grammar, $scanner);
    $parser->verbose = $verbose;
    foreach ($tests[$lang] as $row) {
      list($str, $expect) = $row;
      $parser->parse($str);
      if ($expect) {
        $tmp = testTrue("$str is valid in language $lang", $parser->str_valid);
      } else {
        $tmp = testFalse("$str is invalid in language $lang", $parser->str_valid);
      }
      if (!$tmp) {
        echo $parser->render();
        // echo $parser->language->dump();
      }
    }
  }
} // end Parser Section

if ($test_all || $test_infinite_recursion) {
// infinite recusion test
$recursion_test =<<<EOT
  \$states = array(
    array('init', 'emit_error.no next state found',
      array('/^\s*([A-Z])/i', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
    ),
  );
  \$scanner = new YAScanner('init', \$states);
  \$parser = new Parser('a: a X | X;', \$scanner);
  // \$parser->verbose = TRUE;
  \$parser->parse('X');
EOT;
  // echo $recursion_test;
  testException('Exception on infinite recursion', $recursion_test);
  // eval($recursion_test);

  testReport();
}  // Infinite recursion trap test


// execution string quoting section
if ( $test_all || $test_quoted_strings ) {
  echo "Execution String Quote Language Dev + Test\n";
  $states = array(
    array('init', 'emit_error.ill formed string',
      array('/^\s+$/', 'init', 'push_tag.WHITESPACE,add_matched,emit,pop_tag'),
      array('/^\'/', 'sq', 'add_matched'),
      array('/^"/', 'dq', 'add_matched'),
      array('/^(?U).+(?=\\\'|\\")/', 'init', 'push_tag.TEXT,add_matched,emit,pop_tag'),
      array('/^.*$/', 'init', 'push_tag.TEXT, add_matched,emit,pop_tag'),
    ),
    array('sq', 'emit_error.ill formed string',
      array('/^\'/', 'init', 'add_matched,push_tag.QUOTED,emit,pop_tag'),
      array('/^\\\'/', 'sq', 'add_matched'),
      array('/^./', 'sq', 'add_matched'),
    ),
    array('dq', 'emit_error.ill formed string',
      array('/^"/', 'init', 'add_matched,push_tag.QUOTED,emit,pop_tag'),
      array('/^\\\"/', 'dq', 'add_matched'),
      array('/^./', 'dq', 'add_matched'),
    ),
  );

  $language ='
  %start ss
  ss : s ss %str{ @1 @2 } | s %str{ @1 };
  s : TEXT %str{ @1 }| QUOTED %str{ @1 } | WHITESPACE %str{ @1 }
  ;';
  
  $test_data = array(
    '   ',
     ' this is text',
     "'foo'",
     '"foo"',
     "some text followed by 'single quoted stuff' and some crud \"in double quotes\"",
  );


  $scanner = new YAScanner('init', $states);
  // echo $scanner->dump();
  $parser = new Parser($language, $scanner);
  $parser->verbose = $verbose;
  foreach ($test_data as $test) {
    list($str, $expect) = is_array($test) ? $test : array($test, FALSE);
    testTrue("able to parse '$str'", $parser->parse($str));
    if (!testTrue("Round Trip Test", $parser->render() == $str)) {
      echo "Expected: '$str'\n";
      echo "Received: '{$parser->render()}'\n";
    }
  }
}

// Execute Section
if ($test_all || $test_execute) {
  echo "\nExecute Section\n";

  $states = array(
    array('init', 'emit_error.no next state found',
      array('/^\s*([A-Z])/', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
      array('/^\s*([a-z])/', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
      array('/^\s*([[:punct:]])/', 'init', 'push_tag_matched.1, add_matched.1,emit,pop_tag'),
      array('/^\s*(.)/', 'init', 'push_tag.terminal,add_matched.1,emit,pop_tag'),
    ),
  );
  $scanner = new YAScanner('init', $states);

  $language =<<<EOT
  %action_prefix{
  \$foo = "action prefix / ";
  %}
  %action_suffix{
    \$bar = " / action suffix";
    @@ = "{\$foo}{\$baz}{\$bar}";
  %}
  %start a
  a: A %php{ \$baz = "This is @1"; };
EOT;

  $parser = new Parser($language, $scanner);
  testTrue('Parser parsed trivial language', $parser->parse('A'));
  // echo $parser->root->dump('trivial language');
  $output = $parser->render();
  $expected = 'action prefix / This is A / action suffix';
  if (!testTrue('action prefix and suffix execute', $output == $expected)) {
    echo "Expected: '$expected'\nReceived: '$output'\n";
  }

  echo "\nTesting Language with %string and %php actions\n";
  $language =<<< EOT
  %start ss
  ss : s ss (X) %string{ @1 X } |  ;
  s (S) : A B ';' %string{ @1 @2 } %php{ \$__context->prod = 'production 1'; }
    | B (b) C (c) ';' %string{ b c } %php{ \$__context->prod = 'production 2'; }
    | D (d) %php{ return 'fo\'o'; } (x) F ';' %php{ S = 's value: ' . d . x . @3; }
         %php{ \$__context->prod = 'production 3'; }
    | A B C ';' %str{ @1 @2 @3 } %str{@5 @5} %php{ \$__context->prod = 'production 4'; }
    | error %str{ @1 }
    ;
EOT;

  class A {
    public $prod;
  }
  $a = new A();

  $test_data = array(
    array('A B ;', 'AB', 'production 1'),
    array('B C ;', 'BC', 'production 2'),
    array('AB;AB;', 'ABAB', 'production 1'),
    array('D F ;', 's value: Dfo\'oF', 'production 3'),
    array('A B X', '', ''),
    array('A B C ;', 'ABCABC', 'production 4'),
    // array('', ''),
    // array('', ''),
    // array('', ''),
  );

  $parser = new Parser($language, $scanner, $a);
  // $parser->verbose = TRUE ; // $verbose;
  // echo $parser->language->language_def;
  echo "$parser->language\n";

  foreach ($test_data as $row) {
    list($str, $expect, $context) = $row;
    if ($parser->parse($str)) {
      // echo $parser->display_parser_state('foo');
      if (!testTrue("rendering '$str': expect '$expect'", $expect == trim($parser->render()))) {
        echo "\$parser->render(): '{$parser->render()}'\n";
      } else {
        testTrue("context->prod == $context", $a->prod == $context);
      }
    } else {
      echo "Unable to parse '$str'\n";
      $parser->verbose = TRUE;
      $parser->parse($str);
      $parser->verbose = FALSE;
      echo $parser->render() . "\n";
      echo $parser->dump();
      // $parser->verbose = TRUE;
      // $parser->parse($str);
      // $parser->verbose = FALSE;
    }
  }

  testReport();
}

if ($test_all || $test_error_handling) {
  echo "Error Handling Section\n";
  
  $states = array(
    array('init', 'emit_error.illegal token',
      array('/^(a)\s*/', 'init', 'push_tag.A,add_matched.1,emit,pop_tag'),
      array('/^(word)\s*/', 'init', 'push_tag.WORD,add_matched.1,emit,pop_tag'),
      array('/^(more)\s*/', 'init', 'push_tag.MORE,add_matched.1,emit,pop_tag'),
      array('/^(stuff)\s*/', 'init', 'push_tag.STUFF,add_matched.1,emit,pop_tag'),
      array('/^\s+/', 'init', 'push_tag.WHITE,add_matched,emit,pop_tag'),
    ),
  );
  $scanner = new YAScanner('init', $states);
  
  $language = "
    %start ss
    ss : token ss %str{ @1 @2 }
        | token %str{ @1 }
        | error
        ;
    token : WHITE %str{ @1 }
        | A %str{ @1 }
        | WORD %str{ @1 }
        | MORE %str{ @1 }
        ;
  ";
  // $scanner->verbose = TRUE;
  $parser = new Parser($language, $scanner, NULL, $verbose);

  $test_data = array(
    array('   ', TRUE),
    array('a word', TRUE),
    array("\n\n\n!#$", FALSE),
    array("a\nword\nng!\nmore\n", FALSE),
    array('stuff', FALSE),
  );
  
  foreach ($test_data as $test) {
    list($str, $parse_return) = $test;
    if ($parse_return) {
      testTrue("Can Parse '$str'", $parser->parse($str));
    } else {
      testFalse("Cannot Parse '$str'", $parser->parse($str));
    }
    // echo $parser->scanner->dump();
    echo "rendering: '{$parser->render()}'\n";
    // echo $parser->dump("Parse of '$str'");
  }
}


testReport();