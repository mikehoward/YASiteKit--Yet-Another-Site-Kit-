<?php

/*
#begin-doc
h1. aparser.php - A Parser for little languages

This is top-down, recursive parser in PHP

Parser is a PHP 5 object which takes a *right, recursive language specification*
in more-or-less yacc/bison format and magically becomes a top-down, recursive
descent, predictive parser. If the grammer is written properly, it even works.

*Note:* yacc/bison and most other parser generators are use a _shift-reduce_
algorithmn and so they require *left recursive* grammars to keep the parser
from going into an infinite loop. Recursive descent - on the other hand -
needs the grammar to be *right recursive*. [Go read the Dragon book if you
want more detail - or even to correct any garbage I may be slinging]

This module defines several helper classes:

* "Parser":#parser - the actual Parser Class - follow this link for
the details of the object.
* "ParserNode":#parsernode - nodes which are created by the parser when
parsing a sentense in the target language
* "ParserLangNode":#parserlangnode - nodes used by the Parser and ParsesrLangDefParser
in parsing the target language definition. These are used by the Parser in
running the parse (phew)
* "ParserLangDefParser":#parserlangdefparser - the object which parses the
target language definition and prepares the table which drives the parser. It
produces an array of ParserLangNodes.

h2. Grammar Specification Rules

*Comments* may be inserted anywhere in a grammer by enclosing them in
C-style comment markers: '/&#42; ..... &#42;/'. Everything following, and including,
the first '/&#42;' is discarded up _through_ the first '&#42;/'. *Comments* do _not_
next.

*Terminals* start with a capital letter OR are single quote enclosed punctuation marks.

bq. T, TERMINAL, Terminal, ':', ','

*non-terminals* start with a lower case letters and contain lower case letters, digits
and underscore characters (_).

bq. foo, bar, foo_bar10

*Directives* start with a percent prefixed keyword. There are only a limited number.

* %start &lt;non-terminal&gt; - optional - defines the start non-terminal
* %action_prefix{ _php code_ %} - optional - defines code which prefixes
_every_ action. This is where you put global variables and other stuff.
NOTE: '%action_prefix{' is all one word - no interior blanks.
* %action_suffix{ _php code_ %} - optional - code which is appended
to _every_ action.

*non-terminal definitions:*
all non-terminals are uniquely defined by a set of production definitions.
non-terminal definitions start with the non-terminal, followed by a colon (:),
followed by one or more productions (separated by pipe symbols (|)), and terminated
by a semicolon (;)

bq. The syntax is: non-terminal ':' production | production | production | ... ;

*productions* are sequences of symbols, semantic_name declarations, and semantic actions.
For example 'A B ( C ) {some action}'.

A production may be empty. An empty production
should always be the last production in a non-terminal definition. It is symbolized
by only putting white space between the production separator (|) and the definition
terminator.

bq. foo: A foo | ;

*semantic_names* are identifiers enclosed in parenthesis. _identifiers_ satisfy
the regular expression [a-zA-Z]\w*.

bq. Examples: (A), (foo), (foo_bar_1)

There are two types of *semantic actions*:

* %string{ ... } - which contains a list of language symbol references which
are concatenated together and then assigned to the non-terminal being defined
* %php{ ... } - which contains PHP code which is executed. The code may contain
language symbol references which will be substituted _verbatum_ with the values
of the indicated symbols. This may lead to PHP syntax errors if strings are
not properly quoted and/or interior characters are not properly escaped.

The contents of a _%string_ form may only consist of symbol references and
white space. Each symbol reference must be surrounded by white space - unless
it is the first or last symbol reference where the white space between the
reference and the bracket is optional: as in '%string{ A }' or '%string{A}'.
[for the lazy, _%string{_ can be abbreviated _%str{_ ]

In the PHP form, verything inside the braces is opaque to the parser, so
you can actually put about anything in there you want. The only caviate
is that any embedded braces must match. That is '%php{ ... {...}...} is OK,
but %php{ { } and %php{ } } are not. However - if you need to embed an
unmatched (read unmatchable) brace, you can by escaping it with a backslash (/).
That is: '%php{ ... \} ... }' is ok and will be translated to '%php{ ... } ... }'.
Similarly, '%php{ ... \{ ... }' translates to '%php{ ... { ... }'.

PHP semantic actions are executed when the parser's _execute()_ method is run.
This contrasts with other parsers which ususally perform semantic actions
while parsing is in progress.

*special variable names* in semantic actions refer to symbols which occur in the same production,
augmented by the non-terminal being defined. In other words, in 'foo: A B {action1} | C D { action2 }',
the code _action2_ will may refer to the values of C and D, but not A or B. It may assign a
value to _foo_. Similarly, action1 may refer to values of A and B, but not C and D. Again,
it can assign a value to _foo_

*special variables names* can be EITHER _semantic_names_
or a speical form which mimics the convention used in yacc, bison, lemon, and many
other parser generators:

* @@ refers to the non-terminal being defined. This is a read-write variable.
* @1, @2, ... refer to the preceeding symbols and semantic actions in the rule,
as defined above. These are _read-only_ variables.

If a *semantic action* returns a value, it is assigned to the action and may be used
by other actions to the right and in the same production. Othewise it is assigned
the value NULL.

h3. PHP Semantic Actions Expanded


Semantics are grafted on to the parse tree using _semantic actions_.

h4. String Semantic Actions

String actions are enclosed in '%string{' '}' symbols. [alternately, '%str{ ... } ]
The contents consists
solely of white space separated language symbol references (see the next subsection).
The values of all references are concatenated together and assigned to the
non-terminal being defined. That is: 'foo : A B %string{ @1 @2 }' concatenates
the values of the terminals 'A' and 'B' and assigns the result to 'foo'.
Note that this _overwrites_ any value 'foo' had in this node. This is generally
not a problem, because when you want to append values, you usually recurse, as in

pre. foo : A B foo %string{ @1 @2 @3 }.

pre. foo : A B %string{ @1 @2 } %string{ @1 @2 @3 }

which doubles the concatenation of A and B and assigns it to foo [the
first assignment is overwritten]. [of course, this is contrived
because it would be easier to write the action as %str{ @1 @2 @1 @2 },
but you get the idea]

h4. PHP Actions

PHP actions are PHP enclosed in curly braces: '%php{' '}' and are executed
as PHP Functions in the context described below.

All production symbols - including the non-terminal being defined - are
available for use in the function. The non-terminal being defined can
be assigned a value by writing '@@ = some experssion'. "See":#php_action_symbols
for details

This allows more complex formatting than _string actions_ do as well
as communication and modification with the enclosing environment -
see Execution Context, below, for more detail.

Each PHP action may have a prefix and suffix which can set up a more
elaborate execution context. See "Execution Prefix and Suffix":#php_action_prefix_suffix

All values of production symbols are initialized to local function
variables - as described "below":#php_action_symbols. The value of each symbol depends
its nature:

* terminals - defined by the semantic_value returned by the scanner.
This value is immutable.
* non-terminals - initially defined as the empty string. May be modified by
semantic actions - as described below.
* string actions - the concatenation of the symbols named in the string action,
in the order of their occurance.
* php actions - the return value of the code upon execution by calling
the _render()_ method on the semantic action's ParseNode instance.
* error messages - the value returned by the error handler. This is immutable.

h5(#php_action_symbols). Accessing Symbol Values

Each symbol (and semantic action) in a non-terminal definition production
is assigned a symbolic name.

For example:

pre. a : foo { if (@1) { @@ = @1; } }

which tests to see if the value of _foo_ is not false in some sense and, if it isn't,
assigns the value of _foo_ (@1) to the _a_ (@@).

Each symbol in a production is automatically assigned a name. The non-terminal
being defined is named '@@'. The symbols in the production are named '@1', '@2', ...
in order of appearence in the production. _This includes semantic actions_.

For example, in:

pre. a : foo { if (@1) { @@ .= @1; return TRUE; } else { return FALSE; } } bar { @@ .= @3; }

the assignments are:

* @@ - a
* @1 - foo
* @2 - the value returned by '{ @@ .= @1; return TRUE; } else { return FALSE; ; }'.
[FALSE will automatically be returned if no explicit return is included]
* @3 - bar
* @4 - the value returned by '{ @@ .= @3; }' - which is FALSE in this case.

This can get difficult to maintain, so each _symbol_ may be given an explicit
name by following it with a word in parenthesis, as in _foo (F)_. Traditionally,
this word will be a single, upper case letter - but it can be any word
which satisfies _\w+_. [I don't know where the tradition came from or even if
it's really a tradition. I stole this from the Lemon parser documentation
because I like the idea.]

Thus,

pre. a : foo { @@ = @1; }

and 

pre. a (A) : foo (FOO) { A = FOO; }

are equivalent, execute identical code, and achieve the same result.

You can mix and match.

h5(#php_action_context). Execution Context

In contrast with other parser generators, the parser does not execute
semantic actions during the parse. First the parse tree is constructed
by the _parse()_ method. The semantic actions are _only_ executed
when the _render()_ method is invoked. _render()_ returns the
value assigned to the _start_ non-terminal.

Each _semantic action_ executes in the context of a temporary function.
Each symbol reference is transformed into a local variable and the
current value of each symbol is passed in the argument list to the
body of the function. The local variable names are generated
automatically:

* $__context - the context object passed in from outside. This is passed
by value and is usually an object reference, so that object methods and
attributes are available within the local function context.
* $__non_terminal - for the non-terminal being defined - symbol reference @@
* $__var1 - for the first symbol in the production - @1
* $__var2 - for the second symbol in the production - i.e. @2
* etc

The calling sequence passess _$__non_terminal_ as a reference and the others
as values.

Note: this means that you can write 'normal PHP' expressions using the symbol
references - such as

pre. '@@ = @1 . @2;' or '"it rains in {@1}\n".

h5(#php_action_prefix_suffix). Execution Prefix and Suffix

Each (and every) semantic action may be prefixed and suffixed by chunks of code
which are defined in the _language definition_ using the _%action_prefix_ and
_%action_suffix_ directives.

This is a limited feature because:

* only one prefix and one suffix may be defined
* the prefix and suffix code are prepended and appended to the action body
for each and every action prior to execution
* the prefix and suffix are defined in the _language specification_ and so
are a fixed part of the language. In other words, you can't use a different
prefix or suffix when parsing different string.
* if the body of the action executes a _return_, then the suffix code
is not executed. This can be a feature if, for example, you want to assign
a default value to every _semantic action_ and only return significant
ones.

What do you use this stuff for?

Well you can put global variables in the prefix and they will then be accessible
from every action.

You can create a default return value [see above].

You can probably think of lots of other things.

h2(#parser). Parser - the parser object

Each parser is an instance of a Parser object. It needs a language definition
(see above) and a scanner (see "ascanner.php":/doc.d/system-includes/ascanner.html).

Once the scanner and language grammar are constructed, the parser object is
very easy to use.

h3. Instantiation

pre. $foo = new Parser(language_def_string, YAScannerBase scanner, $context = NULL, verbose = FALSE, node_class_name = NULL);

* _language_def_string_ is string containing the language definition - as specified above.
* scanner - a scanner object which extends YAScannerBase.
(found in "ascanner.php":/doc.d/system-includes/ascanner.html).
* context - typically an object. This is used to pass an object reference to
PHP actions so that they can access methods and attributes defined in the object.
This provides a per-parser execution environment. Parser treats this an an opaque
parameter.
* verbose - boolean - the usual.
* node_class_name - the name of a class of nodes which implements the ParserNode
to populate the parse tree.

h3. Attributes

<!-- case 'indent': -->
Attributes you might be interested in. All attributes except _verbose_ are read-only.

* language - ParserLangDefParser instance which defines the language being parsed.
* root - this is the root node of the parse tree. That is where _render()_ starts
* str - the string the Parser instance can parse. Is set by calling the _parse()_ method
* str_valid - boolean - TRUE if the string parsed correctly, else FALSE
* verbose - boolean - turn it on or off.

Internal attributes. You should never need to even know about these.

* cur_node - node_class_name (defaults to ParserNode) - the current node which is being
examined during the parse. Used internally and not something to mess with.
* node_stack - stack of node_class_name instances being manipulated during the parse
* productions - this is a copy of language->language_ar: an associative array of
production arrays used by the parser and created by a ParserLangDefParser object.
Don't mess with them.
* scanner - The scanner object which was passed in.

h3. Class methods

None

h3. Instance Methods

Magic methods plus _dump(msg)_ and . . .

* parse(str, $file_name) - parses the string according to the language specification.
_$file_name_ is used for diagnostics - it is passed to the scanner.
Returns TRUE on a successful parse and FALSE on an error.
* render() - returns the rendering of the current parse tree.
* display_parser_state() - returns a string describing the parser state
* $parser->root->dump(msg) - returns a string containing a recursive dump of all the
ParserNodes in the current parse tree. [not strictly a method, but handy for debugging
language grammars]
#end-doc
*/

require_once('ascanner.php');

/*
#begin-doc
h2(#parsernode). ParserNode

These are nodes created by the Parser when parsing a sentense in the target
language. The final result of the parse is a tree of these nodes.

h3. Instantiation

pre. $foo = new ParserNode($type, $value, $semantic_value, $semantic_name);

see attributes, next, for the meaning of these parameters

h3. Attributes

Attributes defined at instantiation:

* type - type of node: production / literal / action / message
* syntactic_value - this is the term used in the grammar
* semantic_value - string which will be returned if _render()_ is called on this node
* semantic_name - symbolic name this execution value will be assigned to, so it can

Attributes defined later and internally

* execution_value - the value returned from the latest execution of the node
* my_index - int - the nodes index into the table of subnodes of it's parent
* next - ParserNode - left sybling in subnodes of parent or NULL
* parent - ParserNode - parent node or NULL
* previous - ParserNode - right sybling in subnodes of parent orN NULL
be used in semantic actions
* subnodes - array of subnodes living under this node. Only valid for productions


h3. Class Methods

None

h3. Instance Methods

* add_node(node) - appends _node_ to the _subnodes_ attribute;
* discard_modes() - discards all subnodes. Leaves it up to PHP to actually
destroy them.
* render() - renders this node and recursively renders all subnodes.
* execute() - does something brilliant and I don't know what it is yet.

#end-doc
*/

class ParserNodeException extends Exception {}

class ParserNode {
  const PRODUCTION = 'production';
  const LITERAL = 'literal';
  const STRING_ACTION = 'string_action';
  const PHP_ACTION = 'php_action';
  public $action_prefix = '';
  public $action_suffix = '';
  private $execution_value = NULL;
  private $my_index = FALSE;
  private $next = NULL;
  private $parent = NULL;
  private $previous = NULL;
  private $semantic_value;
  private $semantic_name;
  private $subnodes = array();
  private $type;
  private $syntactic_value;
  private $func = NULL;      // for PHP_ACTIONS - this is where we cache the function
  private $verbose = FALSE;
  public static $trace = FALSE;
  public static $indent = '';
  
  public function __construct($type, $syntactic_value, $semantic_value, $semantic_name) {
    $this->type = $type;
    $this->syntactic_value = $syntactic_value;
    $this->semantic_value = $semantic_value;
    $this->semantic_name = $semantic_name;
    switch ($type) {
      case ParserNode::PRODUCTION:
      case ParserNode::PHP_ACTION:
      case ParserNode::STRING_ACTION:
        $this->execution_value = '';
        break;
      case ParserNode::LITERAL:
        $this->execution_value = $semantic_value;
        break;
    }
  } // end of __construct()
  
  public function __toString() {
    return "($this->type / $this->syntactic_value"
      . ($this->semantic_name ? "($this->semantic_name)" : '')
      . ", '$this->semantic_value')";
  } // end of __toString()
  
  public function __get($name) {
    switch ($name){
      case 'execution_value':
      case 'my_index':
      case 'next':
      case 'parent':
      case 'previous':
      case 'semantic_name':
      case 'semantic_value':
      case 'subnodes':
      case 'syntactic_value':
      case 'type':
      case 'verbose':
        return $this->$name;
      case 'name':
        return $this->semantic_name ? $this->semantic_name : $this->type;
      default:
        throw new ParserNodeException(__CLASS__ . "::__get($name): illegal attribute name '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name){
      case 'semantic_value':
      case 'semantic_name':
      case 'subnodes':
      case 'syntactic_value':
      case 'type':
        throw new ParserNodeException(__CLASS__ . "::__set(): attempt to set read only attribute '$name'");
      case 'execution_value':
      case 'my_index':
      case 'next':
      case 'parent':
      case 'previous':
        $this->$name = $value;
        break;
      case 'verbose':
        $this->$name = $value ? TRUE : FALSE;
        break;
      default:
        throw new ParserNodeException(__CLASS__ . "::__set($name): illegal attribute name '$name'");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name){
      case 'execution_value':
      case 'my_index':
      case 'next':
      case 'parent':
      case 'previous':
      case 'semantic_value':
      case 'semantic_name':
      case 'subnodes':
      case 'type':
      case 'syntactic_value':
      case 'verbose':
        return isset($this->$name);
      default:
        throw new ParserNodeException(__CLASS__ . "::__issest($name): illegal attribute name '$name'");
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name){
      case 'execution_value':
      case 'my_index':
      case 'next':
      case 'parent':
      case 'previous':
      case 'semantic_name':
      case 'semantic_value':
      case 'subnodes':
      case 'syntactic_value':
      case 'type':
      case 'verbose':
        throw new ParserNodeException(__CLASS__ . "::__unset($name): attempt to unset attribute '$name'");
      default:
        throw new ParserNodeException(__CLASS__ . "::__unset($name): illegal attribute name '$name'");
    }
  } // end of __unset()

  public function add_node($node) {
    $node->parent = $this;
    $node->previous = ($tmp = count($this->subnodes)) ? $this->subnodes[$tmp - 1] : NULL;
    if ($node->previous) {
      $node->previous->next = $node;
    }
    $node->next = NULL;
    $node->my_index = count($this->subnodes);
    $this->subnodes[] = $node;
  } // end of add_node()

  // recursively discard nodes
  public function discard_nodes() {
    foreach ($this->subnodes as $node) {
      $node->discard_nodes();
      unset($node);
    }
    $this->subnodes = array();
  } // end of discard_nodes()
  
  // note: references used to avoid string duplication
  public function render( $context, &$action_prefix, &$action_suffix) {
    switch ($this->type) {
      case ParserNode::PRODUCTION:
        if (ParserNode::$trace) {
          echo ParserNode::$indent . "Beginning Render: $this->syntactic_value\n";
          ParserNode::$indent .= '. ';
        }
        foreach ($this->subnodes as $subnode) {
          switch ($subnode->type) {
            case ParserNode::PRODUCTION:
            case ParserNode::PHP_ACTION:
            case ParserNode::STRING_ACTION:
              $subnode->render($context, $action_prefix, $action_suffix);
              break;
            default:
              break;
          }
        }
        if (ParserNode::$trace) {
          ParserNode::$indent = substr(ParserNode::$indent, 0, strlen(ParserNode::$indent) - 2);
          echo ParserNode::$indent . "Leaving Render $this->syntactic_value: $this->execution_value\n";
        }
        return $this->execution_value;
      case ParserNode::LITERAL:
        return $this->execution_value;
      case ParserNode::STRING_ACTION:
        if (!$this->parent) {
          throw new ParserNodeException(__CLASS__ . "::render(): string semantic action w/o parent - internal error");
        }
        $map = array('@@' => $this->parent->execution_value);
        // the parser places a ParserNode created from the non-terminal being defined in this
        //  production in subnodes[0]. This lets us access the semantic_name assigned in
        //  the non-terminal definition. The Value, on the other hand, is stored in the
        //  $this->parent->execution_value, so it is available to subsequent actions within
        //  this same production.
        // That's why we get the name from one place and the value from someplace else
        if ($this->subnodes && $this->subnodes[0]->semantic_name) {
          $map[$this->subnodes[0]->semantic_name] = $this->parent->execution_value;
        }
        for ($idx=0;$idx<$this->my_index; $idx += 1) {
          $node_tmp = $this->parent->subnodes[$idx];
          $map['@' . ($idx+1)] = $node_tmp->execution_value;
          if ($node_tmp->semantic_name) {
            $map[$node_tmp->semantic_name] = $node_tmp->execution_value;
          }
        }
        $str = '';
        foreach (preg_split('/\s+/', trim($this->semantic_value)) as $token) {
          $str .= array_key_exists($token, $map) ? $map[$token] : " [ERROR: symbol '$token' does not select a language symbol ($this)] ";
        }
        $this->parent->execution_value = $str;
        return ($this->execution_value = $str);
      case ParserNode::PHP_ACTION:
        if (!$this->parent) {
          throw new ParserNodeException(__CLASS__ . "::render(): semantic action w/o parent - internal error");
        }
        // debugging code - enable if something bad happens
        // if ($this->verbose) echo $this->parent->dump("PHP action Dump");
        
        if (!$this->func) {
          // build dictionary
          // replace all semantic_name's by @ symbols
          $targets = array();
          $replacements = array();
          // the parser places a ParserNode created from the non-terminal being defined in this
          //  production in subnodes[0]. This lets us access the semantic_name assigned in
          //  the non-terminal definition. The Value, on the other hand, is stored in the
          //  $this->parent->execution_value, so it is available to subsequent actions within
          //  this same production.
          // That's why we get the name from one place and the value from someplace else
          if ($this->subnodes && $this->subnodes[0]->semantic_name) {
            $targets[] = '/\b' . $this->subnodes[0]->semantic_name . '\b/';
            $replacements[] = '@@';
          }
          for ($idx = 0;$idx < $this->my_index; $idx += 1) {
            $node_tmp = $this->parent->subnodes[$idx];
            if ($node_tmp->semantic_name) {
              $targets[] = '/\b' . $node_tmp->semantic_name . '\b/';
              $replacements[] = '@' . ($idx + 1);
            }
          }
          $execution_string = $targets ? preg_replace($targets, $replacements,
              $action_prefix . $this->semantic_value . $action_suffix)
            : $action_prefix . $this->semantic_value . $action_suffix;
  
          // replace conventional targets
          $targets = array('/(?<!@)@@(?!@)/');
          $replacements = array('$__non_terminal');
          // leading symbol values
          for ($idx = 0;$idx < $this->my_index; $idx += 1 ) {
            // debugging code - enable if something bad happens
            // if ($this->verbose) {
            //   echo $node_tmp->dump("node $idx");
            // }
            $targets[] = '/@' . ($idx + 1) . '(?!\d)/';
            $replacements[] = '$__var' . ($idx + 1);  //"{$node_tmp->execution_value}";
          }
          $execution_string = preg_replace($targets, $replacements,  $execution_string) . ";return'';";
  
          $func_args = $context ? '$__context, &' . implode(',', $replacements) :  '&' . implode(',', $replacements);;
          
          if (($this->func = create_function($func_args, $execution_string)) === FALSE) {
            throw new ParserNodeException(__CLASS__ . "::render(): Parse error in function body; $this; execution_string: '$execution_string' [$this->semantic_value]");
          }
        }
  
        // set up arguments
        $arg_array = array();
         // leading symbol values
         for ($idx = 0;$idx < $this->my_index; $idx += 1 ) {
           $node_tmp = $this->parent->subnodes[$idx];
           $arg_array[] = $node_tmp->execution_value;
         }
        $parent_value = $this->parent->execution_value;
        if ($context) {
          array_unshift($arg_array, $context, &$parent_value);
        } else {
          array_unshift($arg_array, &$parent_value);
        }
        $this->execution_value = call_user_func_array($this->func, $arg_array);


        $this->parent->execution_value = $parent_value;
        return $this->execution_value;
      default:
        return "Bad Node: $this";
    }
  } // end of render()
  
  
  public function display_tree($msg = '', $indent = '') {
    $str = $msg ? "{$indent}$msg:\n{$indent}" : $indent;
    $semantic_value = $this->semantic_value ? strlen($this->semantic_value) > 10 ? substr($this->semantic_value, 0, 10) . " . . ." : "$this->semantic_value"
      : '';
    $semantic_value = htmlentities(preg_replace("/\\n/", '(n)', $semantic_value));
    switch ($this->type) {
      case ParserNode::PRODUCTION: $str .= "$this->syntactic_value / $semantic_value\n";
        break;
      case ParserNode::LITERAL: $str .= "$this->syntactic_value / $semantic_value\n";
        break;
      case ParserNode::STRING_ACTION: $str .= "string action\n"; break;
      case ParserNode::PHP_ACTION: $str .= "php action\n"; break;
      default: $str .= "Illegal Node Type". "\n"; break;
    }
    
    foreach ($this->subnodes as $subnode) {
      $str .= $subnode->display_tree('', $indent . '  ');
    }
    return $str;
  } // end of display_tree()

  public function dump($msg = '', $indent = '') {
    $str = $msg ? "\n$msg\n" : "\n";
    if (!$indent) {
      $msg = '';
    }
    $str .= "{$indent}" . get_class($this) . ": $this->type\n";
    $str .= "{$indent}values: synt: $this->syntactic_value, ";
    $str .= "sem: " . substr($this->semantic_value, 0, 30) . ", ";
    $str .= "exec: " . substr($this->execution_value, 0, 40) . "\n";
    $str .= "{$indent}lineage: self: $this->name, ";
    $str .= "up: " . ($this->parent?"{$this->parent->name}": "none") . ", ";
    $str .= "prev: " . ($this->previous?"{$this->previous->name}": "none") . ", ";
    $str .= "next: " . ($this->next?"{$this->next->name}": "none") . "\n";
    foreach ($this->subnodes as $subnode) {
      $str .= $subnode->dump(">> subnode " . $msg, $indent . " . ");
    }
    return $str;
  } // end of dump()
} // end of class ParserNode

/*
#begin-doc
h2(#parserlangnode). ParserLangNode - holds information about a legal language definition
symbol

h3. Instantiation

pre. $foo = new ParserLangNode($type, $value);

where the arugments are defined below in Attributes.

h3. Attributes

* type - type of node. Types are:
** production - then the value is the name of the non-terminal
** terminal - a terminal symbol
** non_terminal - a non terminal occuring in a production rule
** semantic_action - some PHP to be executed or symbols to be concatenated.
May contain references to the
symbols previously occurring _in this rule_. References can be $$, $1, ... as
in yacc and bison OR assigned _semantic_names_.
** error - error messages
* value - value of node. For productions, terminal, and non_terminal, the value is
the name of the symbol. For semantic actions, the value is the supplied PHP code.
For errors, the value is the error message.
* semantic_name - either a string or NULL
* productions - array() - only defined for type == 'production' nodes. Is an array
of productions for this non-terminal.

h3. Class Methods

None

h3. Instance Methods

* sort_productions(callback) - sorts the _productions_ array or throws an exception
if _this_ is not a _production_
* static production_as_string(production array) - returns the production as a comma separated
string. Throws an exception if _this_ is not a PRODUCTION node.
* production_as_string_by_index(index) - returns the 'index'th production as a comma
separated string.

#end-doc
*/


class ParserLangNodeException extends Exception {}

class ParserLangNode {
  private $type;
  private $value;
  private $semantic_name = NULL;
  private $productions = NULL;  // in case this node is a production rather than an entry in a production

  public function __construct($type, $value) {
    $this->type = $type;
    $this->value = $value;
    if ($type == ParserLangDefParser::PRODUCTION) {
      $this->productions = array();
    }
  } // end of __construct()
  
  public function __toString() {
    switch ($this->type) {
      case ParserLangDefParser::PRODUCTION:
        $str = "$this->value" . ($this->semantic_name ? " ($this->semantic_name) :\n" : " :\n");
        $str .= "     {$this->production_as_string_by_index(0)}\n";
        for ($idx=1;$idx<count($this->productions);$idx++) {
          $str .= "   | {$this->production_as_string_by_index($idx)}\n";
        }
        $str .= "   ;\n";
        return $str;
      case ParserLangDefParser::TERMINAL:
      case ParserLangDefParser::NONTERMINAL:
      case ParserLangDefParser::ERROR:
        return "$this->value" . ($this->semantic_name ? " ($this->semantic_name)" : "");
      case ParserLangDefParser::PHP_ACTION:
        return "%php{{$this->value}}" . ($this->semantic_name ? " ($this->semantic_name)" : "");
      case ParserLangDefParser::STRING_ACTION:
        return "%string{{$this->value}}" . ($this->semantic_name ? " ($this->semantic_name)" : "");
      default:
        return $this->dump("Unknown Type: ");
    }
    return "ParserLangNode($this->type / " . substr($this->value, 0, 40) . ")";
  } // end of __toString()

  public function production_as_string_by_index($idx) {
    if ($this->type != ParserLangDefParser::PRODUCTION) {
      throw new ParserLangNodeException("ParserLangNode::production_as_string_by_index($idx): this is not a PRODUCTION node");
    }
    if ($idx < 0 || $idx >= count($this->productions)) {
      throw new ParserLangNodeException("ParserLangNode::production_as_string_by_index($idx): index '$idx' out of range");
    }
    return ParserLangNode::production_as_string($this->productions[$idx]);
  } // end of production_as_string_by_index()
  
  public static function production_as_string($production) {
    static $func = NULL;
    if (!$func) {
      $func = create_function('$s', 'return "$s";');
    }
    $str = preg_replace("/\n/", '\n', implode(' ', array_map($func, $production)));
    return $str;
    // return strlen($str) > 40 ? substr($str, 0, 40) . '. . .' : $str;
  } // end of production_as_string()

  public function __get($name) {
    switch ($name) {
      case 'type':
      case 'value':
      case 'semantic_name':
      case 'productions':
        return $this->$name;
      default:
        throw new ParserLangNodeException("ParserLangNode::__get($name): illegal attribute name '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'type':
      case 'value':
        throw new ParserLangNodeException("ParserLangNode::__set($name, value): attempt to set read only attribute '$name'");
      case 'productions':
        if ($this->type != ParserLangDefParser::PRODUCTION) {
          throw new ParserLangNodeException("ParserLangNode::__set(): attempt to add production definition to non-production: $this");
        }
        $this->productions[] = $value;
        break;
      case 'semantic_name':
        if (!$this->$name) {
          $this->$name = $value;
        } else {
          throw new ParserLangNodeException("ParserLangNode::__set($name, value): attempt to set write once attribute '$name'");
        }
        break;
      default:
        throw new ParserLangNodeException("ParserLangNode::__set($name): illegal attribute name '$name'");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {

      case 'value':
      case 'semantic_name':
      case 'productions':
        return isset($this->$name) && $this->$name;
      default:
        throw new ParserLangNodeException("ParserLangNode::__isset($name): illegal attribute name '$name'");
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'type':
      case 'value':
      case 'semantic_name':
      case 'productions':
        throw new ParserLangNodeException("ParserLangNode::__unset($name): attempt to unset attribute '$name'");
      default:
        throw new ParserLangNodeException("ParserLangNode::__unset($name): illegal attribute name '$name'");
    }
  } // end of __unset()

  public function sort_productions($callback) {
    usort($this->productions, $callback);
    if (in_array(array(), $this->productions)) {
      $this->productions = array_filter($this->productions, create_function('$x', 'return $x != array();'));
      $this->productions[] = array();
    }
  } // end of sort_productions()
  
  public function dump($msg = '') {
    $str = $msg ? "$msg: " : "";
    $str .= "$this->type: ";
    if ($this->semantic_name) {
      $str .= "({$this->semantic_name}) ";
    }
    $str .= strlen($this->value) > 40 ? "'" . substr($this->value, 0, 40) . " . . .'\n" : "'$this->value'\n";
    if ($this->type == ParserLangDefParser::PRODUCTION) {
      foreach ($this->productions as $prod) {
        $node_leader = "  | ";
        if ($prod) {
          foreach ($prod as $node) {
            $str .= $node->dump($node_leader);
            $node_leader = '    ';
          }
        } else {
          $str .= "  |\n";
        }
      }
      $str .= "  ;\n";
    } 
    return $str;
  } // end of dump()
}

/*
#begin-doc
h2(#parserlangdefparser). ParserLangDefParser - parses language definitions

h3. Instantiation

h3. Attributes

* language_def - original string passed to object
* language_ar - array of arrays of ParserLangNode instances which implement
the language definition. Keys are by production names.
* start_symbol - string - the starting production for the language. This will
Either be the first production encountered OR the value taken from the
phrase '%start non-terminal', where _non-terminal_ is a non_terminal of the
language.

h3. Class Methods

None

h3. Instance Methods

Usual magic methods, dump(), and . . .

Recursive descent parsers - like all parsers - are very sensitive to the order in which
productions are tested. For example, for the language 'foo: A | A foo;', when
presented with the string 'AAAA', the parser will announce completion after seeing
the first 'A' and will ignore the trailing 'AAA'. If the grammar is rewritten
as 'foo: A foo | A;', then the parser will recognize 'AAAA' and finish processing
the string. This is a good thing.

So we provide (crude) sorting support for fixing sloppily written (or edited) grammars.
Calling _sort()_ is optional, so one strategy for testing to see if your grammar
is really what you want is:

# feed your grammar to ParserLangDefParser - by creating an instance.
# print the instance as a string - this will print out your grammar in a nicely
indented display.
# invoke the _sort()_ method on your instance.
# print out the instance as a string (again) and compare it with the first printout.
# then do something which you think is a good idea.

* sort(cmp_func = ParserLangDefParser::cmp_productions) - sorts the language productions
using the comparision function. The default comparison function sorts the products
so that:
** the productions with the most non-terminals are tested first. The order is in reverse
of the number of non-terminals in a production. For example foo: a b c | T b c | T S c | T
** within productions with the same number of non-terminals, they are sorted so that
the longest are tested first.
** if any empty productions exist, they are deleted and a single empty production is
tested last.
* cmp_productions(p1, p2) - used to sort the production array. returns -1, 0, or 1
if p1 < p2, p1 == p2, or p1 > p2 as determined by the number of non-terminals
in each production and - for equal numbers of non-terminals - which is longer.
In all cases, -1 means p1 is more complex than p2.

#doc-stop
Parsing support

* first($symbol) - TRUE or array - taken from the Dragon book section 5.5; page 188, 1977 edition.
_first()_ examines all productions which are in the definition of _$symbol_ or which
can be reached by empty production and returns an array 'starting symbols' - that
is all the terminal symbols which start productions which can reduce to the non-terminal
we are examing. For example, if _$symbol_ is a terminal, then that's as far as it goes
and _first()_ returns an array containing the symbol itself. If _$symbol_ is a
non-terminal with no empty production(s) and which can produce strings beginning
with X and Y, but not Z, then _first()_ returns array('X', 'Z').
_non-terminals_ which have empty productions get the value TRUE, instead of an array.
If _$symbol_ is anything else, _first()_
returns the value TRUE which means _yep - we match everything, but don't consume
tokens_. 
#doc-start
#end-doc
*/

class ParserLangDefParserException extends Exception {}

class ParserLangDefParser {
  const PRODUCTION = 'production';
  const PRODUCTION_NEW = 'new_production';
  const PRODUCTION_END = 'production_end';
  const TERMINAL = 'terminal';
  const NONTERMINAL = 'non_terminal';
  const PHP_ACTION = 'php_action';
  const STRING_ACTION = 'string_action';
  const SEMANTIC_NAME = 'semantic_name';
  const START_SYMBOL = 'start_symbol';
  const CODE = 'code';
  const CODE_START = 'code_start';
  const CODE_END = 'code_end';
  const ERROR = 'error';
  const LEX_ERROR = 'lex_error';
  
  static public $parser_language_states = array(
    array('init', 'emit_error.no legal symbol recognized',
      array('/^\s*%start\s+(\w+)/', 'init', 'push_tag.start_symbol,add_matched.1,emit,pop_tag'),
      array('/^\s*%(action_prefix|action_suffix)\{/', 'code', 'push_tag.code_start, add_matched.1,emit,pop_tag'),
      array('/^\s*error\b/', 'init', 'push_tag.error,add_literal.error,emit,pop_tag'),
      array('/^\s*([a-z]\w*)\s*:(?!>:)/', 'init', 'push_tag.production,add_matched.1,emit,pop_tag, push_tag.new_production,add_literal.:,emit,pop_tag'),
      array('/^\s*([a-z]\w*)\s*\(\s*(\w+)\s*\)\s*:(?!>:)/', 'init',
        'push_tag.production,add_matched.1,emit,pop_tag, push_tag.semantic_name,add_matched.2,emit,pop_tag, push_tag.new_production,add_literal.:,emit,pop_tag'),
      array('/^\s*([A-Z]\w*)/', 'init', 'push_tag.terminal, add_matched.1, emit, pop_tag'),
      array('/^\s*\'([[:punct:]])\'/', 'init', 'push_tag.terminal,add_matched.1,emit,pop_tag'),
      array('/^\s*([a-z]\w*)/', 'init', 'push_tag.non_terminal, add_matched.1, emit, pop_tag'),
      array('/^\s*\(\s*(\w+)\s*\)/', 'init', 'push_tag.semantic_name, add_matched.1, emit, pop_tag'),
      array('/^\s*;\s*/', 'init', 'push_tag.production_end, add_literal.;,emit,pop_tag'),
      array('/^\s*\|\s*/', 'init', 'push_tag.new_production, add_literal.|, emit, pop_tag'),
      array('/^\s*%php{/', 'php_action', 'push_tag.php_action,discard_matched, init_counter'),
      array('/^\s*%str(ing)?{/', 'string_action', 'push_tag.string_action,discard_matched, init_counter'),
      array('|(?sU)^\s*/\*.*\*/|s', 'init', 'discard_matched'),  // discard comments
      array('/^\s*$/', 'init', 'discard_matched'),
    ),
    // php_action gathers PHP code up to the matching closing brace. The tag must be pushed prior to entry
    array('php_action', 'emit_error.error in scanning php action',
      array('/^(\s*)(\\\\([{}]))/', 'php_action', 'add_matched.1,add_matched.3'),
      array('/^\s*{/', 'php_action', 'inc_counter, add_matched'),
      array('/^(\s*)}\s*/', 'php_action', 'dec_counter, add_matched_if_counter, stop_if_counter, add_matched.1, emit, pop_tag, new_state.init'),
      array('/^([^}{](?!\\\\[{}]))*/', 'php_action', 'add_matched'),
    ),
    array('string_action', 'emit_error.error in scanning string action',
      array('/^\s*{/', 'string_action', 'inc_counter, add_matched'),
      array('/^\s*}\s*/', 'string_action', 'dec_counter, add_matched_if_counter, stop_if_counter, emit, pop_tag, new_state.init'),
      array('/^[^}{]*/', 'string_action', 'add_matched'),
    ),
    array('code', 'emit_error.error scanning code',
      array('/^\s*%}/', 'init', 'push_tag.code,emit,pop_tag, push_tag.code_end,add_literal.%},emit,pop_tag'),
      array('/(?sU).*(?=%})/', 'code', 'add_matched'),
    ),
  );
  
  private $language_ar;
  private $language_def;
  private $non_terminal_names = array();
  private $start_symbol = NULL;
  private $terminal_names = array();
  private $terminal_to_production_map = array();
  private $terminals = array();
  private $action_prefix = '';
  private $action_suffix = '';
  private $verbose = FALSE;
  private $first_ar = array();

  public function __construct($language_def, $verbose = FALSE) {
    $this->language_def = $language_def;
    $this->verbose = $verbose;
    $scanner = new YAScanner('init', ParserLangDefParser::$parser_language_states, 'lex_error');
    if (!is_string($language_def)) {
      throw new ParserLangDefParserException("ParserLangDefParser::__construct(def): language definition is not a string");
    }
    $this->language_ar = array();
    $cur_production = NULL;
    $cur_production_ar = FALSE;
    $non_terminals_used = array();
    $scanner->process($language_def, 'language_def');
    
    while ((@list($token, $value, $line_no) = $scanner->token())) {
// echo "------- $token / $value\n";
      switch ($token) {
        case ParserLangDefParser::START_SYMBOL:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found %start\n";
          $this->start_symbol = $value;
          break;
        case ParserLangDefParser::CODE_END:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found %}\n";
          break;
        case ParserLangDefParser::CODE_START:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found %{$value}{\n";
          $cur_code_section = $value;
          break;
        case ParserLangDefParser::CODE:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found code body\n";
          $this->$cur_code_section = $value;
          break;
        case ParserLangDefParser::PRODUCTION:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found definition for non-terminal '$value'\n";
          if ($cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): production def started w/o finishing previous def: $token: line_no: $line_no");
          }
          $cur_production =
            $cur_node = new ParserLangNode($token, $value);
          $this->language_ar[$value] = $cur_production;
          if (!$this->start_symbol) {
            $this->start_symbol = $value;
          }
          break;
        case ParserLangDefParser::PRODUCTION_NEW:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found production start for $cur_production->value\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): production def start encountered w/o production defined: {$token}: line_no: $line_no");
          }
          if ($cur_production_ar !== FALSE) {
            $cur_production->productions = $cur_production_ar;
          }
          $cur_production_ar = array();
          $cur_node = NULL;
          break;
        case ParserLangDefParser::PRODUCTION_END:  // encountered a ';'
          if ($this->verbose) echo "$scanner->file_name: $line_no: found production end for $cur_production->value\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): production def end encountered w/o production defined: $token: line_no: $line_no");
          }
          $cur_production->productions = $cur_production_ar;
          $cur_production = NULL;
          $cur_production_ar = FALSE;
          $cur_node = NULL;
          break;
        case ParserLangDefParser::TERMINAL:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found terminal '$value'\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): TOKEN encountered w/o production defined: $token: line_no: $line_no");
          }
          $cur_production_ar[] =
            $cur_node = new ParserLangNode(ParserLangDefParser::TERMINAL, $value);
            
          if (!isset($this->terminals[$value])) {
            $this->terminals[$value] = $cur_node;
          }
          break;
        case ParserLangDefParser::ERROR:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found error handling point found '$value'\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): special 'error' symbol encountered w/o production defined: $token: line_no: $line_no");
          }
          $cur_production_ar[] =
            $cur_node = new ParserLangNode(ParserLangDefParser::ERROR, $value);
          break;
        case ParserLangDefParser::NONTERMINAL:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found non-terminal '$value' reference\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Non-Terminal end encountered w/o production defined: $token: line_no: $line_no");
          }
          if ($value == 'error') {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): $scanner->file_name: $line_no: Illegal use of special symbol 'error' in production definition");
          }
          $cur_production_ar[] =
            $cur_node = new ParserLangNode(ParserLangDefParser::NONTERMINAL, $value);
          if (!isset($non_terminals_used[$value])) {
            $non_terminals_used[$value] = array($cur_production->value);
          } else {
            $non_terminals_used[$value][] = $cur_production->value;
          }
          break;
        case ParserLangDefParser::PHP_ACTION:
        case ParserLangDefParser::STRING_ACTION:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found '$token' action\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): $token Action end encountered w/o production defined: $token: line_no: $line_no");
          }
          $cur_production_ar[] =
            $cur_node = new ParserLangNode($token, $value);
          break;
        case ParserLangDefParser::SEMANTIC_NAME:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found semantic name '$value'\n";
          if (!$cur_production) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Semantic Name encountered w/o production defined: $token: line_no: $line_no");
          } elseif (!$cur_node) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Semantic Name encountered w/o node defined: $token: line_no: $line_no");
          } elseif (isset($cur_node->semantic_name)) {
            throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Semantic Name for node already defined: $token: line_no: $line_no");            
          } else {
            $cur_node->semantic_name = $value;
          }
          break;
        case ParserLangDefParser::LEX_ERROR:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found error\n";
          throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Language Definition Lexical Scan Error encountered: $token / $value");
        default:
          if ($this->verbose) echo "$scanner->file_name: $line_no: found illegal value\n";
          throw new ParserLangDefParserException("ParserLangDefParser::__construct(def): Illegal token encountered '$token'");
      }
      $scanner->advance();
    }
    
    // check for proper termination of definition
    if ($cur_production) {
      $scanner->push_back();
      throw new ParserLangDefParserException("ParserLangDefParser::__construct(): improperly terminated language - missing production end symbol: $token: line_no: $line_no");
    }
    // make sure the start symbol is defined
    if (!array_key_exists($this->start_symbol, $this->language_ar)) {
      throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Specified start symbol '$this->start_symbol' is NOT a known non-terminal");
    }
    if (($tmp_ar = array_diff(array_keys($non_terminals_used), array_keys($this->language_ar)))) {
      $str = '';
      foreach ($tmp_ar as $non_terminal) {
        $str .= "Nonterminal '$non_terminal not defined but used in definitions of '"
          . implode(',', $non_terminals_used[$non_terminal]) . "'\n";
      }
      throw new ParserLangDefParserException("ParserLangDefParser::__construct(): $str");
    }
    $this->terminal_names = array_keys($this->terminals);
    sort($this->terminal_names);
    $this->non_terminal_names = array_keys($this->language_ar);
    sort($this->non_terminal_names);
    // error proceeds for all terminals
    $this->first_ar['error'] = TRUE;
    foreach ($this->non_terminal_names as $symbol) {
      if (!$this->first($symbol)) {
        throw new ParserLangDefParserException("ParserLangDefParser::__construct(): Language Definition Error: non-terminal '$symbol' cannot be reduced");
      }
    }
  }
    
    // sort language
    // we want to sort the productions so that we process them in the 'right order'.
    // We want all the productions with non-terminals on top and those which are prefixes of
    //  rules, below the rules they are a prefix of. We accomplish this by sorting 
  public function sort($cmp = NULL) {
    // echo $this->dump("Before Sorting");
    foreach ($this->language_ar as $key => $node) {
      $node->sort_productions($cmp ? $cmp : array($this, 'cmp_productions'));
    }
  } // end of __construct()

  public function cmp_productions($p1, $p2) {
    // static $is_terminal = NULL;
    static $is_nonterminal = NULL;
    static $is_symbol = NULL;
    // static $is_action = NULL;

    if (!$is_symbol) {
      // $is_terminal = create_function('$n', 'return $n->type == "terminal";');
      $is_nonterminal = create_function('$n', 'return $n->type == "non_terminal";');
      $is_symbol = create_function('$n', 'return $n->type == "non_terminal" || $n->type == "terminal";');
    }
    
    // more non-terminals goes up
    $c1_nonterminals = count(array_filter($p1, $is_nonterminal));
    $c2_nonterminals = count(array_filter($p2, $is_nonterminal));
    if ($c1_nonterminals != $c2_nonterminals) {
      return $c1_nonterminals > $c2_nonterminals ? -1 : 1;
    }
    // at this point, p1 and p2 have the same number of nonterminals, so sort in symbol count order
    //   NOTE: this automatically moves prefixes down the stack
    $c1_symbols = count(array_filter($p1, $is_symbol));
    $c2_symbols = count(array_filter($p2, $is_symbol));
    if ($c1_symbols != $c2_symbols) {
      return $c1_symbols > $c2_symbols ? -1 : 1;
    }
    
    // I think we're done at this point.
    return 0;
  } // end of cmp_productions()

  public function __toString() {
    $str = "%start {$this->start_symbol}\n";
    foreach ($this->language_ar as $node) {
      $str .= (string)$node;
    }
    return $str;
  } // end of __toString()
  
  public function __get($name) {
    switch ($name) {
      case 'action_prefix':
      case 'action_suffix':
      case 'language_def':
      case 'language_ar':
      case 'non_terminal_names':
      case 'start_symbol':
      case 'terminal_names':
      case 'terminals':
      case 'verbose':
        return $this->$name;
      default:
        throw new ParserLangDefParserException("ParserLangDefParser::__get(): attempt to get illegal attribute '$name'");
    }
  } // end of __get()

  public function __set($name, $value) {
    switch ($name) {
      case 'verbose':
        $this->$name = $value ? TRUE : FALSE;
        break;
      default:
        throw new ParserLangDefParserException("ParserLangDefParser::__set(): attempt to set read only or illegal attribute '$name'");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {
      case 'action_prefix':
      case 'action_suffix':
      case 'language_ar':
      case 'language_def':
      case 'non_terminal_names':
      case 'start_symbol':
      case 'terminal_names':
      case 'terminals':
      case 'verbose':
        return isset($this->$name);
      default:
      throw new ParserLangDefParserException("ParserLangDefParser::__isset(): isset tested on illegal attribute '$name'");
    }
  } // end of __isset()
  
  public function __unset($name) {
    throw new ParserLangDefParserException("ParserLangDefParser::__unset(): attempt to unset read only or illegal attribute '$name'");
  } // end of __unset()
  
  public function dump($msg = '') {
    $str = $msg ? "$msg:\n" : '';
    $str .= "Terminals:\n";
    foreach ($this->terminal_names as $terminal) {
      $str .= "  terminal: $terminal\n";
    }
    $str .= "Non-Terminal Definitions:\n";
    foreach ($this->language_ar as $nonterminal => $node) {
      $str .= $node->dump($nonterminal);
      $str .= "  first:" . ($this->first($nonterminal) === TRUE ? 'TRUE' : implode(',', $this->first($nonterminal))) . "\n";
    }
    return $str;
  } // end of dump()

  // Well, it's not hard to write the Dragon Book first function, but it's useful
  //  for what I want it to do: short cut production evaluation, detect infinite loops,
  //  and handle left-recursive grammars. For that I need something stronger - which
  //  includes information about what symbols in a production to skip and various other
  //  things. Nasty, frustrating, and boring.
  
  // Dragon book section 5.5; page 188
  //  This returns an array of terminal symbols which must be the current token in order
  //  to produce a string derived from _$symbol_
  public function first($symbol) {
    // define a throw-away function for reducing production arrays to their essentials for analysis
    static $select_symbols_func = NULL;
    static $recursion_detection_stack = array();
  
    if (!$select_symbols_func) {
      $select_symbols_func = create_function('$n', 'return $n->type == ParserLangDefParser::TERMINAL || $n->type == ParserLangDefParser::NONTERMINAL;');
    }
    
    // check the 'first()' cache
    if (array_key_exists($symbol, $this->first_ar)) {
      return $this->first_ar[$symbol];
    }
    
    // if $symbol is a terminal, then 'first' is array(symbol)
    if (array_key_exists($symbol, $this->terminals)) {
      return ($this->first_ar[$symbol] = array($symbol));
    } elseif (array_key_exists($symbol, $this->language_ar)) {
      // this is a production, so we want to find all the leading terminals
      $ar = array();
      $symbol_node = $this->language_ar[$symbol];
      array_push($recursion_detection_stack, $symbol);
  
      $prod_idx = 0;
      foreach ($symbol_node->productions as $production) {
        // strip the production down to terminals and non-terminals. We scan over this,
        //  but save entire productions in the first_ar array.
  
        // if $prod_tmp is an empty production, then set first_ar[] to TRUE and terminate
        if (!array_filter($production, $select_symbols_func)) {
          array_pop($recursion_detection_stack);
          return ($this->first_ar[$symbol] = TRUE);
        } else {
          $len = count($production);
          $idx = 0;
          while ($idx < $len) {
            $node_tmp = $production[$idx++];
            // if the node is a terminal, add this production to the array and go to next production
            switch ($node_tmp->type) {
              case ParserLangDefParser::TERMINAL:
                $ar[] = $node_tmp->value;
                $idx = $len;
                break;
              case ParserLangDefParser::NONTERMINAL:
                // we've found a non-terminal node, so examine it to see if it works
                $sym_tmp = $node_tmp->value;
  
                // if we find ourselves prior to finding a way to move forward, this will loop,
                //  so skip this production
                if ($sym_tmp == $symbol) {
                  $idx = $len;
                  continue;
                }
                // check for infinite recursion. If not detecetd, examine first($sym_tmp)
                if (!in_array($sym_tmp, $recursion_detection_stack)) {
                  array_push($recursion_detection_stack, $sym_tmp);
                  if (is_array(($first_tmp = $this->first($sym_tmp)))) {
                    foreach ($this->first($sym_tmp) as $tmp) {
                      $ar[] = $tmp;
                    }
                  } elseif ($first_tmp === TRUE) {
                    array_pop($recursion_detection_stack);
                    array_pop($recursion_detection_stack);
                    return ($this->first_ar[$symbol] = TRUE);
                  } else {
                    throw new ParserLangDefParserException("ParserLangDefParser::first(): Illegal fisrt() value for non-terminal '$sym_tmp'");
                  }
                  $idx = $len;
                  array_pop($recursion_detection_stack);
                } else {
                  throw new ParserLangDefParserException("ParserLangDefParser::first(): infinite recursion detected when calling first($sym_tmp) from first($symbol)");
                }
                break;
              default:
                break;
            }
          }
        }
      }
      array_pop($recursion_detection_stack);

      return ($this->first_ar[$symbol] = array_unique($ar));
    } else {
      return ($this->first_ar[$symbol] = TRUE);
    }

    // cache first and return
    throw new ParserLangDefParserException("ParserLangDefParser::first(): internal error - should never be reached:"
      . basename(__FILE__) . ": " . __LINE__);
  } // end of first()
  
  public function display_first($symbol) {
    $first = $this->first($symbol);
    if (is_bool($first)) {
      return $first ? 'TRUE' : 'FALSE';
    } elseif (is_array($first)) {
      return '{ ' . implode(', ', $first) . ' }';
    } else {
      return (string)$first;
    }
  } // end of display_first()
}

class ParserException extends Exception {}

class Parser {
  private $node_class_name;
  private $node_stack = array();
  private $str;
  private $str_valid = FALSE;
  private $production_mark_stack = array();
  
  private $context;
  private $language;
  private $productions;
  private $scanner;
  private $error_mode = FALSE;
  private $error_node = NULL;  // populated with the error node if an error is detected
  private $rendered_content = NULL;
  
  // output control
  public $trace = FALSE;
  public $verbose = FALSE;
  public $indent = '';
  
  public function __construct($language_def, YAScannerBase $scanner, $context = NULL, $verbose = FALSE, $node_class_name = 'ParserNode') {
    $this->language = new ParserLangDefParser($language_def, FALSE);
    $this->productions = $this->language->language_ar;
    $this->node_class_name = $node_class_name ? $node_class_name : 'ParserNode';
    $this->context = $context;
    if (!class_exists($this->node_class_name)) {
      require_once($this->node_class_name . ".php");
    }
    $this->scanner = $scanner;
    $this->str = NULL;
    $this->verbose = $verbose;
  } // end of __construct()
  
  public function __toString() {
    return "Parser(" . substr($this->language->language_def, 0, 60) . ")";
  } // end of __toString()
  
  public function __get($name) {
    switch ($name) {
      case 'context':
      case 'error_mode':
      case 'error_node':  // this is probably a mistake
      case 'indent':
      case 'language':
      case 'node_stack':
      case 'productions':
      case 'scanner':
      case 'str':
      case 'str_valid':
      case 'trace':
      case 'verbose':
        return $this->$name;
        break;
      case 'cur_node':
        return $this->node_stack[0];
      case 'root':
        if (!$this->node_stack) {
          throw new ParserException("Parser::__get(): attempt to get 'root' on empty stack");
        }
        return $this->node_stack[count($this->node_stack) - 1];
      default:
        throw new ParserException("Parser::__get($name): illegal attribute name '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'cur_node':
      case 'error_mode':
      case 'error_node':  // this is probably a mistake
      case 'indent':
      case 'language':
      case 'node_stack':
      case 'productions':
      case 'scanner':
      case 'str':
      case 'str_valid':
        throw new ParserException("Parser::__set($name, value): attempt to set read-only attribute '$name'");
      case 'context':
        $this->$name = $value;
        break;
      case 'trace':
      case 'verbose':
        $this->$name = $value ? TRUE : FALSE;
        break;
      default:
        throw new ParserException("Parser::__set($name): illegal attribute name '$name'");
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {
      case 'context':
      case 'error_mode':
       case 'error_node':  // this is probably a mistake
      case 'indent':
      case 'language':
      case 'node_stack':
      case 'productions':
      case 'scanner':
      case 'str':
      case 'str_valid':
      case 'trace':
      case 'verbose':
        return isset($this->$name);
        break;
      case 'cur_node':
        return isset($this->node_stack[0]);
        break;
      default:
        throw new ParserException("Parser::__isset($name): illegal attribute name '$name'");
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'context':
      case 'cur_node':
      case 'error_mode':
      case 'error_node':  // this is probably a mistake
      case 'indent':
      case 'language':
      case 'node_stack':
      case 'productions':
      case 'scanner':
      case 'str':
      case 'str_valid':
      case 'trace':
      case 'verbose':
        throw new ParserException("Parser::__unset(): attempt to unset read-only attribute '$name'");
        break;
      default:
        throw new ParserException("Parser::__unset($name): illegal attribute name '$name'");
    }
  } // end of __unset()

  // node handling
  private function node_top() {
    return $this->node_stack ? $this->node_stack[0] : FALSE;
  } // end of node_top()
  
  private function push_node($node) {
    array_unshift($this->node_stack, $node);
  } // end of push_node()
  
  private function pop_node() {
    if (count($this->node_stack) <= 0) {
      throw new ParserException("Parser::pop_node(): attempt to pop empty node stack");
    }
    return array_shift($this->node_stack);
  } // end of pop_node()
  
  // infinite recursion testing
  private function mark_production_start($nonterminal_name) {
    if (isset($this->production_mark_stack[$nonterminal_name])) {
      if ($this->production_mark_stack[$nonterminal_name]
            && $this->production_mark_stack[$nonterminal_name][0] >= $this->scanner->token_pointer) {
        throw new ParserException("Parser::production($nonterminal_name): infinite recursion detected for nonterminal $nonterminal_name");
      } else {
        array_unshift($this->production_mark_stack[$nonterminal_name], $this->scanner->token_pointer);
      }
    } else {
      $this->production_mark_stack[$nonterminal_name] = array($this->scanner->token_pointer);
    }
  } // end of mark_production_start()
  
  private function mark_production_finish($nonterminal_name) {
    array_shift($this->production_mark_stack[$nonterminal_name]);
    if (empty($this->production_mark_stack[$nonterminal_name])) {
      unset($this->production_mark_stack[$nonterminal_name]);
    }
  } // end of mark_production_finish()

  private function display_production_mark_stack($msg = '') {
    $str = $msg ? "$msg\n" : '';
    $str .= "production_mark_stack: (top on left):\n";
    foreach ($this->production_mark_stack as $prod => $stack) {
      $str .= " $prod: [" . implode(' > ', $stack) . "]\n";
    }
    return $str;
  }

  // here's where we always start
  public function parse($str, $file_name = '-') {
    $this->str = $str;
    $this->scanner->process($str, $file_name);
    $this->node_stack = array();
    $this->production_mark_stack = array();
    $this->error_mode = FALSE;
    $this->error_node = NULL;
    $this->indent = '';
    $start_nt_node = $this->language->language_ar[$this->language->start_symbol];
    $root_parser_node = new $this->node_class_name(ParserNode::PRODUCTION, $start_nt_node->value, '', '',
        $start_nt_node->semantic_name);
    $root_parser_node->verbose = $this->verbose;
    if (!$root_parser_node instanceof ParserNode) {
      throw new ParerException("Parser::parse(): Supplied Parser Node is not an extension of ParserNode - cannot proceed");
    }
    $this->push_node($root_parser_node);
    $this->str_valid = $this->start();

    return $this->str_valid;
  } // end of parse()

  private function start() {
    if (FALSE && $this->verbose) {
      echo "\nParsing String: '$this->str':\n";
      echo "{$this->indent}Language " . $this->language->language_def . "\n";
    }

    
    // return TRUE if a prefix in _str_ parsed correctly AND the entire string was used
    if ($this->production($this->language->start_symbol) && !$this->error_mode
          && $this->scanner->token() === FALSE) {
      return TRUE;
    } else {
      // create error node
      $error_mode = $this->error_mode ? "error mode is set" : "error mode is NOT set";
      if ($this->scanner->token() !== FALSE) {
        $scanning_complete = "scanning terminated early";
        list($token, $token_value, $line_no) = $this->scanner->token();
        $content_ar = array_slice(preg_split("/\\n/", $this->scanner->raw_string), $line_no - 2, 10);
        $idx = $line_no - 1;
        $error_section = '';
        foreach ($content_ar as $line) {
          $error_section .= sprintf("%3d: %s\n", $idx++, htmlentities($line));
        }
      } else {
        $scanning_complete = "scanning is complete";
        $content_ar = preg_split("/\\n/", $this->scanner->raw_string);
        $line_no = count($content_ar) - 12;
        $content_ar = array_slice($content_ar, $line_no);
        $idx = $line_no - 1;
        $error_section = '';
        foreach ($content_ar as $line) {
          $error_section .= sprintf("%3d: %s\n", $idx++, htmlentities($line));
        }
      }
      $error_message = "Syntax Error: Parsing file {$this->scanner->file_name} failed:\n$this->scanner\n"
        . "$error_mode\n$scanning_complete:\n"
        . "$error_section\n"
        // . $this->root->display_tree()
        // . $this->scanner->dump();
        ;
      $nd = new $this->node_class_name(ParserNode::LITERAL, 'error', '<pre>' . htmlentities($error_message) . '</pre>',
          'error');
      $nd->verbose = $this->verbose;
      $this->error_node = $nd;
      $this->cur_node->add_node($nd);
      $this->error_mode = TRUE;
      return FALSE;
    }
  } // end of start()
  
  
  private function production($nonterminal_name, $production_list = NULL) {
    // args:
    //  $nonterminal_name - name of a non-terminal in the language
    //  $production_list - either NULL or a list of (production index, starting symbol index) pairs
    if ($this->verbose) echo "\n{$this->indent}===========Entering Production($nonterminal_name)===========\n";
    // we try to detect infinite recursion by checking to see (1) if this production is in
    //  process (occurs lower on the node stack) AND (2) that the token_pointer has not advanced
    //  since that last occurance.
    $this->mark_production_start($nonterminal_name);

    // nt_node stands for 'non_terminal node'
    $nt_node = $this->productions[$nonterminal_name];

    if ($this->verbose) echo $this->display_parser_state("Starting Production $nonterminal_name");

    // check all leading non-terminals
    if ($this->scanner->token() === FALSE) {
      // we are done, so mark it
      $this->mark_production_finish($nonterminal_name);
      
      // check to see we suceeded
      if (in_array(array(), $nt_node->productions)) {
        if ($this->verbose) echo "{$this->indent}accept: no tokens and empty production found\n";
        if ($this->verbose) echo "{$this->indent}===========Leaving Production()===========\n";
        return TRUE;
      } {
        if ($this->verbose) echo "{$this->indent}fail: no tokens and NO empty production found\n";
        if ($this->verbose) echo "{$this->indent}===========Leaving Production()===========\n";
        return FALSE;
      }
    }

    // have tokens, so check non-empty transitions
    $prod_index = 1;
    foreach ($nt_node->productions as $production) {
      if ($this->trace) {
        echo "{$this->indent}$nonterminal_name: production $prod_index\n";  // ": " . implode(' ', $production) . "\n";
        $prod_index += 1;
      }
      if ($this->verbose) echo "\n=========Checking Production: '" . ParserLangNode::production_as_string($production) . "'\n";

      // there are tokens, so check them
      if ($this->process_production($production, $nonterminal_name)) {
        // at this point, we have processed every symbol in the list of symbols for
        //  this specific production without returning FALSE, so we have matched the string
        if ($this->verbose) echo $this->display_parser_state("satisfied production " . ParserLangNode::production_as_string($production) . "  " . __LINE__);
        if ($this->verbose) echo "{$this->indent}===========Leaving Production()===========\n";
        $this->mark_production_finish($nonterminal_name);
        return TRUE;
      }
    }

    // failed to find a production which works
    // if ($this->verbose) echo $this->dump("no alternative works " . __LINE__);
    if ($this->verbose) echo "{$this->indent}===========Leaving production method===========\n";
    if ($this->verbose) $this->display_parser_state("No Alternative Works: " . __LINE__);
    $this->cur_node->discard_nodes();
    $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
    $this->mark_production_finish($nonterminal_name);
    return FALSE;
  } // end of production()

  private function process_production($production, $nonterminal_name, $starting_symbol = 0) {
    $defining_nonterminal = $this->language->language_ar[$nonterminal_name];
    foreach ($production as $symbol) {
      list($token_syntactic_value, $token_semantic_value, $line_no) = $this->scanner->token();
      switch ($symbol->type) {
        case ParserLangDefParser::TERMINAL:
          if (!in_array($token_syntactic_value, $this->language->terminal_names)) {
            if ($this->verbose) echo $this->display_parser_state("  Unknown terminal symbol: $token_syntactic_value definition of $nonterminal_name: line " . __LINE__);
            $this->cur_node->discard_nodes();
            $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
            return FALSE;
          }
          
          if ($token_syntactic_value == $symbol->value) {
            if ($this->verbose) echo $this->display_parser_state("  terminal '$symbol' FOUND: line " . __LINE__);
            // $type, $syntactic_value, $semantic_value, $semantic_name
            $nd = new $this->node_class_name(ParserNode::LITERAL, $token_syntactic_value, $token_semantic_value, $symbol->semantic_name);
            $nd->verbose = $this->verbose;
            if ($this->verbose) echo $nd->dump("Adding terminal $token_syntactic_value");
            $this->cur_node->add_node($nd);
            $this->scanner->advance();
          } else {
            if ($this->verbose) echo $this->display_parser_state("  terminal '$symbol' NOT FOUND: line " . __LINE__);
            $this->cur_node->discard_nodes();
            $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
            return FALSE;
          }
          break;
        case ParserLangDefParser::NONTERMINAL:
          // check to see if this token is in first($non-terminal). If not, return failure
          if (is_array($first_tmp = $this->language->first($symbol->value)) && !in_array($token_syntactic_value, $first_tmp)) {
// echo "symbol->value: "; var_dump($symbol->value);
// echo "first_tmp: "; var_dump($first_tmp);
// echo "token_syntactic_value: "; var_dump($token_syntactic_value);
// echo "scanner: $this->scanner\n";
            if ($this->verbose) {
              echo $this->display_parser_state("  Production $symbol NOT FOUND: line " . __LINE__
                . ': first set: ' . implode(',', $first_tmp));
            }
            $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
            // no need to pop node stack, because we haven't pushed this node yet
            // $this->pop_node();
            // now cur_node is the guy we were appending nodes to, so we need to discard everything
            //   we added in the current, failed production
            $this->cur_node->discard_nodes();
            
            return FALSE;
          }

          if (!is_array($first_tmp) && $first_tmp !== TRUE) {
            throw new ParserException("Parser::process_production(): Illegal first() for non-terminal $symbol->value");
          }

          // if ($this->verbose) echo $this->display_parser_state("checking for non-terminal $symbol: line " . __LINE__);
          $nt_node_tmp = $this->productions[$symbol->value];
          // $type, $syntactic_value, $semantic_value, $semantic_name
          $nd = new $this->node_class_name(ParserNode::PRODUCTION, $nt_node_tmp->value, '', $symbol->semantic_name);
          $nd->verbose = $this->verbose;
          $this->cur_node->add_node($nd);
          // make 'cur_node'
          $this->push_node($nd);
          // if ($this->verbose) echo $this->display_parser_state("after creating node for non-terminal $symbol: line " . __LINE__);
          $tmp = $this->production($symbol->value);
          if ($this->error_mode) {
            return TRUE;
          } elseif ($tmp) {
            $this->pop_node();
            if ($this->verbose) echo $this->display_parser_state("  production $symbol FOUND: line " . __LINE__);
          } else {
            // if ($this->verbose) echo $this->display_parser_state("  production '$symbol' NOT FOUND: line " . __LINE__);
            // rewind the scanner
            $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
            // pop off the node wejust checked
            $this->pop_node();
            // now cur_node is the guy we were appending nodes to, so we need to discard everything
            //   we added in the current, failed production
            $this->cur_node->discard_nodes();
            if ($this->verbose) echo $this->display_parser_state("  Production $symbol NOT FOUND: line " . __LINE__);
            return FALSE;
          }
          break;
        case ParserLangDefParser::PHP_ACTION:
          if ($this->verbose) echo $this->display_parser_state("  php action '$symbol' FOUND: line " . __LINE__);
          // $type, $syntactic_value, $semantic_value, $semantic_name
          $nd = new $this->node_class_name(ParserNode::PHP_ACTION, "%php{{$symbol->value}}", $symbol->value, $symbol->semantic_name);
          $nd->verbose = $this->verbose;
          $this->cur_node->add_node($nd);
          $defining_nd = new $this->node_class_name($defining_nonterminal->type, $defining_nonterminal->value,
              '', $defining_nonterminal->semantic_name);
          $nd->add_node($defining_nd);
          break;
        case ParserLangDefParser::STRING_ACTION:
          if ($this->verbose) echo $this->display_parser_state("  string action '$symbol' FOUND: line " . __LINE__);
          // $type, $syntactic_value, $semantic_value, $semantic_name
          $nd = new $this->node_class_name(ParserNode::STRING_ACTION, "%string{{$symbol->value}}", $symbol->value, $symbol->semantic_name);
          $nd->verbose = $this->verbose;
          $this->cur_node->add_node($nd);
          $defining_nd = new $this->node_class_name($defining_nonterminal->type, $defining_nonterminal->value,
              '', $defining_nonterminal->semantic_name);
          $nd->add_node($defining_nd);
          break;
        case ParserLangDefParser::ERROR:
          if ($token_syntactic_value != 'error') {
            if ($this->verbose) echo $this->display_parser_state("  error message NOT FOUND: line " . __LINE__);
            return FALSE;
          }
          if ($this->verbose) echo $this->display_parser_state("  error message '$symbol' FOUND: line " . __LINE__);
          // $type, $syntactic_value, $semantic_value, $semantic_name

          $error_message = "Lex Error: $token_semantic_value";
          $nd = new $this->node_class_name(ParserNode::LITERAL, $symbol->value, $error_message, $symbol->semantic_name);
          $nd->verbose = $this->verbose;
          $this->error_node = $nd;
          $this->cur_node->add_node($nd);
          $this->error_mode = TRUE;
          return TRUE;
          break;
        default:
          throw new ParserException($this->display_parser_state($this->scanner->file_name . ": line "
            . $line_no . ": Language Definition Error: Unknown symbol type: '$symbol'"));
          // $this->cur_node->discard_nodes();
          // $this->scanner->push_back($this->scanner->token_pointer - $this->production_mark_stack[$nonterminal_name][0]);
          // return FALSE;
      }
    }
    return TRUE;
  } // end of process_production()

  public function display_parser_state($msg) {
    $idx = count($this->node_stack);
    // $token = $this->scanner->token();
    // $next_token = $this->scanner->next_token();
    $str = "\nParser State: " . ($this->error_mode ? "Error Mode" : "Normal Mode") . ": $msg\n";
    $str .= "$this->scanner\n";
    if ($this->error_node) {
        $str .= "Error Node: $this->error_node\n";
    }
    // $str .= "token_pointer: {$this->scanner->token_pointer}; token: ({$token[0]}, '{$token[1]}', {$token[2]}); next_token: ({$next_token[0]}, '{$next_token[1]}')\n";
    $str .= "node stack depth: $idx\n";
    $indent = '';
    $str .= "node stack:\n";
    $action_prefix = $this->language->action_prefix;
    $action_suffix = $this->language->action_suffix;
    while ($idx-- > 0) {
      $str .= "{$indent}{$this->node_stack[$idx]}\n";
      // $str .= $this->node_stack[$idx]->dump("Node $idx");
      $indent .= '  ';
    }
    $str .= $this->display_production_mark_stack();
    return $str;
  } // end of display_parser_state()

  // display section
  public function render($tree = NULL) {
    if ($this->error_mode) {
      return $this->error_node->render($this->context, $action_prefix, $action_suffix);
    } elseif ($this->rendered_content) {
      return $this->rendered_content;
    } else {
      $action_prefix = $this->language->action_prefix;
      $action_suffix = $this->language->action_suffix;
      return ($this->rendered_content = $this->root->render( $this->context, $action_prefix, $action_suffix));
    }
  } // end of render()

  public function dump($msg = '') {
    $str = "\nParser Object Dump\n" . ($msg ? "$msg\n" : '');
    $str .= "error mode is " . ($this->error_mode ? 'TRUE' : 'FALSE') . "\n";
    $str .= "scanner: $this->scanner\n";
    $str .= $this->root->dump('Parse Tree');
    if ($this->error_node) {
      $str .= $this->error_node->dump('Error Node');
    }
    ob_start(); debug_print_backtrace(); $str .= ob_get_clean();
    return $str . "\n";
  } // end of dump()
}
