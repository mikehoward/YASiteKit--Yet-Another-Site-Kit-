<?php
/*
#begin-doc
h1. athemer.php - implements parsing and stuff for YATheme

Implements basic YATheme compilation to mixed PHP / HTML.

* "YAPHPVarScanner":#ya_php_var_scanner - wraps the "PHP tokenizer":http://php.net/manual/en/book.tokenizer.php
in a YAScannerBase extension so it can be used with the Parser class
* "YAThemeParser":#ya_theme_parser - defines the scanners and grammars needed to parse
mixed PHP / HTML / YATheme text and render them to mixed PHP / HTML.

h2(#ya_php_var_scanner). YAPHPVarScanner

This info is for documentation only. It's unlikely that you will ever instatiate
or use this class directly.

This class wraps the PHP Tokenizer. It works by running the tokenizer on the
entire supplied string and creates an array of YAScanner compatable 'chunks'.

These chunks are 3 element arrays: array(tag, value, line number).
Where the tokenizer returns an array, the tag is supplied by the PHP function _token_name()_
and the value is the string.

Where the tokenizer returns a single character - certain punctuation marks - the
tag is taken from the array _$char_token_names_ and the value is the punctuation mark.

Thus, if the tokenizer returns '$' from line 12, the array is array('T_DOLLAR_SIGN', '$', 12).

h3. Instantiation

pre. $foo = new YAPHPVarScanner(NULL, NULL);

This is the instantiation signature of YAScannerBase, so something must be
supplied for the two parameters - which are ignored by this scanner

h3. Attributes

See "YAScannerBase":/doc.d/system-includes/yascanner.html for the full list.

h3. Class Methods

None

h3. Instance Methods

See "YAScannerBase":/doc.d/system-includes/yascanner.html for the default list.

This class only implements the constructor, process(), and dump().

h2(#ya_theme_parser). YAThemeParser

The YAThemeParser class defines the grammar and YAScanner state transition array for
the YATheme theme language in the static variables YAThemeParser::$yatheme_grammar and
YAThemeParser::$yatheme_states, respectively. It also defines a grammar and scanner for
parsing PHP variables - YAThemeParser::$ya_phpvar_grammar
and the YAPHPVarScanner object.

h3. Instantiation

pre. $foo = new YAThemeParser($file_name);

where _$file_name_ is the name of a file which is on the current include path.
NOTE: Any path information is stripped by passing _$file_name_ through _basename()_.

h3. Instance Attributes

Attributes which are set by the object instance

* rendered_content - string - content of file being processed by _this_ YAThemeParser instance
* file_name - string - file name of file used to instantiate this instance
* file_path - string - absolute path to file
* parse_result - boolean - TRUE if _file_name_ has been parsed correctly. Parsing occurs automatically
* phpvar_parser - Parser instance - parses PHP variables, object instance attributes, and
class static variables. Creates a list of all variables which have to exist in order
to evaluate the variable.
* phpvar_scanner - YAScannerBase instance - translates PHP tokenizer results into YAScanner
tokens.
* verbose - boolean - controls diagnostic output
* yatheme_parser - Parser instance - the Parser object which parses the file content
* yatheme_scanner - YAScannerBase instance - the YAScanner object which provides lexical scanning

Attributes set by YATheme files via YATheme commands

* required_authority - string - required authority to view page. Is stored as defined in the
_authority_ control statement. YASiteKit expects this to be a comma separated list
of authority tokens. See "Account.php":/doc.d/system-objects/Account.html
* errors - string - current error handling method: 'display', 'email', or 'ignore'
* errors_email - string - email address errors are sent to if _errors_ is 'email'
* guards - string - defines how variable/attribute instances are guarded. 
It may be 'paranoid', 'normal' or 'off' - which correspond to different levels of variable name
(and class name) testing.
* scope - array - current scope setting as an array of scope strings. Scopes are set once using the _set-scope_ command as a comma separated list.
if the instance is created with an accessible file OR if _parse_str()_ is called
directly. FALSE if nothing has been parsed yet or the parsing process failed.
* scope - array OR NULL - array of scope strings, if scope has been set
* template_file - string - argment of the _yatemplate_ command. If set, then the the template file will be rendered with the rendering of the current object inserted in place of the _yatemplate-content_ statement. If not set, then the current file will be rendered as is.
* variables - array - associative array of YATheme _var_ variables.
* yatemplate_content - string - rendering of _this_ object which will be inserted in it's template file. Only defined if _template_file_ is defined
* yatheme - string - turns YATheme parsing on and off. Has one of two values: 'on' or 'off'

Attributes which collect information resulting from rendering the page

* all_file_names - array - array of all files used in constructing this page. These
are the basenames of the files, not paths, because YAThemeParser only finds files on
the include path.
* class_names - array - array of all accumlated class names
* processing_results - Result of the latest parsing action
* variable_names - array - array of all accumulated variable names

Attributes which access stacks in the stack system:

* array_ref - array - current 'array_ref' stack
* attr - array - current 'attr' stack
* class_name - array - current 'class_name' stack
* cond_var - array - current 'cond_var' stack
* sq_bracket - array - current 'sq_bracket' stack
* tmp - array - current 'tmp' stack
* variable_name - array - current 'variable_name' stack

* stacks - array - readonly - all of the stacks

h3. Class Methods

None.

h3. Instance Methods

The usual magic methods plus _dump()_. 

Support for the YATheme language constructs

* include_file($file_name) - string - implements the _include_ command. Returns a complete
rendering of the named file.
* syntax_check() - boolean - a utility which passes the result of _$this->render()_ through
the PHP CLI processor for a syntax check. Returns TRUE or FALSE.
* include_file(file_name) - implements the YATheme _include_ command.
* add_class_name($name) - appends _$name_ to the class names list. Supports gathering of
class names by parser.
* add_variable_name($name) - appends _$name_ to the variable name list. Supports gathering
of variable names by the parser.
* add_css($path, $media), add_style($script, $media), add_javascript($path), add_script($script),
add_meta($name, $content) - support adding the associated data type to a page.
* render_css(), render_javascript(), render_meta() - all return strings which contain
HTML appropriate for the type of elements indicated.
* private add_helper(array-name, data) - used by the various add_...() methods to
uniquely add _data_ to the array _array-name_.

Page file dependencies

* add_file_name($arg) - adds one or more file names to _all_file_names_. _$arg_ can be
either a single file name (string) or an array of file names.

Rendering support

* render() - string - returns the rendering of the currently existing parse tree. If no tree
exists - nothing has been parsed or the file doesn't exist - then returns an error message.
* esc_dollar_func($str) - string - replaces leading '$' symbols of variable names
with '\$' so they will be printed rather than evaluated.
* render_attribute($attribute_str) - string - wraps _$attribute_str_ string around the
appropriate guards to test for variable existence.
* paranoid_guards($attribute_str) - string - wraps _$attribute_str_ in some
really paranoid checks.
* render_as_php($str) - string - wraps _$str_ in PHP process escape tags
* render_error($error_msg, $quote = TRUE) - string - implments the YATheme _guards_
action by wrapping, emailing, or ignoring the supplied error message.
If _errors_ is 'display', then _$error_msg_ is wrapped in a _div_ element
with class 'yatheme-error' AND then _$quote_ comes into play.
If TRUE  then the return is a single quoted string
suitable for including in a PHP segment; othewise it is HTML text.

Document Parsing.

* parse_str($str, $file_name = '-') - boolean - parses the supplied string and returns
TRUE on success, FALSE on failure. _basename($file_name)_ is passed to the scanner and
is used in error messages.
* parse_file($file_name) - boolean - reads the content of _$file_name_, if it can be
found on the include path. Then we pass its content to the parser. Returns TRUE if successful.
Returns FALSE if the parse fails or _$file_name_ cannot be read.

h4. The Stack System

The stack system is a stackable collection of stack/queue structures which support
the normal stack and queue operations plus a few convenience functions.

The collection is stackable because the entire current collection of stacks may
be pushed on the 'stacks_stack' (which is independent of the rest of the system)
to create a clean context by calling _push_context().
The immediate previous context can be (destructively) restored by calling _pop_context()_.
There is no fixed limit on the depth of this stack.

All operations are implemented by method calls of the form: _<operation>_<stack name>(args)_.
Where

* operation is one of: pushstack, popstack, push, pop, enqueue, dequeue, top,
addprefix, clear, mergeresult, flatten, or display.
* stack name is one of: array_ref, attr, class_name, cond_var, 
sq_bracket, tmp, or variable_name.
* args depend on the operation, but always have an optional last argument for
stack operation tracing. This optional argument is a string which will be
printed if the _verbose_ attribute of the YATheme instance is TRUE. (or, in plain
english: if the YATheme object has *vebose* set).

h5. Stacks

* array_ref - used to hold array element reference fragments - that is
the stuff which looks like '[12]' and '[$idx]'
* attr - used to hold object attribute references - stuff which looks
like '->a', '->$b->x[12]', etc
* class_name - used to hold class names for static class variables. Contains
both symbols and variables - for example, 'Foo', '$class_name', etc
* sq_bracket - used for building a list of array references which must
exist. This holds the 'square bracket' part.
* tmp - a temporary stack
* variable_name - temporary storage for variable names. See the grammar
for details.

h5. Stack Operations

All stack operations are implemented using _private_ functions. The public
methods are created dynamically by conjoining the operation with the stack name.
- as in _push_tmp('something')_. This function does not actually exist, but the call
is intercepted by the __call() magic method, argument list modified and then
routed to the correct function.

The implementation functions are:

* pushstack($stack, $msg = '') - pushes the entire stack and creates a new,
empty stack.
* popstack($stack, $msg = '') - pops and discards the contents of _$stack_,
recovering the next stack below it (if any).
* push($stack, $value, $msg = '') - pushes _$value_ onto the top of _$stack_
* pop($stack, $msg = '') - removes and returns the top of _$stack_ or returns
FALSE if _$stack_ is empty.
* top($stack, $msg = '') - returns the value on the top of _$stack_ or FALSE if
_$stack_ is empty. Does not modify _$stack_
* enqueue($stack, $value, $msg = '') - enqueues _$value_ onto the tail of _$stack_
* dequeue($stack, $msg = '') - removes and returns the tail of _$stack_ or returns
FALSE if _$stack_ is empty
* addprefix($stack, $prefix, $msg = '') - prepends _$prefix_ to every value
of _$stack_
* clear($stack, $msg = '') - empties _$stack_
* flatten($stack, $msg = '') - merges all stacks of _$stack_
* display($stack, $level, $msg = '') - prints the specified level of _$stack_.
Level 0 is the top-most (current) level.
* displaystack($stack, $msg = '') - prints all levels of the given stack

#end-doc
*/

require_once('ascanner.php');
require_once('aparser.php');

// this is redundant because it also is in 'includes.php', but I'm keeping it 'just in case'
// stream_resolve_include_path() is in PHP >= 5.3.2. 
if (!function_exists('stream_resolve_include_path')) {
  function stream_resolve_include_path($filename) {
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir_path) {
      if (file_exists(($tmp = $dir_path . DIRECTORY_SEPARATOR . $filename))) {
        return $tmp;
      }
    }
    return FALSE;
  }
}

// some miminal Lispish structures

// a YAScannerBase extention which wraps PHP's tokenizer
class YAPHPVarScanner extends YAScannerBase {
  private static $char_token_names = array(
    '"' => 'T_DOUBLE_QUOTE',
    '$' => 'T_DOLLAR_SIGN',
    '(' => 'T_LPAREN',
    ')' => 'T_RPAREN',
    '{' => 'T_LBRACE',
    '}' => 'T_RBRACE',
    '[' => 'T_LBRACKET',
    ']' => 'T_RBRACKET',
    ',' => 'T_COMMA',
    "." => 'T_OP',
    '+' => 'T_OP',
    '-' => 'T_OP',
    '*' => 'T_OP',
    '/' => 'T_OP',
    '>' => 'T_OP',
    '<' => 'T_OP',
    '>=' => 'T_OP',
    '<=' => 'T_OP',
    '==' => 'T_OP',
    '===' => 'T_OP',
    '!=' => 'T_OP',
    '!==' => 'T_OP',
    // '' => 'T_OP',
  );
  
  public function __construct($initial_state, $states) {
    // this scanner doesn't use the state machine
  } // end of __construct()
  
  public function process($str, $file_name = '-') {
    $this->token_pointer = 0;
    $this->chunk_buffer = array();
    $this->line_no = 1;
    $chunk_line_no = 1;
    $this->line_count = 0;
    $token_list = token_get_all("<\x3fphp " . $str . "\x3f>");
    // strip off PHP escape tags
    array_shift($token_list);
    array_pop($token_list);
    foreach ($token_list as $token) {
      if (is_array($token)) {
        $this->tag = token_name($token[0]);
        $this->chunk_buffer[] = array($this->tag, $token[1], $chunk_line_no);
        if ($token[0] == T_INLINE_HTML) {
          $this->line_count = substr_count($token[1], "\n");
          $this->line_no += $this->line_count;
        } else {
          $this->line_count = 0;  // admittedly, this is a guess
        }
      } else {
        $this->tag = YAPHPVarScanner::$char_token_names[$token];
        $this->chunk_buffer[] = array($this->tag, $token, $chunk_line_no);
      }
      $chunk_line_no = $this->line_no;
    }
    $this->chunk_buffer_len = count($this->chunk_buffer);
  } // end of process()
  
  public function dump($msg = '') {
    $str = $msg ? "$msg\n" : '';
    $str .= "YAPHPVarScanner: contains $this->chunk_buffer_len tokens\n";
    foreach ($this->chunk_buffer as $chunk) {
      $str .= " {$chunk[0]}[{$chunk[2]}]: '{$chunk[1]}'\n";
    }
    return $str;
  } // end of dump()
}

class PHPPrefixException extends Exception {}

class PHPPrefix {
  private $this_setup = FALSE;
  private $this_prefix = FALSE;
  private $include_file_list = array();
  private $content_prefix = FALSE;
  public function __construct() {
  } // end of __construct()

  public function __toString() {
    return $this->render();
  } // end of __toString()
  
  public function __get($name) {
    throw new PHPPrefixException("PHPPrefix::__get($name): Illegal attribute $name");
  } // end of __get()
  
  public function __set($name, $value) {
    throw new PHPPrefixException("PHPPrefix::__set($name): Illegal attribute $name");
  } // end of __set()
  
  public function __isset($name) {
    throw new PHPPrefixException("PHPPrefix::__isset($name): Illegal attribute $name");
  } // end of __isset()
  
  public function __unset($name) {
    throw new PHPPrefixException("PHPPrefix::__unset($name): Illegal attribute $name");
  } // end of __unset()

  public function set_this_setup($php) {
    $this->this_setup = $php;
  } // end of set_this_prefix()

  public function set_this_prefix($prefix) {
    $this->this_prefix = $prefix;
  } // end of set_this_prefix()
  
  public function add_include_prefix(PHPPrefix $prefix) {
    $this->include_file_list[] = $prefix;
  } // end of append_prefix_list()
  
  public function add_content_provided_php(PHPPrefix $prefix) {
    $this->content_prefix = $prefix;
  } // end of add_content_provided_php()
  
  public function render() {
    $str = $this->this_setup ? "$this->this_setup\n" : '';
    foreach ($this->include_file_list as $prefix) {
      $str .= $prefix->render();
    }
    if ($this->this_prefix) {
      $str .= "$this->this_prefix\n";
    }
    if ($this->content_prefix) {
      $str .= $this->content_prefix->render();
    }
    return $str;
  } // end of render()

  public function render_as_php() {
    return "<\x3fphp\n" . $this->render() . "\x3f>\n";
  } // end of render_as_php()
  
  public function dump($msg = '') {
    $str = $msg ? "$msg\n" : '';
    $str .= "This Prefix:\n$this->this_prefix\n";
    $str .= "\nIncluded Prefixes:\n";
    foreach ($this->include_file_list as $tmp) {
      $str .= $tmp->dump();
    }
    $str .= "\nPrefixes from Content\n";
    if ($this->content_prefix) {
      $str .= $this->content_prefix->dump();
    }
    return $str;
  } // end of dump()
}

class YAThemeParserException extends Exception {}

class YAThemeParser {
  // these are the real states we use to parse combined HTML, PHP, and YATHEME
  public static $yatheme_states = array(
    array('init', 'emit_error.Unable to proceed: no HTML, PHP, or YATheme detected',
      array("/^<\\\x3fphp\\s/", 'php', 'push_tag.PHP,add_matched'),
      array('/^{:\s*yatheme\s+off\s*:}/', 'yat_yatheme_off', 'discard_matched'),
      array('/^{:\s*yatheme\s+on\s*:}/', 'init', 'discard_matched'),
      array('/^{:/', 'yat_init', 'add_matched,push_tag.Y_OPEN_YBRACE,emit,pop_tag'),
      array("/^(?sU)(.*)(?={:|<\\\x3fphp\\s)/", 'init', 'push_tag.HTML,add_matched.1,emit,pop_tag'),
      array('/^(?s).*$/', 'init', 'push_tag.HTML,add_matched,emit,pop_tag'),
    ),
    array('php', 'add_matched',
      array('/^{:/', 'php', 'emit_error.Illegal YATheme escape in PHP escape, add_matched'),
      array("/^(?sU).*\\\x3f>/", 'init', 'add_matched,emit,pop_tag'),
      array('/^(?sU).*$/', 'init', 'add_matched,emit,pop_tag'),
    ),
    array('yat_init', 'emit_error.no yat starting token found or bad YATheme command',
      array('/^\s*(guards)\s+/', 'yat_guards', 'push_tag.Y_GUARDS,add_matched.1,emit,pop_tag'),
      array('/^\s*(yatemplate)\s+/', 'yat_template', 'push_tag.Y_YATEMPLATE,add_matched.1,emit,pop_tag'),
      array('/^\s*(yatemplate-content)\b\s*:}/', 'init', 'push_tag.Y_YATEMPLATE_CONTENT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*(authority)\s*/', 'yat_authority', 'push_tag.Y_AUTHORITY,add_matched.1,emit,pop_tag'),
      array('/^\s*(errors)\s+/', 'yat_errors', 'push_tag.Y_ERRORS,add_matched.1,emit,pop_tag'),
      array('/^\s*(php-setup)\s*:}/', 'yat_php_prefix', 'push_tag.Y_PHP_SETUP,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag, push_tag.Y_TEXT'),
      array('/^\s*(php-prefix)\s*:}/', 'yat_php_prefix', 'push_tag.Y_PHP_PREFIX,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag, push_tag.Y_TEXT'),
      array('/^\s*(test)\s+((?Us).*)\s*:}/', 'init', 'push_tag.Y_TEST,add_matched.1,emit,pop_tag, push_tag.Y_TEXT,add_matched.2,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^meta\s+([-\w]+)\s+((?U).*):}/', 'init', 'push_tag.Y_META,add_matched.1,emit,pop_tag, push_tag.Y_TEXT,add_matched.2,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('|^css\s+([-+/\.\w]+)\s+((?Us)[^:]*)\s*:}|', 'init', 'push_tag.Y_CSS,add_matched.1,emit,pop_tag, push_tag.Y_TEXT,add_matched.2,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('|^css\s+([-+/\.\w]+)\s*:}|', 'init', 'push_tag.Y_CSS,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*style\s*((?U)[^:]+)\s*:}/', 'yat_style', 'push_tag.Y_STYLE,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag, push_tag.Y_TEXT'),
      array('/^\s*style\s*:}/', 'yat_style', 'push_tag.Y_STYLE,add_literal.all,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag, push_tag.Y_TEXT'),
      array('/^\s*(script)\s*:}/', 'yat_script', 'push_tag.Y_SCRIPT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag, push_tag.Y_TEXT'),
      array('|^javascript\s+\s*([-+ /\.\w]+)\s*:}|', 'init', 'push_tag.Y_JAVASCRIPT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*(include)\s+/', 'yat_include', 'push_tag.Y_INCLUDE,add_matched.1,emit,pop_tag'),
      array('/^\s*render\s+(meta|css|javascript)\s*:}/', 'init', 'push_tag.Y_RENDER,add_matched.1,emit.pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*((?U)\$[^:]+|[A-Z]\w*::\$[^:]+)\s*\|\s*(((?Us).*)\s*)(?=:})\s*:}/', 'init', 'push_tag.Y_ATTRIBUTE,add_matched.1,emit,pop_tag, push_tag.Y_TEXT,add_matched.3,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*((?U)\$[^:]+|[A-Z]\w*::\$[^:]+)\s*:}/', 'init', 'push_tag.Y_ATTRIBUTE,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*((?U).*)\s*:}/', 'init', 'add_matched.1,emit_error.Syntax Error,empty_buffer, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')
      // array('/^{:\s*()\s*/', '', ''),
    ),

    array('yat_authority', 'emit_error.illegal or improper YATheme authority command',
      array('/^\s*(([A-Z]|ANY)(\s*,\s*([A-Z]|ANY))*)\s*:}/', 'init', 'push_tag.Y_TEXT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),

    array('yat_errors', 'emit_error.Illegal YATheme errors command or missing terminator',
      array('/^(display|ignore)\s*:}/', 'init', 'push_tag.Y_TEXT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^email\s+([-.\w]+@[-.\w]+)\s*:}/', 'init', 'push_tag.Y_EMAIL,add_literal.email,emit,pop_tag, push_tag.Y_TEXT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),

    array('yat_guards', 'emit_error.illegal guard command or missing YATheme terminator',
      array('/^\s*(on|off)\s*:}/', 'init', 'push_tag.Y_TEXT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),
      // array('/^\s*(paranoid|normal|off)\s*:}/', 'init', 'push_tag.Y_TEXT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),

    array('yat_include', 'emit_error.illegal YATheme include command or missing terminator',
      array('|^((?U)[-+ /.\w]+)\s*:}|', 'init', 'push_tag.Y_INCLUDE_FILE,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),

    array('yat_php_prefix', 'emit_error.illegal YATheme php-prefix/setup container or missing termination',
      array('/^\s*{:\s*(end-php-setup)\s*:}/', 'init', 'emit,pop_tag, push_tag.Y_OPEN_YBRACE,add_literal.{:,emit,pop_tag, push_tag.Y_END_PHP_SETUP,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^\s*{:\s*(end-php-prefix)\s*:}/', 'init', 'emit,pop_tag, push_tag.Y_OPEN_YBRACE,add_literal.{:,emit,pop_tag, push_tag.Y_END_PHP_PREFIX,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array("/^<\\\x3fphp /", 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal PHP escape encountered in php-prefix'),
      array('/^\s*{:/', 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal YATheme escape encountered in php-prefix'),
      array('/^((?Us).*)(?={:|<\\\x3fphp\\s)/', 'yat_php_prefix', 'add_matched.1'),
      // array('^/\s*/', '', ''),
      ),

    array('yat_script', 'emit_error.illegal YATheme script container or missing termination',
      array('/^\s*{:\s*(end-script)\s*:}/', 'init', 'emit,pop_tag, push_tag.Y_OPEN_YBRACE,add_literal.{:,emit,pop_tag, push_tag.Y_END_SCRIPT,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array("/^<\\\x3fphp /", 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal PHP escape encountered in script'),
      array('/^\s*{:/', 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal YATheme escape encountered in script'),
      array('/^((?Us).*)(?={:|<\\\x3fphp\\s)/', 'yat_script', 'add_matched.1'),
      ),

    array('yat_style', 'emit_error.illegal YATheme style container or missing termination',
      array('/^\s*{:\s*(end-style)\s*:}/', 'init', 'emit,pop_tag, push_tag.Y_OPEN_YBRACE,add_literal.{:,emit,pop_tag, push_tag.Y_END_STYLE,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array("/^<\\\x3fphp /", 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal PHP escape encountered in style'),
      array('/^\s*{:/', 'init', 'empty_buffer,pop_tag,emit_error.Syntax Error: illegal YATheme escape encountered in style'),
      array('/^((?Us).*)(?={:|<\\\x3fphp\\s)/', 'yat_style', 'add_matched.1'),
      ),

    array('yat_template', 'emit_error.illegal yatemplate command or missing terminator',
      array('|^\s*([-+ /\.\w]+)\b\s*:}|', 'init', 'push_tag.Y_YATEMPLATE_FILE,add_matched.1,emit,pop_tag, push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag')),

    array('yat_yatheme_off', 'emit_error.illegal yatheme command or missing terminator',
      array('/^{:\s*yatheme\s+on\s*:}/', 'init', 'push_tag.Y_TEXT,emit,pop_tag, discard_matched'),
        // 'push_tag.Y_TEXT,emit,pop_tag ,push_tag.Y_OPEN_YBRACE,add_literal.{:, emit,pop_tag ,push_tag.Y_YATHEME,add_literal.yatheme,emit,pop_tag ,push_tag.Y_TEXT,add_literal.on,emit,pop_tag ,push_tag.Y_CLOSE_YBRACE,add_literal.:},emit,pop_tag'),
      array('/^(?s)./', 'yat_yatheme_off', 'add_matched'),),
    );
    
  // grammars
public static $yatheme_grammar = "
  %start statements

  statements : statement statements %str{ @1 @2 }
              |
              ;
  statement : HTML %str{ @1 }
            | Y_TEXT %str{ @1 }
            | PHP %str{ @1 }
            | yat_simple  %str{ @1 }
            | yat_php_prefix
            | yat_php_setup
            | yat_script
            | yat_style
            | yat_yatheme %str{ @1 }
            | error %str{ @1 }
            ;
  yat_simple : Y_OPEN_YBRACE yat_simple_command Y_CLOSE_YBRACE %str{ @2 }
            ;
  yat_simple_command : Y_COMMENT Y_TEXT
            | Y_YATHEME Y_TEXT (A) %php{ \$__context->yatheme = @2; }  
            | Y_TEST Y_TEXT (V) %php{ @@ = \$__context->test_variable(V); }
            | Y_GUARDS Y_TEXT (A) %php{ \$__context->guards = @2; }
            | Y_YATEMPLATE Y_YATEMPLATE_FILE (A)
                %php{
                  \$__context->add_file_name(@2);
                  \$__context->template_file = @2;
                }
            | Y_YATEMPLATE_CONTENT
                %php{ @@ = \$__context->yatemplate_content ? \$__context->yatemplate_content : '{: yatemplate-content :}'; }
            | Y_AUTHORITY Y_TEXT
                %php{ \$__context->required_authority = trim(@2); }
            | Y_ERRORS Y_TEXT
                %php{ \$__context->errors = @2; }
            | Y_ERRORS Y_EMAIL Y_TEXT
                %php{ \$__context->errors = @2; \$__context->errors_email = @3; }
            | Y_INCLUDE Y_INCLUDE_FILE (A)
                %php{
                  \$__context->add_file_name(@2);
                  @@ = \$__context->include_file(@2);
                }
            | Y_ATTRIBUTE (A) Y_TEXT (D) %php{ @@ = \$__context->render_attribute(A, D); }
            | Y_ATTRIBUTE %php{ @@ = \$__context->render_attribute(@1); }
            | Y_META (M) Y_TEXT (T) %php{ \$__context->add_misc('meta', array(M, T)); }
            | Y_JAVASCRIPT %php{ \$__context->add_misc('javascript', array('link', @1)); }
            | Y_CSS (C) Y_TEXT (T) %php{ \$__context->add_misc('css', array('link', C, T)); }
            | Y_CSS (C) %php{ \$__context->add_misc('css', array('link', C)); }
            | Y_RENDER
              %php{
                switch (@1) {
                  case 'meta': @@ = '{:-meta-:}'; break;
                  case 'css': @@ = '{:-css-:}'; break;
                  case 'javascript': @@ = '{:-javascript-:}'; break;
                  default: throw new Exception(\"Unable to render '@1' - Illegal value\");
                }
              }
            ;
  yat_php_prefix : Y_OPEN_YBRACE Y_PHP_PREFIX Y_CLOSE_YBRACE Y_TEXT (T) Y_OPEN_YBRACE Y_END_PHP_PREFIX Y_CLOSE_YBRACE
              %php{ \$__context->php_prefix->set_this_prefix(T); }
            ;
  yat_php_setup : Y_OPEN_YBRACE Y_PHP_SETUP Y_CLOSE_YBRACE Y_TEXT (T) Y_OPEN_YBRACE Y_END_PHP_SETUP Y_CLOSE_YBRACE
              %php{ \$__context->php_prefix->set_this_setup(T); }
            ;
  yat_script : Y_OPEN_YBRACE Y_SCRIPT Y_CLOSE_YBRACE Y_TEXT (T) Y_OPEN_YBRACE Y_END_SCRIPT Y_CLOSE_YBRACE
              %php{ \$__context->add_misc('javascript', array('script', T)); }
            ;
  yat_style : Y_OPEN_YBRACE Y_STYLE (M) Y_CLOSE_YBRACE Y_TEXT (T) Y_OPEN_YBRACE Y_END_STYLE Y_CLOSE_YBRACE
              %php{ \$__context->add_misc('css', M ? array('style', T, M) : array('style', T)); }
            ;
  yat_yatheme : Y_OPEN_YBRACE Y_YATHEME Y_YATHEME_OFF Y_CLOSE_YBRACE Y_TEXT (T)
                      Y_OPEN_YBRACE Y_YATHEME Y_YATHEME_ON Y_CLOSE_YBRACE 
                      %str{ T }
            ;
";

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
  public static $ya_phpvar_grammar =
"    %start variable

    variable : static_variable
                  %php{ @@ = @1; \$__context->add_variable_name(@1, 'variable 1'); }
              | non_static_variable
                  %php{ @@ = @1; \$__context->add_variable_name(@1, 'variable 2'); }
              ;

    static_variable : class_name (C)
                  %php{ \$__context->add_class_name(C); }
                T_DOUBLE_COLON (D)
                referenceable_variable (V)
                  %php{
                    \$__context->add_variable_name(C .D . \$__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                    while (\$tmp = \$__context->pop_array_ref('static_variable 1')) {
                      \$__context->add_variable_name(C . D . \$tmp, 'static_variable 1');
                    }
                  }
                object_attribute_list (O)
                  %php{
                    @@ = C . D . V . O;
                    \$__context->addprefix_attr(V, 'static_variable 1');
                    while (\$tmp = \$__context->pop_attr('static_variable 1')) {
                      \$__context->add_variable_name(C . D. \$tmp, 'static_variable 1');
                    }
                  }
    /*              %php{ \$__context->display_stacks('static_variable 1' ); } /* */

              | non_static_variable (C)
                  %php{ \$__context->add_class_name(C); }
                T_DOUBLE_COLON (D)
                referenceable_variable (V)
                  %php{
                    \$__context->add_variable_name(C .D . \$__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                    while (\$tmp = \$__context->pop_array_ref('static_variable 1')) {
                      \$__context->add_variable_name(C . D. \$tmp, 'static_variable 1');
                    }
                  }
                object_attribute_list (O)
                  %php{
                    @@ = C . D . V . O;
                    \$__context->addprefix_attr(V, 'static_variable 1');
                    while (\$tmp = \$__context->pop_attr('static_variable 1')) {
                      \$__context->add_variable_name(C . D . \$tmp, 'static_variable 1');
                    }
                  }
              ;

    class_name : T_STATIC
                  %str{ @1 }
              | namespace_name
                  %str{ @1 }
              | T_NAMESPACE T_NS_SEPARATOR namespace_name
                  %str{ @1 @2 @3 }
              | T_NS_SEPARATOR namespace_name
                  %str{ @1 @2 }
              ;

    namespace_name : T_NS_SEPARATOR T_STRING namespace_name %str{ @1 @2 @3 }
              | T_STRING T_NS_SEPARATOR namespace_name %str{ @1 @2 @3 }
              | T_NS_SEPARATOR T_STRING %str{ @1 @2 }
              | T_STRING %str{ @1 }
              ;

    non_static_variable :  referenceable_variable (V)
                  %php{
                    \$__context->add_variable_name(\$__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                    while (\$tmp = \$__context->pop_array_ref('non_static_variable 1')) {
                      \$__context->add_variable_name(\$tmp, 'non_static_variable 1');
                    }
                  }
                object_attribute_list (O)
                  %php{
                    @@ = V . O;
                    \$__context->addprefix_attr(V, 'non_static_variable 1');
                    while (\$tmp = \$__context->pop_attr('non_static_variable 1')) {
                      \$__context->add_variable_name(\$tmp, 'non_static_variable 1');
                    }
                  }
    /*              %php{ \$__context->display_stacks('non_static_variable 1' ); } /* */
              ;
    referenceable_variable :
                variable_variable (V)
                  %php{ \$__context->pushstack_sq_bracket('referenceable_variable 1'); }
                sq_bracket_list (R)
                  %php{
                    @@ = V . R;
                    \$v = V;
                    // we need to save the raw variable name in case it is used in an attribute
                    \$__context->push_variable_name(V, 'referenceable_variable 1');
                    // we need this here in case this referenceable_variable is an object attribute
                    \$__context->push_array_ref(\$v, 'referenceable_variable 1');
                    // build rest of array references
                    while (\$tmp = \$__context->pop_sq_bracket('referenceable_variable 1')) {
                      \$v .= \$tmp;
                      \$__context->push_array_ref(\$v, 'referenceable_variable 1');
                    }
                    \$__context->popstack_sq_bracket('referenceable_variable 1');
                  }
              | variable_variable
                  %str{ @1}
              ;

    variable_variable : T_DOLLAR_SIGN variable_variable
                %php{
                  @@ = @1 . @2;
                  // this is a variable which must be defined in the local scope
                  \$__context->add_variable_name(@2, 'variable_variable 1');
                }
            | basic_variable
                %php{
                  @@ = @1;
                  // this is a variable which must be defined in the local scope
                  // \$__context->add_variable_name(@@, 'variable_variable 2');
                }
            ;

    basic_variable : T_VARIABLE  %str{ @1 }
                | T_DOLLAR_SIGN T_LBRACE (L)
                    %php{ \$__context->push_context('basic_variable 2a'); }
                  expr (E)
                    %php{ \$__context->pop_context('basic_variable 2b'); }
                  T_RBRACE (R) %str{ @1 L E R }
                ;

    sq_bracket_list : sq_bracket sq_bracket_list
                  %str{ @1 @2 }
              |  /* empty */
              ;
    sq_bracket : T_LBRACKET (L)
                  %php{ \$__context->push_context('sq_bracket 1'); }
                expr (E)
                  %php{
                    // \$__context->display_stacks('sq_bracket 1');
                    \$__context->mergeresult_variable_name('sq_bracket 1');
                    \$__context->pop_context('sq_bracket 1');
                  }
                T_RBRACKET (R)
                  %php{
                    @@ = L . E . R;
                    \$__context->enqueue_sq_bracket(@@, 'sq_bracket 1');
                  }
              ;

    object_attribute_list : T_OBJECT_OPERATOR referenceable_variable (V)
                  %php{
                    if (\$__context->verbose)  \$__context->displaystack_array_ref('object_attribute_list 1');
                    while (\$tmp = \$__context->pop_array_ref('object_attribute_list 1')) {
                      \$__context->add_variable_name(\$tmp, 'object_attribute_list 1');
                    }
                    // discard saved variable name - we don't need it, because it was on the array_ref stack
                    \$__context->pop_variable_name('object_attribute_list 1');
                    \$__context->pushstack_array_ref('object_attribute_list 1');
                  }
                object_attribute_list (O)
                  %php{
                    \$__context->popstack_array_ref('object_attribute_list 1');
                    @@ = @1 . V . O;
                    \$__context->addprefix_attr(@1 . V, 'object_attribute_list 1');
                    \$__context->push_attr(@1 . V, 'object_attribute_list 1');
                    if (\$__context->verbose) \$__context->displaystack_array_ref(\"object_attribute_list 1: bot: @@\");
                    if (\$__context->verbose)  \$__context->displaystack_attr(\"object_attribute_list 1: bot: @@\");
                  }
              | T_OBJECT_OPERATOR T_STRING (S)
                  %php{ \$__context->pushstack_sq_bracket('object_attribute_list 2'); }
                sq_bracket_list (L) 
                object_attribute_list (O)
                  %php{
                    @@ = @1 . S . L . O;
                    \$prefix = @1 . S . L;
                    \$__context->addprefix_attr(\$prefix, 'object_attribute_list 2');

                    // deal with array references generated above
                    \$s = @1 . S;
                    \$__context->push_attr(\$s, 'object_attribute_list 2');
                    while (\$tmp = \$__context->pop_sq_bracket('object_attribute_list 2')) {
                      \$s .= \$tmp;
                      \$__context->push_attr(\$s, 'object_attribute_list 2');
                    }
                    \$__context->popstack_sq_bracket('object_attribute_list 2');
                  }
              | T_OBJECT_OPERATOR T_LBRACE (L)
                  %php{ \$__context->push_context('object_attribute_list 3'); }
                expr (E)
                  %php{ \$__context->pop_context('object_attribute_list 3'); }
                T_RBRACE (R) object_attribute_list (O)
                  %php{
                    @@ = @1 . L .E . R . O;
                    \$__context->enqueue_attr(@@, 'object_attribute_list 3');
                  }
              |  /* empty */
              ;


    expr :       term opt_whitespace T_OP opt_whitespace expr %str{ @1 @2 @3 @4 @5 }
              | opt_whitespace term opt_whitespace %str{ @1 @2 @3 }
              ;

    opt_whitespace : T_WHITESPACE %str{ @1 }
              |
              ;
    func_call : T_STRING T_LPAREN func_arg_list T_RPAREN %str{ @1 @2 @3 @4 }
              | variable T_LPAREN func_arg_list T_RPAREN %str{ @1 @2 @3 @4 }
              ;
    func_arg_list  : expr T_COMMA func_arg_list %str{ @1 @2 @3 }
              | expr %str{ @1 }
              |
              ;

    term:       func_call %str{ @1 }
              | variable %str{ @1 }
              | T_STRING %str{ @1 }
              | T_CONSTANT_ENCAPSED_STRING %str{ @1 }
              | T_LNUMBER %str{ @1 }
              | T_DOUBLE_QUOTE encaps_list T_DOUBLE_QUOTE %str{ @1 @2 @3 }
              ;

    encaps_list: encaps_var  encaps_list %str{ @1 @2 }
                |  T_ENCAPSED_AND_WHITESPACE encaps_var %str{ @1 @2 }
                | T_ENCAPSED_AND_WHITESPACE  encaps_list %str{ @1 @2 }
                |  encaps_var %str{ @1 }
                ;

    encaps_var: T_VARIABLE %str{ @1 }
                    %php{ \$__context->add_variable_name(@1, 'encaps_var 1'); }
                |  T_VARIABLE T_LBRACKET encaps_var_offset T_RBRACKET %str{ @1 @2 @3 @4}
                    %php{ \$__context->add_variable_name(@1, 'encaps_var 2'); }
                |  T_VARIABLE T_OBJECT_OPERATOR T_STRING %str{ @1 @2 @3 }
                    %php{
                        \$__context->add_variable_name(@1, 'encaps_var 3a');
                        \$__context->add_variable_name(@@, 'encaps_var 3b');
                      }
                |  T_DOLLAR_OPEN_CURLY_BRACES expr T_RBRACE %str{ @1 @2 @3 }
                    %php{ \$__context->add_variable_name(@@, 'encaps_var 4'); }
                |  T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME T_LBRACKET expr T_RBRACKET T_RBRACE
                   %str{ @1 @2 @3 @4 @5 @6 }
                    %php{ \$__context->add_variable_name(@@, 'encaps_var 5'); }
                |  T_CURLY_OPEN variable T_RBRACE %str{ @1 @2 @3 }
                ;

    encaps_var_offset: T_STRING %str{ @1 }
                |  T_NUM_STRING %str{ @1 }
                |  T_VARIABLE %str{ @1 }
                    %php{ \$__context->add_variable_name(@1, 'encaps_var_offset 1'); }
                ;

";

  private $all_file_names = array();
  private $css = array();
  private $errors = 'display';
  private $errors_email = NULL;
  private $error_messages = array();
  private $file_name = '-';
  private $file_path = NULL;   // absolute path to file - set to NULL so isset() returns TRUE if non-null
  private $guards = 'normal';
  private $javascript = array();
  private $meta = array();
  private $parse_result = FALSE;
  private $phpvar_parser = NULL;
  private $phpvar_scanner = NULL;
  private $rendered_content = '';
  private $required_authority = NULL;
  private $scope = NULL;
  private $template_file = NULL;
  private $verbose = FALSE;
  private $yatemplate_content = NULL;
  private $yatheme = 'on';
  private $yatheme_parser = NULL;
  private $yatheme_scanner = NULL;

  // stacks are implemented as sub-arrays of $stacks.
  private static $stack_list = array(
      'array_ref',
      'attr',
      'class_name',
      'cond_var',
      'sq_bracket',
      'tmp',
      'variable_name',
    );
  private $stacks = array();
  private $stacks_stack = array();
  private $processing_results = array('variable_name' => array(), 'class_name' => array());
  private $php_prefix = FALSE;
  private static $yatheme_scanner_ser = NULL;
  private static $yatheme_parser_ser = NULL;
  private static $phpvar_scanner_ser = NULL;
  private static $phpvar_parser_ser = NULL;

  public function __construct($file_name = '', $verbose = FALSE) {
    if (!YAThemeParser::$yatheme_parser_ser) {
      $this->yatheme_scanner = new YAScanner('init', YAThemeParser::$yatheme_states);
      $this->yatheme_parser = new Parser(YAThemeParser::$yatheme_grammar, $this->yatheme_scanner);
      YAThemeParser::$yatheme_parser_ser = serialize($this->yatheme_parser);
      YAThemeParser::$yatheme_scanner_ser = serialize($this->yatheme_scanner);
    } else {
      $this->yatheme_parser = unserialize(YAThemeParser::$yatheme_parser_ser);
    }
    $this->yatheme_parser->context = $this;
    $this->verbose = $verbose;
    $this->yatheme_parser->verbose = $this->verbose;
    $this->php_prefix = new PHPPrefix();

    $this->clear_stacks();

    if (($file_name = basename($file_name))) {
      $this->file_name = $file_name;
      $this->file_path = stream_resolve_include_path($this->file_name);
      if ($this->file_path !== FALSE) {
        if ($str = file_get_contents($this->file_path, TRUE)) {
          // $this->yatheme_parser->verbose = TRUE;
          $this->parse_str($str, $this->file_name);
        } else {
          $this->error_messages[] = "Unable to Read $file_name";
          $this->parse_result = NULL;
        }
      } else {
        $ar = array();
        foreach (array(Globals::$packages_root, Globals::$system_packages) as $root) {
          foreach (scandir($root) as $dir) {
            $path = $root . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($path)) {
              $ar[] = $path;
            }
          }
        }
        $saved_include_path = get_include_path();
        set_include_path(implode(PATH_SEPARATOR, $ar));
        $this->file_path = stream_resolve_include_path($this->file_name);
        if ($this->file_path !== FALSE) {
          if ($str = file_get_contents($this->file_path, TRUE)) {
            // $this->yatheme_parser->verbose = TRUE;
            $this->parse_str($str, $this->file_name);
          } else {
            $this->error_messages[] = "Unable to Read $file_name";
            $this->parse_result = NULL;
          }
        } else {
          $this->error_messages[] = "Unable to resolve path to '$file_name'";
          $this->parse_result = NULL;
        }
        set_include_path($saved_include_path);
      }
    }
  } // end of __construct()
  
  public function __toString() {
    return "YAThemeParser($this->file_name)";
  } // end of __toString()
  
  public function __get($name) {
    switch ($name) {
      case 'required_authority':
      case 'css':
      case 'errors':
      case 'errors_email':
      case 'file_name':
      case 'file_path':
      case 'rendered_content':
      case 'guards':
      case 'javascript':
      case 'meta':
      case 'parse_result':
      case 'php_prefix':
      case 'processing_results':
      case 'scope':
      case 'stacks':
      case 'template_file':
      case 'verbose':
      case 'yatemplate_content':
      case 'yatheme':
      case 'yatheme_parser':
      case 'yatheme_scanner':
        return $this->$name;
      case 'phpvar_scanner':
      case 'phpvar_parser':
        $this->create_phpvar_parser();
        return $this->$name;
      case 'all_file_names':
        return array_unique($this->all_file_names);
      case 'array_ref':
      case 'attr':
      case 'class_name':
      case 'cond_var':
      case 'save':
      case 'tmp':
      case 'variable_name':
        return $this->stacks[$name][0];
      case 'variable_names':
      case 'class_names':
        $key = substr($name, 0, strlen($name) - 1);
        return array_unique($this->processing_results[$key]);
      default:
        throw new YAThemeParserException("YAThemeParser::__get(): attempt to access illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'required_authority':
      case 'errors':
      case 'errors_email':
      case 'file_name':
      case 'file_path':
      case 'guards':
      case 'scope':
      case 'template_file':
      case 'verbose':
      case 'yatemplate_content':
      case 'yatheme':
        return $this->$name = $value;
      case 'all_file_names':
      case 'css':
      case 'rendered_content':
      case 'javascript':
      case 'meta':
      case 'parse_result':
      case 'php_prefix':
      case 'phpvar_parser':
      case 'phpvar_scanner':
      case 'processing_results':
      case 'stacks':
      case 'yatheme_parser':
      case 'yatheme_scanner':
        throw new YAThemeParserException("YAThemeParser::__set(): attempt to set read-only attribute '$name'");
      default:
        throw new YAThemeParserException("YAThemeParser::__set(): attempt to set illegal attribute '$name'");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {
      case 'all_file_names':
      case 'required_authority':
      case 'css':
      case 'errors':
      case 'errors_email':
      case 'rendered_content':
      case 'file_name':
      case 'file_path':
      case 'guards':
      case 'javascript':
      case 'meta':
      case 'parse_result':
      case 'php_prefix':
      case 'processing_results':
      case 'scope':
      case 'template_file':
      case 'verbose':
      case 'yatemplate_content':
      case 'yatheme':
      case 'yatheme_parser':
      case 'yatheme_scanner':
        return isset($this->$name);
      case 'phpvar_parser':
      case 'phpvar_scanner':
        $this->create_phpvar_parser();
        return isset($this->$name);
      default:
        throw new YAThemeParserException("YAThemeParser::__isset(): attempt to access illegal attribute '$name'");
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'all_file_names':
      case 'required_authority':
      case 'css':
      case 'errors':
      case 'errors_email':
      case 'rendered_content':
      case 'file_name':
      case 'file_path':
      case 'guards':
      case 'javascript':
      case 'meta':
      case 'parse_result':
      case 'php_prefix':
      case 'phpvar_parser':
      case 'phpvar_scanner':
      case 'processing_results':
      case 'scope':
      case 'template_file':
      case 'verbose':
      case 'yatemplate_content':
      case 'yatheme':
      case 'yatheme_parser':
      case 'yatheme_scanner':
        throw new YAThemeParserException("YAThemeParser::__get(): illegal attempt to unset attribute '$name'");
      default:
        throw new YAThemeParserException("YAThemeParser::__get(): attempt to access illegal attribute '$name'");
    }
  } // end of __unset()
  
  // stack handling
  public function __call($func, $args) {
    if (!preg_match('/^([a-z]+)_(.*)/', $func, $match_obj)) {
      throw new YAThemeParserException("YAThemeParser::$func(): no such function");
    }
    list($func, $real_func, $stack) = $match_obj;
    switch ($real_func) {
      case 'clear':
      case 'dequeue':
      case 'display':
      case 'displaystack':
      case 'enqueue':
      case 'flatten':
      case 'mergeresult':
      case 'addprefix':
      case 'pop':
      case 'pushstack':
      case 'push':
      case 'popstack':
      case 'top':
        array_unshift($args, $stack);
        return call_user_func_array(array($this, $real_func), $args);
      default:
        throw new YAThemeParserException("YAThemeParser::$func(): no such function");
    }
  } // end of __call()
  
  public function add_class_name($name) {
    if (!in_array($name, $this->processing_results['class_name'])) {
      $this->processing_results['class_name'][] = $name;
    }
  } // end of add_class_name()
  
  public function add_variable_name($name, $msg = '') {
    if ($this->verbose) {
      echo ParserNode::$indent . "  add_variable_name(): $msg: $name\n";
    }
    if (!in_array($name, $this->processing_results['variable_name'])) {
      $this->processing_results['variable_name'][] = $name;
    }
  } // end of add_variable_name()

  public function push_context($msg = '') {
    if ($this->verbose) {
      echo ParserNode::$indent . " push context: $msg\n";
    }
    array_push($this->stacks_stack, $this->stacks);
    foreach (YAThemeParser::$stack_list as $stack_name) {
      $this->stacks[$stack_name] = array(array());
    }
  } // end of push()

  public function pop_context($msg = '') {
    if ($this->verbose) {
      echo ParserNode::$indent . " pop context: $msg\n";
    }
    if ($this->stacks_stack) {
      $this->stacks = array_pop($this->stacks_stack);
    } else {
      throw new YAThemeParserException("YAThemeParser::pop_context(): called with empty stacks_stack");
    }
  } // end of pop()

  private function pushstack() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  pushstack($stack): " . array_shift($args) ."\n";
      }
      if (isset($this->stacks[$stack])) {
        array_unshift($this->stacks[$stack], array());
      } else {
        $this->stacks[$stack] = array(array());
      }
    } else {
      throw new YAThemeParserException("YAThemeParser::pushstack(): illegal stack '$stack'");
    }
  } // end of pushstack()

  private function popstack($stack, $msg = '') {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  popstack($stack): " . array_shift($args) . "\n";
      }
      array_shift($this->stacks[$stack]);
    } else {
      throw new YAThemeParserException("YAThemeParser::popstack(): illegal stack '$stack'");
    }
  } // end of popstack()
  
  private function push() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      $value = array_shift($args);
      array_unshift($this->stacks[$stack][0], $value);
      if ($this->verbose) {
        echo ParserNode::$indent . "  push onto $stack: " . array_shift($args) . " $value\n";
      }
    } else {
      throw new YAThemeParserException("YAThemeParser::push_$stack(): Illegal stack");
    }
  } // end of push()

  private function pop() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      $top = array_key_exists(0, $this->stacks[$stack]) ? array_shift($this->stacks[$stack][0]) : FALSE;
      if ($this->verbose) {
        echo ParserNode::$indent . "  pop $stack: " . array_shift($args) . " $top\n";
      }
      return $top;
    } else {
      throw new YAThemeParserException("YAThemeParser::pop_$stack(): Illegal stack");
    }
  } // end of pop()

  private function enqueue() {
    $args = func_get_args();
    $stack = array_shift($args);
    $value = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  enqueue $value under $stack: " . array_shift($args) . "\n";
      }
      array_push($this->stacks[$stack][0], $value);
    } else {
      throw new YAThemeParserException("YAThemeParser::enqueue_$stack(): Illegal stack");
    }
  } // end of enqueue()

  private function dequeue() {
    $args = func_get_args();
    $stack = array_shift($args);
    $value = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  dequeue $stack: " . array_shift($args) . "\n";
      }
      return $this->stacks[$stack][0] ? array_pop($this->stacks[$stack][0]) : FALSE;
    } else {
      throw new YAThemeParserException("YAThemeParser::dequeue_$stack(): Illegal stack");
    }
  } // end of dequeue()

  private function top() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      $tmp = $this->stacks[$stack][0] ? $this->stacks[$stack][0][0] : FALSE;
      if ($this->verbose) {
        echo ParserNode::$indent . "  top of $stack: " . array_shift($args) . ($tmp?" ($tmp)":'(empty)') . "\n";
      }
      return $tmp;
    } else {
      throw new YAThemeParserException("YAThemeParser::top_$stack(): Illegal stack");
    }
  } // end of top()

  private function addprefix() {
    $args = func_get_args();
    $stack = array_shift($args);
    $prefix = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  addprefix $stack " . array_shift($args) . " $prefix\n";
      }
      $lim = count($this->stacks[$stack][0]);
      for ($idx=0;$idx<$lim;$idx += 1) {
        $this->stacks[$stack][0][$idx] = $prefix . $this->stacks[$stack][0][$idx];
      }
    } else {
      throw new YAThemeParserException("YAThemeParser::addprefix_$stack(): Illegal stack");
    }
  } // end of addprefix()

  private function clear() {
    $args = func_get_args();
    $stack = array_shift($args);
    if ($this->verbose) {
      echo ParserNode::$indent . "  clear stack $stack: " . array_shift($args) . "\n";
    }
    if (in_array($stack, YAThemeParser::$stack_list)) {
      $this->stacks[$stack][0] = array();
    } else {
      throw new YAThemeParserException("YAThemeParser::clear_$stack(): Illegal stack");
    }
  } // end of clear()

  private function mergeresult() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (!array_key_exists($stack, $this->processing_results)) {
      throw new YAThemeParserException("YAThemeParser::mergeresult_$stack(): stack $stack not a processing result");
    }
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if ($this->verbose) {
        echo ParserNode::$indent . "  merging $stack with processing_results: " . array_shift($args) . "\n";
      }
      // save results if this is a processing result stack
       $this->processing_results[$stack] = array_merge($this->processing_results[$stack],
              $this->stacks[$stack][0]);
    } else {
      throw new YAThemeParserException("YAThemeParser::mergeresult_$stack(): Illegal stack name");
    }
  } // end of mergeresult()

  private function flatten() {
    $args = func_get_args();
    $stack = array_shift($args);
    if ($this->verbose) {
      echo ParserNode::$indent . "  flatten $value onto $stack: " . array_shift($args) . "\n";
    }
    if (in_array($stack, YAThemeParser::$stack_list)) {
      $ar = $this->stacks[$stack][0];
      for ($i=0;$i<count($this->stacks);$i++) {
        $ar = array_merge($ar, $this->stacks[$i][$stack]);
      }
      $ar = array_unique($ar);
      for ($i=0;$i<count($this->stacks);$i++) {
        $this->stacks[$i][$stack] = $ar;
      }
    } else {
      throw new YAThemeParserException("YAThemeParser::flatten_$stack(): Illegal stack");
    }
  } // end of flatten()
  
  public function displaystack() {
    $args = func_get_args();
    $stack = array_shift($args);
    if ($this->verbose) {
      echo ParserNode::$indent . "Stack $stack " . array_shift($args) . "\n";
    }
    for ($idx=0;$idx<count($this->stacks[$stack]);$idx++) {
      $this->{"display_$stack"}($idx);
    }
  } // end of displaystack()

  private function display() {
    $args = func_get_args();
    $stack = array_shift($args);
    if (in_array($stack, YAThemeParser::$stack_list)) {
      if (!($level = array_shift($args))) {
        $level = 0;
      }
      if ($msg = array_shift($args)) {
        echo ParserNode::$indent . "  display_$stack(): $msg\n";
      }
      if ($level >= count($this->stacks[$stack])) {
        echo ParserNode::$indent . "  $stack: $level: level not found\n";
      } else {
        echo ParserNode::$indent . "  $stack: $level: "
          . ($this->stacks[$stack][$level] ? implode(', ', $this->stacks[$stack][$level]) : '(empty)')
          . "\n";
      }
    } else {
      throw new YAThemeParserException("YAThemeParser::enqueue_$stack(): Illegal stack");
    }
  } // end of display()

  // stack handling
  public function clear_stacks() {
    $this->stacks = array();
    foreach (YAThemeParser::$stack_list as $stack) {
      $this->stacks[$stack] = array(array());
    }
  } // end of clear_stacks()
  
  public function display_stacks($msg = '') {
    echo ParserNode::$indent . "Display_Stacks($msg)\n";
    foreach (YAThemeParser::$stack_list as $stack) {
      echo ParserNode::$indent . "Stack: $stack\n";
      for ($level=0;$level<count($this->stacks[$stack]);$level++) {
      echo ParserNode::$indent . " Lvl $level: ";
        call_user_func(array($this, "display_$stack"), $level);
      }
    }
    echo ParserNode::$indent . "Processing Results\n";
    foreach ($this->processing_results as $key => $val) {
      echo ParserNode::$indent . "  $key: " . implode(', ', array_unique($val)) . "\n";
    }
    echo "\n";
  } // end of display_stacks()

  // misc

  // YATheme Language support
  public function add_file_name($file_name) {
    if (is_array($file_name)) {
      foreach ($file_name as $fn) {
        $this->add_file_name($fn);
      }
    } else {
      if (!in_array($file_name, $this->all_file_names)) {
        $this->all_file_names[] = $file_name;
      }
    }
  } // end of add_file_name()

  // parsing support
  public function parse_str($str, $file_name = '-') {
    $this->file_name =
      $file_name = basename($file_name);
    if ($file_name != '-') {
      $this->add_file_name(basename($file_name));
    }
    $this->parse_result = $this->yatheme_parser->parse($str, $file_name);
    return $this->parse_result;
  } // end of parse()

  private function pre_render_tasks($other_theme_obj) {
    // add meta, css, and javascript arrays to end of other_theme_obj structures
    foreach ($this->meta as $tmp) {
      $other_theme_obj->add_misc('meta', $tmp);
    }
    foreach (array('css', 'javascript') as $ar_name) {
      foreach ($this->$ar_name as $tmp) {
        $tag = preg_match('/^pre_render-/', ($tag = array_shift($tmp))) ? $tag : 'pre_render-' . $tag;
        array_unshift($tmp, $tag);
        $other_theme_obj->add_misc($ar_name, $tmp);
      }
    }
    $other_theme_obj->php_prefix->add_content_provided_php($this->php_prefix);
  } // end of setup_render_environment()
  
  private function post_render_tasks($other_theme_obj) {
    // pull in css, javascript and meta tags
    foreach ($other_theme_obj->meta as $tmp) {
      $this->add_misc('meta', $tmp);
    }
    foreach (array('javascript', 'css') as $array) {
      foreach ($other_theme_obj->$array as $tmp) {
        $tag = preg_match('/^pre_render-(.*)$/', ($tag = array_shift($tmp)), $m) ? $m[1] : $tag;
        array_unshift($tmp, $tag);
        $this->add_misc($array, $tmp);
      }
    }
    // foreach ($other_theme_obj->meta as $meta_tag) {
    //   $this->add_meta($meta_tag);
    // }
    // foreach ($other_theme_obj->javascript as $tmp) {
    //   switch ($tmp[0]) {
    //     case 'link':
    //       $this->add_javascript($tmp[1]);
    //       break;
    //     case 'script':
    //       $this->add_script($tmp[1]);
    //       break;
    //     default:
    //       throw new YAThemeException("YATheme::post_render_tasks($other_theme_obj->file_name): called from $this->file_name: illegal script type: {$tmp[0]}");
    //   }
    // }
    // foreach ($other_theme_obj->css as $css) {
    //   $this->add_css($css[0], $css[1]);
    // }
    // Add all file dependencies to $this
    foreach ($other_theme_obj->all_file_names as $fn) {
      $this->all_file_names[] = $fn;
    }
  } // end of post_render_tasks()
  
  // rendering result
  public function render() {
    if (!$this->parse_result) {
      $str = "<pre>supplied file $this->file_name cannot be parsed:\n";
      $str = $this->error_messages ? implode("\n", $this->error_messages)
        : $this->yatheme_parser->render() . "</pre>";
      return $str;
    }

    if (!$this->rendered_content) {
      $this->rendered_content = $this->yatheme_parser->render();
    }

    // embed this content in a template if a template file is specified.
    // the rest of the logic for chosing a default template is in YATheme.php
    if ($this->template_file && $this->template_file != 'none' && $this->template_file != $this->file_name) {
      $template_parser = new YAThemeParser($this->template_file);
      if ($template_parser->parse_result) {
        // do all pre-render fixups
        $this->pre_render_tasks($template_parser);

        // let's see if it works
        $template_parser->yatemplate_content = $this->rendered_content;
        
        // render template
        $this->rendered_content = $template_parser->render();
        // some debugging cruft
        file_put_contents("/tmp/" . $this->file_name, $this->rendered_content);

        // do post render fixups
        $this->post_render_tasks($template_parser);
        return $this->rendered_content;
      } else {
        // FIXME!!!!!
        // this is a crude stopgap - but it is in the correct direction
        return $this->render_error("Unable to open template file '$this->template_file': " . $template_parser->render(), FALSE);
      }
    } else {
      return $this->php_prefix->render_as_php() .
       trim(preg_replace(array('/{:-meta-:}/', '/{:-css-:}/', '/{:-javascript-:}/'),
              array($this->render_meta(), $this->render_css(), $this->render_javascript()),
              $this->rendered_content));
    }
  } // end of render()

  public function include_file($file_name) {
    $themer = new YAThemeParser($file_name);
    $themer->verbose = TRUE;
    if ($themer->parse_result) {
      // $this->pre_render_tasks($themer);

      $themer->render();
      $this->post_render_tasks($themer);
      $this->php_prefix->add_include_prefix($themer->php_prefix);
      return $themer->rendered_content;
    } else {
      return "<p class=\"error\">Unable to Parse $file_name. {$themer->render()}</p>";
    }
  } // end of include_file()

  public function syntax_check($str = FALSE) {
    $p = proc_open('$this->php_path -l', array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    fwrite($pipes[0], $str !== FALSE ? $str : $this->render());
    fclose($pipes[0]);
    var_dump($pipes);
    $stderr = '';
    while ($tmp = fread($pipes[2], 8192)) {
      $stderr .= $tmp;
    }
    $result = proc_close($p);
    // $result is != 0 on syntax error
    if ($result) {
      $this->error_message = $stderr;
      return FALSE;
    } else {
      return TRUE;
    }
  } // end of syntax_check()

  // FIXME!!!! does not handle ${...} forms
  public function esc_dollar_func($str) {
    return preg_replace('/(?<!\\\\)\$(?=\w)/', '\\\\$', $str);
  } // end of esc_dollar_func()

  private function render_as_php($str) {
    return "<\x3fphp echo $str; \x3f>";
  } // end of render_as_php()

  public function render_error($error_msg, $quote = TRUE) {
    static $fmt = "'<div class=\"yatheme-error\"><p>%s</p></div>'";
    static $nq_fmt = "<div class=\"yatheme-error\">\n<p>%s\n</p>\n</div>\n";
    if ($quote) {
      $error_msg = preg_replace("/'/", '\\\'', $error_msg);
    }
    switch ($this->errors) {
      case 'display':
        return sprintf($quote ? $fmt : $nq_fmt, $error_msg);
      case 'email':
        return "mail('$this->errors_email', 'Error Message from ' . Globals::\$site_name, '$error_msg'";
      case 'ignore':
        return '';
      default:
        return sprintf($quote ? $fmt : $nq_fmt, "Illegal error mode: '$this->errors' when rendering error '$error_msg'");
    }
  } // end of render_error()
  
  public function render_attribute($attribute_str, $default = NULL) {
    switch ($this->guards) {
      case 'paranoid':
        return $this->paranoid_guards($attribute_str); // paranoid_guards ignores the default value
      case 'normal':
      case 'on':
        // if (!$this->phpvar_parser->parse($attribute_str)) {
        //   return "<span class=\"yatheme-error\">Attribute '$attribute_str' does not parse</span>";
        // }
        $error_msg = $default ? "'" . preg_replace('/([^\\\\])\'/', '\\1\\\'', $default) . "'" : $this->render_error("Error: variable '$attribute_str' is not set");
        return $this->render_as_php("(isset($attribute_str) ? $attribute_str : $error_msg)");
      case 'off':
        if ($default) {
          $default = "'" . preg_replace('/([^\\\\])\'/', '\\1\\\'', $default) . "'";
          return $this->render_as_php("(isset($attribute_str) ? $attribute_str : $default)");
        } else {
          return $this->render_as_php($attribute_str);
        }
      default:
        throw new YAThemeParserException("YAThemeParser::render_attribute($attribute_str):; Illegal guards mode: '" . $this->guards . "'");
    }
  } // end of render_attribute()

  private function create_phpvar_parser() {
    if (!YAThemeParser::$phpvar_parser_ser) {
      $this->phpvar_scanner = new YAPHPVarScanner('', '');
      YAThemeParser::$phpvar_scanner_ser = serialize($this->phpvar_scanner);
      $this->phpvar_parser = new Parser(YAThemeParser::$ya_phpvar_grammar, $this->phpvar_scanner);
      YAThemeParser::$phpvar_parser_ser = serialize($this->phpvar_parser);
    } else {
      $this->phpvar_parser = unserialize(YAThemeParser::$phpvar_parser_ser);
    }
    $this->phpvar_parser->context = $this;
    $this->phpvar_parser->verbose = $this->verbose;
  } // end of create_phpvar_parser()

  public static function paranoid_guards($attribute_str) {
    if (!$this->phpvar_parser) {
      $this->create_phpvar_parser();
    }
    $this->phpvar_parser->parse($attribute_str, '-');
    $if_token = 'if';
    $str = '';

    // guard against undefined classes
    foreach ($this->class_names as $cls_name) {
      $tmp = $this->esc_dollar_func($cls_name);
      $str .= "$if_token (!class_exists($cls_name)) { echo \"ERROR: class '$tmp' does not exist\"; }\n";
      $if_token = 'elseif';
    }

    // guards for undefined variables
    foreach ($this->variable_names as $var_name) {
      $tmp = $this->esc_dollar_func($var_name);
      $str .= "$if_token (!isset($var_name)) { echo \"ERROR: variable '$tmp' is not set\"; }\n";
      $if_token = 'elseif';
    }

    $str .= "else { echo $attribute_str; }\n";
    return $str;
  } // end of paranoid_guards()
  
  public function test_variable($attribute_str) {
    $this->push_context("entering test_variable($attribute_str)");
    if (!$this->phpvar_parser) {
      $this->create_phpvar_parser();
    }
    $if_token = 'if';
    $str = "<div class=\"yatheme-test\">\n<\x3fphp ";

    // parse attribute - fail if it doesn't work
    if (!$this->phpvar_parser->parse($attribute_str, $this->file_name)) {
      return "<div class=\"yatheme-test\"><p>Attribute: $attribute_str has a syntax error: {$this->phpvar_parser->render()}</p></div>";
    }

    $this->phpvar_parser->render();
    
// echo "Rendering of $attribute_str: " .  $this->phpvar_parser->render() . "\n";
// echo "var_dump(attribute_str): "; var_dump($attribute_str);
// echo 'classes: '; var_dump($this->class_names);
// echo 'variables: '; var_dump($this->variable_names);

    // guard against undefined classes
    foreach ($this->class_names as $cls_name) {
      $tmp = $this->esc_dollar_func($cls_name);
      $str .= "$if_token (!class_exists($cls_name)) { echo \"<p>ERROR: class '$tmp' does not exist</p>\"; }\n";
      $if_token = 'elseif';
    }

    // guards for undefined variables
    foreach ($this->variable_names as $var_name) {
      $tmp = $this->esc_dollar_func($var_name);
      $str .= "$if_token (!isset($var_name)) { echo \"<p>ERROR: variable '$tmp' is not set</p>\"; }\n";
      $if_token = 'elseif';
    }

    $str .= "else { \x3f><p>Attribute:$attribute_str exists</p>\n<\x3fphp }\n";
    $str .= "\x3f>\n</div>\n";
    $this->pop_context("leaving test_variable($attribute_str)");
    return $str;
    
  } // end of test_variable()
  
  // meta and link support
  public function add_misc($array,  $data) {
    if (($idx = array_search($data, $this->$array)) !== FALSE) {
      unset($this->{$array}[$idx]);
      // renumber css array
      $this->$array = array_merge($this->$array);
    }
    $this->{$array}[] = $data;
  } // end of css_helper()

  // public function add_meta($meta_tag, $content = '') {
  //   // this is a hack so we don't have to burst the array when adding to a template
  //   $this->meta[] = is_array($meta_tag) ? $meta_tag : array($meta_tag, $content);
  // } // end of add_meta()
  
  public function render_meta() {
    $str = '';
    foreach ($this->meta as $row) {
      list($name, $content) = $row;
      switch (($name = strtolower($name))) {
        case 'accept':
        case 'accept-charset':
        case 'accept-encoding':
        case 'accept-language':
        case 'accept-ranges':
        case 'age':
        case 'allow':
        case 'authorization':
        case 'cache-control':
        case 'connecting':
        case 'content-encoding':
        case 'content-language':
        case 'content-length':
        case 'content-location':
        case 'content-md5':
        case 'content-range':
        case 'content-type':
        case 'date':
        case 'etag':
        case 'expect':
        case 'expires':
        case 'from':
        case 'host':
        case 'if-match':
        case 'if-modified-since':
        case 'if-none-match':
        case 'if-range':
        case 'if-unmodified-since':
        case 'last-modified':
        case 'location':
        case 'max-forwards':
        case 'pragma':
        case 'proxy-authenticate':
        case 'proxy-authorization':
        case 'range':
        case 'referer':
        case 'retry-after':
        case 'server':
        case 'te':
        case 'trailer':
        case 'transfer-encoding':
        case 'upgrade':
        case 'user-agent':
        case 'vary':
        case 'via':
        case 'warning':
        case 'www-authenticate':
          $str .= "  <meta http-equiv=\"$name\" content=\"$content\">\n";
          break;
        default:
          $str .= "  <meta name=\"$name\" content=\"$content\">\n";
          break;
      }
    }
    return $str;
  } // end of render_meta()
  
  // public function add_javascript($path) {
  //   $this->add_helper('javascript', array('link', $path));
  // } // end of javascript()
  // 
  // public function add_script($script) {
  //   $this->add_helper('javascript', array('script', $script));
  // } // end of add_script()
  
  public function render_javascript() {
    $strs = array('', '', '', '');

    foreach ($this->javascript as $tmp) {
      list($type, $script) = $tmp;
      switch ($type) {
        case 'link':
         $strs[0] .= "<script type=\"text/javascript\" src=\"$script\" charset=\"utf-8\"></script>\n";
          break;
        case 'script':
          $strs[1] .= "<script type=\"text/javascript\" charset=\"utf-8\">\n$script\n</script>\n";
          break;
        case 'pre_render-link':
         $strs[2] .= "<script type=\"text/javascript\" src=\"$script\" charset=\"utf-8\"></script>\n";
          break;
        case 'pre_render-script':
          $strs[3] .= "<script type=\"text/javascript\" charset=\"utf-8\">\n$script\n</script>\n";
          break;
        default:
          throw new YAThemeParserException("YAThemeParser::render_javascript(): illegal type: $type");
      }
    }
    return implode("\n", $strs);
  } // end of render_javascript()

  // public function add_css($path, $media = '') {
  //   $this->add_helper('css', array('link', $path, $media ? $media : 'all'));
  // } // end of add_css()
  // 
  // public function add_style($css_text, $media) {
  //   $this->add_helper('css', array('style', $css_text, $media ? $media : 'all'));
  // } // end of add_style()
  
  public function render_css() {
    $strs = array('', '', '', '');
    foreach ($this->css as $row) {
      list($type, $css, $media) = $row;
      switch ($type) {
        case 'link':
          $strs[0] .= "<link rel=\"stylesheet\" href=\"$css\" type=\"text/css\" media=\"$media\" charset=\"utf-8\">\n";
          break;
        case 'style':
          $strs[1] .= "<style type=\"text/css\" media=\"$media\">\n$css\n</style>\n";
          break;
        case 'pre_render-link':
          $strs[2] .= "<link rel=\"stylesheet\" href=\"$css\" type=\"text/css\" media=\"$media\" charset=\"utf-8\">\n";
          break;
        case 'pre_render-style':
          $strs[3] .= "<style type=\"text/css\" media=\"$media\">\n$css\n</style>\n";
          break;
        default:
          throw new YAThemeParserException("YAThemeParser::render_css(): error in css stack: type: $typs");
      }
    }
    return implode("\n", $strs);
  } // end of render_css()

  public function dump($msg = '') {
    $str = $msg ? "$msg\n" : '';

    return $str;
  } // end of dump()
}

// echo YAThemeParser::$php_var_grammar;
