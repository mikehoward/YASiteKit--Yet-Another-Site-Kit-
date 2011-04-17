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

$progname = basename(array_shift($argv));

// testing YAScanner
function test_state_def($title, $states, $test_data) {
  global $verbose;
  
  echo "\n$title\n";
  $scanner = new YAScanner('init', $states, 'scanner_error');
  // allow lots of errors to be detected
  $scanner->error_count_limit = 100;
  if ($verbose) {
    $scanner->verbose = TRUE;
    echo $scanner->dump(__LINE__);
  }
  foreach ($test_data as $row) {
      list($str, $expected) = $row;
      $scanner->process($str);
      echo "processing '$str'\n";
      foreach ($expected as $row) {
        if (is_array($row) && count($row) == 2) {
          $tmp = $scanner->token();
          // discard line number
          array_pop($tmp);
          if (!testTrue("found {$row[0]} / '{$row[1]}'", $tmp == $row)) {
            var_dump($scanner->token());
            var_dump($row);
            echo $scanner->dump();
          }
        } else {
          if (!testTrue("found $row", $scanner->token() == $row)) {
            var_dump($scanner->token());
            var_dump($row);
            echo $scanner->dump();
          }
        }
        $scanner->advance();
      }
  }
}

// Simple Scanner - almost vacuous
$some_states = array(
  array('init', 'emit_error.bad sequence',
    array('/^\s*([A-Z])\b/', 'init', 'push_tag_matched.1,add_matched.1,emit,pop_tag'),
    array('/^\s*([a-z])\b/', 'init', 'push_tag_matched.1,add_matched.1,emit.pop_tag'),
    array('/^\s*./', 'init', 'push_tag.error, add_matched, emit,pop_tag'),
  ),
);

$test_data = array(
  array('A B', array(array('A','A'), array('B','B'))),
  array('a b C', array(array('a','a'), array('b','b'), array('C','C'))),
  array('ab', array(array('error', 'a'), array('b', 'b'))),
);
test_state_def('Simple Scanner', $some_states, $test_data);

// chunk state tests
$chunk_states = array(
  array('init', 'emit_error.Unable to proceed: no HTML, PHP, or YATheme detected',
    array("/^<\\\x3fphp\\s/", 'php', 'push_tag.php, add_matched'),
    array('/^{:/', 'yat', 'push_tag.yat, add_matched'),
    array('/^(?s)./', 'html', 'push_tag.html, add_matched'),
  ),
  array('html', 'add_matched',
    array("/^<\\\x3fphp\\s/", 'php', 'emit,pop_tag, push_tag.php, add_matched'),
    array('/^{:/', 'yat', 'emit, pop_tag, push_tag.yat,add_matched'),
  ),
  array('php', 'add_matched',
    // array('/^{:/', 'yat', 'push_context, emit, push_tag.yat, add_matched'),
    array('/^{:/', 'php', 'emit_error.Illegal YATheme escape in PHP escape, add_matched'),
    array("/^\\\x3f>/", 'init', 'add_matched, emit, pop_tag'),
  ),
  array('yat', 'add_matched',
    array('/^:}/', 'init', 'add_matched, emit, pop_tag'),
  ),
);

$chunk_states_terminals = array('php', 'yat', 'html','error');

$chunk_test_data = array(
  array('<p>This is html</p>', array(array('html', '<p>This is html</p>'))),
  array("<\x3fphp echo \"foo\";\x3f>", array(array('php', "<\x3fphp echo \"foo\";\x3f>"))),
  array('{:comment foo:}', array(array('yat', '{:comment foo:}'))),
  array("<\x3fphp echo {:\$foo:};\x3f>",
    array(
      array('scanner_error', 'file -:near line 1: Illegal YATheme escape in PHP escape: \'{:$foo:};?>\''),
      array('php', "<\x3fphp echo {:\$foo:};\x3f>"))),
  array('<p>This is html</p>' . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),
    )),
  array('<p>This is html</p>' . '{:comment foo:}',
    array(array('html', '<p>This is html</p>'), array('yat', '{:comment foo:}'))),
  array("<\x3fphp echo \"foo\";\x3f>" . '{:comment foo:}',
    array(array('php', "<\x3fphp echo \"foo\";\x3f>"), array('yat', '{:comment foo:}'))),
  array('<p>This is html</p>' . '{:comment foo:}' . "<\x3fphp echo \"foo\";\x3f>",
    array(array('html', '<p>This is html</p>'), array('yat', '{:comment foo:}'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  // array('{:comment foo:}', array(array('yat', '{:comment foo:}'))),
  array('<p>This is html</p>' . '{:annotation:}this is an annotation{:end-annotation:}',
    array(array('html', '<p>This is html</p>'),
    array('yat', '{:annotation:}'),
    array('html', 'this is an annotation'),
    array('yat', '{:end-annotation:}'))),
  array("<\x3fphp echo \"foo\";\x3f>" . '{:annotation:}this is an annotation{:end-annotation:}',
    array(array('php', "<\x3fphp echo \"foo\";\x3f>"),
    array('yat', '{:annotation:}'),
    array('html', 'this is an annotation'),
    array('yat', '{:end-annotation:}'))),
  array('<p>This is html</p>' . '{:annotation:}this is an annotation{:end-annotation:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(array('html', '<p>This is html</p>'),
    array('yat', '{:annotation:}'),
    array('html', 'this is an annotation'),
    array('yat', '{:end-annotation:}'),
    array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
);

test_state_def("Testing chunk states", $chunk_states, $chunk_test_data);

$yat_grammar_states = array(
  array('init', 'emit_error.no yat starting token found',
    array('/^{:\s*(comment)\b\s*/', 'yat-comment', 'discard_matched'),
    array('/^{:\s*(yatheme)\s+/', 'yat-yatheme', 'discard_matched'),
    array('/^{:\s*(guards)\s+/', 'yat-guards', 'discard_matched'),
    array('/^{:\s*(yatemplate)\s+/', 'yat-template', 'discard_matched'),
    array('/^{:\s*(yatemplate-content)\b\s*:}/', 'init', 'push_tag.yatemplate-content, add_matched.1, emit, pop_tag'),
    array('/^{:\s*(authority)\s*/', 'yat-authority', 'discard_matched'),
    array('/^{:\s*(errors)\s+/', 'yat-errors', 'discard_matched'),
    array('/^{:\s*(set-scope)\s+/', 'yat-set-scope', 'discard_matched'),
    array('/^{:\s*(include)\s+/', 'yat-include', 'discard_matched'),
    array('/^{:\s*(scope)\s+/', 'yat-scope', 'discard_matched'),
    array('/^{:\s*(end-scope)\b\s*:}/', 'init', 'push_tag.scope-end,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(annotation)\b\s*:}/', 'init', 'push_tag.annotation-start,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(end-annotation)\b\s*:}/', 'init', 'push_tag.annotation-end,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(if)\b\s*/', 'yat-condition', 'push_tag.if,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(elseif)\b\s*/', 'yat-condition', 'push_tag.elseif,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(else)\b\s*:}/', 'init', 'push_tag.else,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(end-if)\b\s*/', 'init', 'push_tag.end-if,add_matched.1,emit,pop_tag'),
    // array('/^{:\s*()\s*/', '', ''),
  ),
  array('yat-comment', 'emit_error.no end of comment detected',
    array('/^(?U).*(:})/', 'init', 'discard_matched')),
  array('yat-yatheme', 'emit_error.illegal yatheme command',
    array('/^(on|off)\s*:}/', 'init', 'push_tag.yatheme, add_matched.1, emit, pop_tag')),
  array('yat-guards', 'emit_error.illegal guard command or missing YATheme terminator',
    array('/^(paranoid|normal|off)\s*:}/', 'init', 'push_tag.guards, add_matched.1, emit, pop_tag')),
  array('yat-template', 'emit_error.illegal yatemplate command',
    array('/^([-.\w]+)\s*:}/', 'init', 'push_tag.yatemplate-file, add_matched.1, emit, pop_tag')),
  array('yat-authority', 'emit_error.illegal or improper YATheme authority command',
    array('/^((A|C|V|M|S|X|ANY)(\s*,\s*(A|C|V|M|S|X|ANY))*)\s*:}/', 'init', 'push_tag.authority,add_matched.1,emit,pop_tag')),
  array('yat-errors', 'emit_error.Illegal YATheme errors command',
    array('/^(display|ignore)\s*:}/', 'init', 'push_tag.errors,add_matched.1,emit,pop_tag'),
    array('/^email\s+([-.\w]+@[-.\w]+)\s*:}/', 'init', 'push_tag.errors-email,add_matched.1,emit,pop_tag')),
  array('yat-set-scope', 'emit_error.illegal YATheme set-scope command or missing y-brace',
    array('/^(\w+(\s*,\s*\w+)*)\s*:}/', 'init', 'push_tag.set-scope,add_matched.1,emit,pop_tag')),
  array('yat-include', 'emit_error.illegal YATheme include command or missing y-brace',
    array('/^([-.\w]+)\b\s*:}/', 'init', 'push_tag.include-file,add_matched.1,emit,pop_tag')),
  array('yat-scope', 'emit_error.illegal scope command or missing y-brace',
    array('/^(\w+(\s*,\s*\w+)*)\s*:}/', 'init', 'push_tag.scope-start,add_matched.1,emit,pop_tag')),
  array('yat-condition', 'emit_error.illegal if command or missing y-brace',
    array('/^(exists)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-exists, add_matched.2,emit, pop_tag'),
    array('/^(isset|is_set|is-set)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-is-set, add_matched.2,emit, pop_tag'),
    array('/^(null)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-null, add_matched.2,emit, pop_tag'),
    array('/^(notnull|not-null|not_null)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-not-null, add_matched.2,emit, pop_tag'),
    array('/^(false)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-is-false, add_matched.2,emit, pop_tag'),
    array('/^(not_false|not-false)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-not-false, add_matched.2,emit, pop_tag'),
    array('/^((?U).+)\s*==\s*((?U).+)\s*:}/', 'init', 'push_tag.attr-eq, add_matched.1,add_literal.==,add_matched.2,emit, pop_tag'),
    array('/^((?U).+)\s*!=\s*((?U).+)\s*:}/', 'init', 'push_tag.attr-neq, add_matched.1,add_literal.!=,add_matched.2,emit, pop_tag'),
    ),
  // array('yat-', 'emit_error.',
  //   array('/()\s*:}/', 'init', '')),
);

$yat_test_data = array(
  array('{:comment this is a comment :}',  array( array(),), ),
  array('{: yatheme on :}', array(array('yatheme', 'on'))),
  array('{: yatheme off :}', array(array('yatheme', 'off'))),
  
  array('{: guards paranoid :}', array(array('guards', 'paranoid'))),
  array('{: guards normal:}', array(array('guards', 'normal'))),
  array('{: guards off:}', array(array('guards', 'off'))),
  array('{: guards on:}', array(array('scanner_error', 'file -:near line 1: illegal guard command or missing YATheme terminator: \'on:}\''))),

  array('{: yatemplate filename:}', array(array('yatemplate-file', 'filename'))),
  
  array('{: yatemplate-content   :}', array(array('yatemplate-content', 'yatemplate-content'))),
  
  array('{: authority A:}', array(array('authority', 'A'))),
  array('{: authority C:}', array(array('authority', 'C'))),
  array('{: authority M:}', array(array('authority', 'M'))),
  array('{: authority V:}', array(array('authority', 'V'))),
  array('{: authority S:}', array(array('authority', 'S'))),
  array('{: authority X:}', array(array('authority', 'X'))),
  array('{: authority ANY:}', array(array('authority', 'ANY'))),
  array('{: authority A,C,M,V,S,X:}', array(array('authority', 'A,C,M,V,S,X'))),
  array('{: authority A,C,M,V , S , X :}', array(array('authority', 'A,C,M,V , S , X'))),


  array('{: errors display:}', array(array('errors', 'display'))),
  array('{: errors ignore:}', array(array('errors', 'ignore'))),
  array('{: errors email foo@example.com :}', array(array('errors-email', 'foo@example.com'))),
  
  array('{:set-scope foo:}', array(array('set-scope', 'foo'))),
  array('{:set-scope foo, bar, baz:}', array(array('set-scope', 'foo, bar, baz'))),
  
  array('{: include foo.bar :}', array(array('include-file', 'foo.bar'))),
  array('{: include ../foo.bar :}', array(array('scanner_error', 'file -:near line 1: illegal YATheme include command or missing y-brace: \'../foo.bar :}\''))),
  
  array('{: scope foo,bar:}', array(array('scope-start', 'foo,bar'))),
  array('{: scope :}', array(array('scanner_error', 'file -:near line 1: illegal scope command or missing y-brace: \':}\''))),
  array('{:end-scope:}', array(array('scope-end', 'end-scope'))),
  array('{: annotation :}', array(array('annotation-start', 'annotation'))),
  array('{: end-annotation :}', array(array('annotation-end', 'end-annotation'))),

  array('{:if exists $a:}', array(array('if', 'if'), array('attr-exists', '$a'))),
  array('{:if isset $a:}', array(array('if', 'if'), array('attr-is-set', '$a'))),
  array('{:if is-set $a:}', array(array('if', 'if'), array('attr-is-set', '$a'))),
  array('{:if is_set $a:}', array(array('if', 'if'), array('attr-is-set', '$a'))),
  array('{:if null $a:}', array(array('if', 'if'), array('attr-null', '$a'))),
  array('{:if not_null $a:}', array(array('if', 'if'), array('attr-not-null', '$a'))),
  array('{:if not-null $a:}', array(array('if', 'if'), array('attr-not-null', '$a'))),
  array('{:if false $a:}', array(array('if', 'if'), array('attr-is-false', '$a'))),
  array('{:if not-false $a:}', array(array('if', 'if'), array('attr-not-false', '$a'))),
  array('{:if not_false $a:}', array(array('if', 'if'), array('attr-not-false', '$a'))),
  array('{:if A::$b == foo bar :}', array(array('if', 'if'), array('attr-eq', 'A::$b==foo bar'))),
  array('{: if $a != $c :}', array(array('if', 'if'), array('attr-neq', '$a!=$c'))),
  
  array('{:elseif exists $a:}', array(array('elseif', 'elseif'), array('attr-exists', '$a'))),
  array('{:elseif isset $a:}', array(array('elseif', 'elseif'), array('attr-is-set', '$a'))),
  array('{:elseif is-set $a:}', array(array('elseif', 'elseif'), array('attr-is-set', '$a'))),
  array('{:elseif is_set $a:}', array(array('elseif', 'elseif'), array('attr-is-set', '$a'))),
  array('{:elseif null $a:}', array(array('elseif', 'elseif'), array('attr-null', '$a'))),
  array('{:elseif not_null $a:}', array(array('elseif', 'elseif'), array('attr-not-null', '$a'))),
  array('{:elseif not-null $a:}', array(array('elseif', 'elseif'), array('attr-not-null', '$a'))),
  array('{:elseif false $a:}', array(array('elseif', 'elseif'), array('attr-is-false', '$a'))),
  array('{:elseif not-false $a:}', array(array('elseif', 'elseif'), array('attr-not-false', '$a'))),
  array('{:elseif not_false $a:}', array(array('elseif', 'elseif'), array('attr-not-false', '$a'))),
  array('{:elseif A::$b == foo bar :}', array(array('elseif', 'elseif'), array('attr-eq', 'A::$b==foo bar'))),
  array('{: elseif $a != $c :}', array(array('elseif', 'elseif'), array('attr-neq', '$a!=$c'))),
  
  array('{:else:}', array(array('else', 'else'))),
  array('{: end-if :}', array(array('end-if', 'end-if'))),
  // array('{: :}', array(array('', ''))),
  // array('{: :}', array(array('', ''))),
  // array('{: :}', array(array('', ''))),
  // array('{: :}', array(array('', ''))),
  // array('{: :}', array(array('', ''))),
);
test_state_def("Testing yat state data", $yat_grammar_states, $yat_test_data);


// these are the real states we use to parse combined HTML, PHP, and YATHEME
$states = array(
  array('init', 'emit_error.Unable to proceed: no HTML, PHP, or YATheme detected',
    array("/^<\\\x3fphp\\s/", 'php', 'add_matched'),
    // array('/^{:/', 'yat', 'push_tag.yat, add_matched'),
    array('/^{:/', 'yat-init', 'no_advance'),
    array('/^(?s)./', 'html', 'add_matched'),
  ),
  array('html', 'add_matched',
    array("/^<\\\x3fphp\\s/", 'php', 'push_tag.html,emit,pop_tag,add_matched'),
    // array('/^{:/', 'yat', 'push_context, emit, push_tag.yat, add_matched'),
    array('/^{:/', 'yat-init', 'push_tag.html,emit,pop_tag, no_advance'),
  ),
  array('php', 'add_matched',
    // array('/^{:/', 'yat', 'push_context, emit, push_tag.yat, add_matched'),
    array('/^{:/', 'php', 'emit_error.Illegal YATheme escape in PHP escape, add_matched'),
    array("/^\\\x3f>/", 'init', 'add_matched, push_tag.php,emit, pop_tag'),
  ),
  array('yat-init', 'emit_error.no yat starting token found or bad YATheme command',
    array('/^{:\s*(comment)\b\s*/', 'yat-comment', 'discard_matched'),
    array('/^{:\s*(yatheme)\s+/', 'yat-yatheme', 'discard_matched'),
    array('/^{:\s*(guards)\s+/', 'yat-guards', 'discard_matched'),
    array('/^{:\s*(yatemplate)\s+/', 'yat-template', 'discard_matched'),
    array('/^{:\s*(yatemplate-content)\b\s*:}/', 'init', 'push_tag.yatemplate-content, add_matched.1, emit, pop_tag'),
    array('/^{:\s*(authority)\s*/', 'yat-authority', 'discard_matched'),
    array('/^{:\s*(errors)\s+/', 'yat-errors', 'discard_matched'),
    array('/^{:\s*(set-scope)\s+/', 'yat-set-scope', 'discard_matched'),
    array('/^{:\s*(include)\s+/', 'yat-include', 'discard_matched'),
    array('/^{:\s*(scope)\s+/', 'yat-scope', 'discard_matched'),
    array('/^{:\s*(end-scope)\b\s*:}/', 'init', 'push_tag.scope-end,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(annotation)\b\s*:}/', 'init', 'push_tag.annotation-start,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(end-annotation)\b\s*:}/', 'init', 'push_tag.annotation-end,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(if)\b\s*/', 'yat-condition', 'push_tag.if,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(elseif)\b\s*/', 'yat-condition', 'push_tag.elseif,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(else)\b\s*:}/', 'init', 'push_tag.else,add_matched.1,emit,pop_tag'),
    array('/^{:\s*(end-if)\b\s*:}/', 'init', 'push_tag.end-if,add_matched.1,emit,pop_tag'),
    array('/^{:\s*([A-Z]\w*)::(\$FIXME!!!!)\s*:}/')
    // array('/^{:\s*()\s*/', '', ''),
  ),
  array('yat-comment', 'emit_error.no end of comment detected',
    array('/^(?U).*(:})/', 'init', 'discard_matched')),
  array('yat-yatheme', 'emit_error.illegal yatheme command or missing terminator',
    array('/^(on|off)\s*:}/', 'init', 'push_tag.yatheme, add_matched.1, emit, pop_tag')),
  array('yat-guards', 'emit_error.illegal guard command or missing YATheme terminator',
    array('/^(paranoid|normal|off)\s*:}/', 'init', 'push_tag.guards, add_matched.1, emit, pop_tag')),
  array('yat-template', 'emit_error.illegal yatemplate command or missing terminator',
    array('/^([-.\w]+)\s*:}/', 'init', 'push_tag.yatemplate-file, add_matched.1, emit, pop_tag')),
  array('yat-authority', 'emit_error.illegal or improper YATheme authority command',
    array('/^((A|C|V|M|S|X|ANY)(\s*,\s*(A|C|V|M|S|X|ANY))*)\s*:}/', 'init', 'push_tag.authority,add_matched.1,emit,pop_tag')),
  array('yat-errors', 'emit_error.Illegal YATheme errors command or missing terminator',
    array('/^(display|ignore)\s*:}/', 'init', 'push_tag.errors,add_matched.1,emit,pop_tag'),
    array('/^email\s+([-.\w]+@[-.\w]+)\s*:}/', 'init', 'push_tag.errors-email,add_matched.1,emit,pop_tag')),
  array('yat-set-scope', 'emit_error.illegal YATheme set-scope command or missing terminator',
    array('/^(\w+(\s*,\s*\w+)*)\s*:}/', 'init', 'push_tag.set-scope,add_matched.1,emit,pop_tag')),
  array('yat-include', 'emit_error.illegal YATheme include command or missing terminator',
    array('/^([-.\w]+)\b\s*:}/', 'init', 'push_tag.include-file,add_matched.1,emit,pop_tag')),
  array('yat-scope', 'emit_error.illegal scope command or missing terminator',
    array('/^(\w+(\s*,\s*\w+)*)\s*:}/', 'init', 'push_tag.scope-start,add_matched.1,emit,pop_tag')),
  array('yat-condition', 'emit_error.illegal if command or missing terminator',
    array('/^(exists)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-exists, add_matched.2,emit, pop_tag'),
    array('/^(isset|is_set|is-set)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-is-set, add_matched.2,emit, pop_tag'),
    array('/^(null)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-null, add_matched.2,emit, pop_tag'),
    array('/^(notnull|not-null|not_null)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-not-null, add_matched.2,emit, pop_tag'),
    array('/^(false)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-is-false, add_matched.2,emit, pop_tag'),
    array('/^(not_false|not-false)\b\s+((?U).+)\s*:}/', 'init', 'push_tag.attr-not-false, add_matched.2,emit, pop_tag'),
    array('/^((?U).+)\s*==\s*((?U).+)\s*:}/', 'init', 'push_tag.attr-eq, add_matched.1,add_literal.==,add_matched.2,emit, pop_tag'),
    array('/^((?U).+)\s*!=\s*((?U).+)\s*:}/', 'init', 'push_tag.attr-neq, add_matched.1,add_literal.!=,add_matched.2,emit, pop_tag'),
    ),
  // array('yat-', 'emit_error.',
  //   array('/()\s*:}/', 'init', '')),
);


$real_test_data = array(
  array('<p>This is html</p>' . '{:comment this is a comment :}' . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),
  )),
  array('<p>This is html</p>' . '{: yatheme on :}' . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('yatheme', 'on'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),
  )),
  array('<p>This is html</p>' . '{: yatheme off :}' . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('yatheme', 'off'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),
  )),
  array('<p>This is html</p>' . '{:comment foo:}' . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  array('<p>This is html</p>' . '{:annotation:}this is an annotation{:end-annotation:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('annotation-start', 'annotation'),
      array('html', 'this is an annotation'),
      array('annotation-end', 'end-annotation'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  array('<p>This is html</p>'
        . '{:if exists $a:}$a exists{:elseif false $b:}$b is false{:else:}$a is not to be found and $b is not false{:end-if:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('if', 'if'),
      array('attr-exists', '$a'),
      array('html', '$a exists'),
      array('elseif', 'elseif'),
      array('attr-is-false', '$b'),
      array('html', '$b is false'),
      array('else', 'else'),
      array('html', '$a is not to be found and $b is not false'),
      array('end-if', 'end-if'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  array('<p>This is html</p>'
        . '{:scope a,b,c:}This stuff shows up in scopes a, b, and c{:end-scope:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('scope-start', 'a,b,c'),
      array('html', 'This stuff shows up in scopes a, b, and c'),
      array('scope-end', 'end-scope'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  array('<p>This is html</p>'
        . '{:include foo.php:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('include-file', 'foo.php'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  array('<p>This is html</p>'
        . '{:guards paranoid:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('guards', 'paranoid'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  //
  array('<p>This is html</p>'
        . '{:yatemplate bar.tpl:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('yatemplate-file', 'bar.tpl'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  //
  array('<p>This is html</p>'
        . '{:yatemplate-content:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('yatemplate-content', 'yatemplate-content'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  //
  array('<p>This is html</p>'
        . '{:authority X,S:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('authority', 'X,S'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  //
  array('<p>This is html</p>'
        . '{: errors ignore:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('errors', 'ignore'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),
  //
  array('<p>This is html</p>'
        . '{:set-scope a,b:}'
        . "<\x3fphp echo \"foo\";\x3f>",
    array(
      array('html', '<p>This is html</p>'),
      array('set-scope', 'a,b'),
      array('php', "<\x3fphp echo \"foo\";\x3f>"),)),

);
test_state_def("Testing real data", $states, $real_test_data);

testReport();
