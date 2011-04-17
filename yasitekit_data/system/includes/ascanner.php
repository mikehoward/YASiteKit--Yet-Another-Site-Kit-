<?php
/*
#begin-doc
h1. ascanner.php - YAScanner: the Yet Another lexical Scanner

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 
licensed under the terms of LGPL version 3. See http://www.gnu.org/licenses/lgpl-3.0.html

YAScanner is a programmable finite state machine written in PHP for creating
lexical scanners.

To create a scanner, you must create a program for the machine, as follows:

# create a collection of state transition rules. See "YAState":#yastate for details.
# instantiate a machine for that set of state transition rules.
# debug the rules by writing a test file and processing it using _process()_ or _process_file()_
and then examining the scan using _display_chunks()_.

To use a scanner, you must:

# instantate a machine for the set of rules you want to use
# call the _process()_ or _process_file()_ method as appropriate.
# call the _next_token()_ and _push_back()_ methods, as required, to scan
and parse the string.

The classes:

* "YAScannerBase":#yascannerbase - abstract class used to construct YAScanner
and YAScanner compatable scanners
* "YAScanner":#yascanner - the machine itself.
* "YARule":#yarule - a simple class for holding finite state machine transition rules.
* "YAState":#yastate - a simple class for holding a finite state machie state. It is a container
for one or more rules plus a default rule.

h2(#yarule). YARule

h3. Instatiation

Never instatiated directly

pre. $rule = new YARule($regx, $next_state, $actions = array())

* _$regx_ is a regular expression which is applied to the string. It is used
as stated, so it's ususally necessary to place an anchor at the beginning of
the expression to avoid skipping important, leading text. You should _not_
put a trailing anchor at the end.
* _$next_state_ - string - the next state matching this rule invokes.
* _$actions_ may be an array or a comma separated list of actions. 
See the "apply_action()":#apply_action method in the description of YAScanner
for allowable actions and programming parameters.

h3. Attributes

* actions - an array of actions. See YAScanner for defined actions
* regx - a regular expression which - when matched against the supplied
string - causes a rule to be chosen
* matched_length - int - length of matched text [strlen($match_ar[0])]
* match_ar - array - array returned from _preg_match()_
* next_state - the name of the next state to go to after the rule is triggered

h3. Class Methods

h3. Instance Methods

Magic methods and _dump($msg = '')_ are defined. They do the usual things.

#end-doc
*/

class YARuleException extends Exception {}

class YARule {
  public $regx;
  public $next_state;
  public $actions;
  public $matched_length;
  public $match_ar;
  
  public function __construct($regx, $next_state, $actions = array())
  {
    $this->regx = $regx;
    $this->next_state = $next_state;
    $this->actions = is_array($actions) ? $actions : preg_split('/\s*,\s*/', $actions);
  } // end of __construct()
  
  public function __toString()
  {
    return "$this->regx -> $this->next_state (" . implode(",", $this->actions) . ")";
  } // end of __toString()

  // public function __get($name)
  // {
  //   switch ($name) {
  //     case 'actions':
  //     case 'matched_length':
  //     case 'match_ar':
  //     case 'next_state':
  //     case 'regx':
  //       return $this->$name;
  //     default;
  //       throw new YARuleException("YARule::__get($name): Illegal attribute '$name'");
  //   }
  // } // end of __get()
  // 
  // public function __set($name, $value)
  // {
  //   switch ($name) {
  //     case 'actions':
  //     case 'matched_length':
  //     case 'match_ar':
  //     case 'next_state':
  //     case 'regx':
  //      throw new YARuleException("YARule::__set($name, value): attempt to set read-only attribute '$name'");
  //     default;
  //       throw new YARuleException("YARule::__set($name, value): Illegal attribute '$name'");
  //   }
  // } // end of __set()
  // 
  // public function __isset($name)
  // {
  //   switch ($name) {
  //     case 'actions':
  //     case 'next_state':
  //     case 'regx':
  //        return TRUE;
  //     case 'matched_length':
  //     case 'match_ar':
  //       return isset($this->matched_length);
  //     default;
  //       throw new YARuleException("YARule::__isset($name): Illegal attribute '$name'");
  //   }
  // } // end of __isset()
  // 
  // public function __unset($name)
  // {
  //   switch ($name) {
  //     case 'actions':
  //     case 'matched_length':
  //     case 'match_ar':
  //     case 'next_state':
  //     case 'regx':
  //       return $this->$name;
  //       throw new YARuleException("YARule::__unset($name, value): attempt to unset read-only attribute '$name'");
  //     default;
  //       throw new YARuleException("YARule::__unset($name): Illegal attribute '$name'");
  //   }
  // } // end of __unset()
  
  public function apply(&$str, $offset) {
    if (preg_match($this->regx, substr($str, $offset), $this->match_ar)) {
      $this->matched_length = strlen($this->match_ar[0]);
// echo $this->dump("MATCH: applying rule to '" . substr($str, $offset, 10) . "'\n");
      return TRUE;
    } else {
// echo $this->dump("NO MATCH: applying rule to '" . substr($str, $offset, 10) . "'\n");
      return FALSE;
    }
  } // end of apply()

  public function dump($msg = '')
  {
    // this crud allows $msg = '  ' for indentation w/o line feeds
    $str = $msg ? (preg_match('/\s*/', $msg) ? "$msg" : "$msg\n" ): '';
    $str .= "$this";
    $str .= "  matched_length: " . (isset($this->matched_length) ? $this->matched_length:'');
    $str .= "  match_ar: '" . (isset($this->match_ar) ? implode("', '", $this->match_ar):'') . "'\n";
    return $str;
  } // end of dump()
}

/*
#begin-doc
h2(#yastate). YAState

YAState holds information for a single state.

h3. Instantiation

Never instantiated directly.

pre. $state = new YAState($state, $default_actions, array-of-rules);

See "YARule for details of array-of-rules arguments":#yarule

h3. Attributes

* state - name of this state
* default_rule - the default YARule object. Used when no _event_ is matched
in the _rules_ array.
* rules - array of YARule objects - an associative array with _events_ as keys
and YARule objects as values. Used by _apply_rule()_ to indicate actions and
next state in response to an event.

h3. Class Methods

None

h3. Instance Methods

In addition to the usual magic methods and _dump()_, there are:

* add_rule(event, next_state, actions) - the arguments are passed directly to "YARule":#yarule
and the new rule is saved in the _rules_ attribute under the _event_ key.
* apply_rule(event) - if _event_ is a key in _rules_, then the associated YARule object
is returned. Otherwise, the _default_ YARule is returned.

#end-doc
*/


class YAStateException extends Exception {}

class YAState {
  private $state;
  private $rules;
  private $default_rule;
  
  public function __construct(/* $state, $default_actions, array(event, next state, actions), ...*/)
  {
    $args = func_get_args();
    $this->state = array_shift($args);
    $default_actions = array_shift($args);
    $state_definitions = array_shift($args);
    $this->rules = array();
    foreach ($state_definitions as $row) {
      list($event, $next_state, $actions) = $row;
      $this->rules[] = new YARule($event, $next_state, $actions);
    }
    $this->rules[] = new YARule('/^(?s)./', $this->state, $default_actions);
  } // end of __construct()
  
  public function __toString()
  {
    return "YAState($this->state)";
  } // end of __toString()
  
  public function __get($name)
  {
    switch ($name) {
      case 'state':
      case 'rules':
        return $this->$name;
      default;
        throw new YAStateException("YAState::__get($name): Illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    switch ($name) {
      case 'state':
      case 'rules':
        throw new YAStateException("YAState::__set($name, value): attempt to set read-only attribute '$name'");
      default;
        throw new YAStateException("YAState::__set($name, value): Illegal attribute '$name'");
    }
  } // end of __set()
  
  public function __isset($name)
  {
    switch ($name) {
      case 'state':
      case 'rules':
        return TRUE;
      default;
        throw new YAStateException("YAState::__isset($name): Illegal attribute '$name'");
    }
  } // end of __isset()
  
  public function __unset($name)
  {
    switch ($name) {
      case 'state':
      case 'rules':
        throw new YAStateException("YAState::__unset($name, value): attempt to unset read-only attribute '$name'");
      default;
        throw new YAStateException("YAState::__unset($name): Illegal attribute '$name'");
    }
  } // end of __unset()

  public function apply_rule(&$str, $offset)
  {
    foreach ($this->rules as $rule) {
      if ($rule->apply($str, $offset)) {
        return $rule;
      }
    }
    throw new YAStateException("YAState::apply_rule(): no rule matched Rule Set error:" . $this->dump());
  } // end of apply_rule()
  
  public function dump($msg = '')
  {
    // this crud allows $msg = '  ' for indentation w/o line feeds
    $str = $msg ? (preg_match('/\s*/', $msg) ? "$msg" : "$msg\n" ): '';
    $str .= " State: $this->state\n";
    foreach ($this->rules as $rule) {
      $str .= $rule->dump('  ') . "\n";
    }
    return $str;
  } // end of dump()
}

/*
#begin-doc
h2(#yascannerbase). YAScannerBase

The *YAScannerBase* is an abstract class which defines both the YAScanner interface
and the minimum number of attributes and methods which
we expect from a scanner which emulates the YAScanner.

h3. Attributes

All attributes are read-only, except _verbose_.

* advance - boolean - flag used in state machine. used to control advancing
through the token string while changing internal states
* chr_buffer = '' - string - an internal buffer which is built as the
machine processes text.
* chunk_buffer = array() - array - a sequential array of two element arrays.
The first element is one of 'html', 'php', or 'yat', depending upon the
context of the second element.
* chunk_buffer_len - int - number of chunks in the chunk buffer
* counter - int - top of counter stack. Counters can be incremented and decremented.
Combined with the _stop_if_counter_ action, the scanner can count nested parentheticals.
Combined with _init_counter_ and _destroy_counter_, this allows the scanner to deal
with nested strings which contain nested parentheticals.
* error_count_limit - int - max number of scan errors detected prior to terminating scan.
Default is 1.
* error_tag - string - token string returned when a scan error occurs. Defaults to 'error'
* file_name - string name of file being processes
* force_new_state - boolean - internal flag used to force a state change via a rule
* initial_state - string - normal starting state when processing text.
* line_count - int - number of lines in a chunk. (may be 0 if the chunk does
not contain any line feeds)
* line_no - int - starting line number of a chunk.
* state - string - current state - used internally. ignor it
* tag - string - current tag of current token. It is actually the top of
the _tag_stack_. Pushing and popping are accomplished by '$foo->tag = value'
and 'unset($foo->tag)'
* token_pointer - int - position in the input stream of the current pointer.
* verbose = FALSE - string - a diagnostic flag. Normally you want to leave
it set to FALSE.

h3. Methods

YAScannerBase only defines three abstract methods. The other methods are
pretty generic to the parsing process and scanner interface. You probably
won't need to override them. You may need to augment the magic methods:
see the YAScanner implementation for how to do that.

h4. Defined Methods

The basic magic methods and . . .

* public function process_file($file_name); - calls _process()_ on the content of _file_name_
* public function token(); - returns current _token_ or FALSE, if none
* public function next_token(); - returns the next _token_ or FALSE, if none
* public function advance(); - advances to the next _token_
* public function push_back($amount = 1); - moves token pointer back _amount_ spaces

h4. Abstract Methods

* abstract public function __construct($initial_state, $states) - where _$initial_state_
is a string which is the name of one of the _$states_. It is the starting state
for the machine. _$states_ is an array of YAState definitions.
* abstract public function process($str, $file_name = '-') - does a complete scan of
the string _$str_ in preparation for delivering tokens. This must be implemented
by an extension. _$file_name_ is not used except in diagnostic output. It defaults
to the UNIX symbol for STDIN.
* abstract public dump($msg = '') - returns a string which displays the state
of the scanner.

The constructor 

h2(#yascanner). YAScanner

A YAScanner object implements a scanner defined by State Transition Rules.
Once the scanner is constructed, it can be used to process strings or files.
A processed file is broken into an array of tagged _chunks_ in the _chunk_buffer_.
Each _tag_ identifies the type of content and is defined by an action in the state
transition rules. NOTE: not all text in the original string or file need be in
the processed data - that is controlled by the Actions of State Transitions.

This seems all very complex - and it probably is - so the best thing to do is study
some rule sets.

h3. Instantiation

pre. $machine = new YAScanner(initial_state, states, error_tag = 'error')

where,

* initial_state is the name of the state the machine starts in when processing
text.
* states is an array of YAState definitions
* error_tag is the tag returned when a scan error is detected and reported.
See "below":#token for more details of returned tokens.

*NOTE:* this creates a reusable scanner. You must invoke the _process()_ method
to add a string. After _process()_ has been called, then the _token_ access
and _advance_ methods work.

To process another string, call _process()_ again and the scanner will reinitialize
itself for the new string.

h3. Defining States Transition Rules

Each rule consists of
* an event - which is simply a regular expression
* a next state - which is simply a string naming an existing rule
* a list of actions - which are a comma separated list of action names which are executed in order.
Actions may have one parameter (called 'param') appended by a period (.) - as in 'push_tag.php'.

Possible actions are defined in the doc for the "apply_action()":#apply_action method.

A rule is normally formed by creating an array, something like:

pre. array('A', 'bar', 'release_hold,add_char,emit')

When the machine 'sees' the letter 'A' it will invoke the rule by performing
the three actions: _release_hold_, _add_char_, and _emit_ and then transition
to state _bar_.

h3. Attributes

All the attributes of YAScannerBase plus:

* chunk_line_no - int - the starting line of each token.

h3. Class Methods

None

h3. Instance Methods

The normal magic methods plus _dump()_.

#end-doc
*/


class YAScannerException extends Exception {}

abstract class YAScannerBase {  
  protected $initial_state;
  protected $state;
  protected $states;
  protected $force_new_state = FALSE;
  protected $chr_buffer = '';
  protected $advance = TRUE;   // a latch which prevents advancing for one rule application
  protected $context_stack = array();
  protected $tag_stack = array();
  protected $counter_stack = array();
  protected $chunk_buffer = array();
  protected $verbose = FALSE;
  
  protected $raw_string;  // buffer for string
  protected $offset; // offset into string
  
  // line number info for error messages
  protected $error_tag = 'error';
  protected $file_name;
  protected $line_no = 0;
  protected $line_count;
  protected $error_count = 0;
  protected $error_count_limit = 1;
  
  // protected variables
  protected $token_pointer;
  protected $chunk_buffer_len;

  abstract public function __construct($initial_state, $states);
  
  public function __toString()
  {
    $line_no = ($tmp = $this->token()) ? $tmp[2] : 'last';
    $token = ($tmp = $this->token()) ? "{$tmp[0]} [{$tmp[2]}] ({$tmp[1]})" : '';
    $next_token = ($tmp = $this->next_token()) ? "{$tmp[0]} [{$tmp[2]}] ({$tmp[1]})" : '';
    return get_class($this) . ": state: $this->state, line: $line_no, token: {$token}, nxt: {$next_token}";
  } // end of __toString()

  public function __get($name)
  {
    switch ($name) {
      case 'chr_buffer':
      case 'chunk_buffer':
      case 'chunk_buffer_len':
      case 'error_count':
      case 'error_count_limit':
      case 'error_tag':
      case 'file_name':
      case 'force_new_state':
      case 'initial_state':
      case 'line_count':
      case 'line_no':
      case 'offset':
      case 'state':
      case 'raw_string':
      case 'token_pointer':
      case 'verbose':
        return $this->$name;
      case 'counter':
      case 'tag':
        // the reference is used here to avoid making a copy of the stack.
        $tmp = &$this->{"{$name}_stack"};
        return isset($tmp[0]) ? $tmp[0] : FALSE;  // this returns a copy of the top of the stack
      default;
        throw new YAStateException("YAScanner::__get($name): Illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    switch ($name) {
      case 'chr_buffer':
      case 'chunk_buffer':
      case 'chunk_buffer_len':
      case 'error_count':
      case 'file_name':
      case 'force_new_state':
      case 'initial_state':
      case 'line_count':
      case 'line_no':
      case 'offset':
      case 'state':
      case 'raw_string':
      case 'token_pointer':
        throw new YAScannerException("YAScanner::__set($name, value): attempt to set read-only attribute '$name'");
      case 'error_count_limit':
      case 'error_tag':
        $this->$name = $value;
        break;
      case 'verbose':
        $this->verbose = $value ? TRUE : FALSE;
        break;
      case 'counter':
      case 'tag':
        array_unshift($this->{"{$name}_stack"}, $value);
        break;
      default;
        throw new YAScannerException("YAScanner::__set($name, value): Illegal attribute '$name'");
    }
  } // end of __set()
  
  public function __isset($name)
  {
    switch ($name) {
      case 'chr_buffer':
      case 'chunk_buffer':
      case 'chunk_buffer_len':
      case 'error_count':
      case 'error_count_limit':
      case 'error_tag':
      case 'file_name':
      case 'force_new_state':
      case 'initial_state':
      case 'line_count':
      case 'line_no':
      case 'offset':
      case 'state':
      case 'raw_string':
      case 'token_pointer':
      case 'verbose':
        return isset($this->$name);
      case 'counter':
      case 'tag':
        return isset($this->{"{$name}_stack"}[0]);
      default;
        throw new YAScannerException("YAScanner::__isset($name): Illegal attribute '$name'");
    }
  } // end of __isset()
  
  public function __unset($name)
  {
    switch ($name) {
      case 'chr_buffer':
      case 'chunk_buffer':
      case 'chunk_buffer_len':
      case 'error_count':
      case 'error_count_limit':
      case 'error_tag':
      case 'file_name':
      case 'initial_state':
      case 'line_count':
      case 'line_no':
      case 'offset':
      case 'state':
      case 'raw_string':
      case 'token_pointer':
      case 'verbose':
        throw new YAScannerException("YAScanner::__unset($name, value): attempt to unset read-only attribute '$name'");
      case 'force_new_state':
        unset($this->force_new_state);
        break;
      case 'counter':
      case 'tag':
        // $var_name = "{$name}_stack";
        array_unshift($this->{"{$name}_stack"});
        break;
      default;
        throw new YAScannerException("YAScanner::__unset($name): Illegal attribute '$name'");
    }
  } // end of __unset()

  abstract public function process($str, $file_name = '-');
  
  public function process_file($file_name) {
    // read file if can be found on include path.
    $file_name = basename($file_name);
    
    $str = file_get_contents($file_name, TRUE);
    if ($str === FALSE) {
      return $this->process("$file_name not found\n", $file_name);
    } else {
      return $this->process($str, $file_name);
    }
  } // end of process_file()

  public function token() {
    return $this->token_pointer < $this->chunk_buffer_len ? $this->chunk_buffer[$this->token_pointer] : FALSE;
  } // end of token()

  public function next_token() {
    return $this->token_pointer < $this->chunk_buffer_len - 1? $this->chunk_buffer[$this->token_pointer + 1] : FALSE;
  } // end of next_token()
  
  public function advance() {
    if ($this->token_pointer < $this->chunk_buffer_len) {
      $this->token_pointer += 1;
    }
  } // end of advance()

  public function push_back($amount = 1) {
    if ($amount < 0) {
      throw new YAScannerException("YAScanner::push_back($amount): Illegal push_back amount: $amount");
    } elseif ($amount > $this->token_pointer) {
      throw new YAScannerException("YAScanner::push_back($amount): push_back amount ($amount) > current position ($this->token_pointer)");
    } elseif ($amount) {
      $this->token_pointer -= $amount;
    }
  } // end of push_back()

  abstract public function dump($msg = '');
}

class YAScanner extends YAScannerBase {
  private $chunk_line_no;
  
  public function __construct($initial_state, $states, $error_tag = 'error')
  {
    $this->initial_state =
      $this->state = $initial_state;
    $this->states = array();
    $this->error_tag = $error_tag;
    foreach ($states as $state_info) {
      $this->add_state($state_info);
    }
    // $this->verbose = TRUE;
  } // end of __construct()

  public function __get($name) {
    switch ($name) {
      case 'chunk_line_no':
        return $this->chunk_line_no;
      default:
        return parent::__get($name);
    }
  } // end of __get()

  public function __set($name, $value) {
    switch ($name) {
      case 'chunk_line_no':
        throw new YAScannerException("YAScanner::__set(): attempt to set read-only attribute '$name'");
      default:
        return parent::__set($name, $value);
    }
  } // end of __set()

  public function __isset($name) {
    switch ($name) {
      case 'chunk_line_no':
        return isset($this->name);
      default:
        return parent::__isset($name);
    }
  } // end of __isset()

  public function __unset($name) {
    switch ($name) {
      case 'chunk_line_no':
        throw new YAScannerException("YAScanner::__unset(): attempt to unset read-only attribute '$name'");
      default:
        return parent::__unset($name);
    }
  } // end of __unset()

/*
#begin-doc
h4. Public Instance Methods
#end-doc
*/


/*
#begin-doc
Processing methods: The entire input stream must be tokenized prior to scanning
by calling either _process()_ or _process_file()_.

* process($str) - processes the string _$str_ using the defined
state transition rules.
* process_file($file_name) - processes contents of named file or error message
if the file cannot be included.

p(#token). Token access methods:
Neither _token()_ nor _next_token()_ advance the token pointer, so that repeated
calls to either return the same value - unless _advance()_ or _push_back()_ are
called.

* token() - returns the current token or FALSE if there isn't one. A token is a three
element array: (tag, value, line_no) - where _tag_ is the tag associated with the data,
_value_ is the actual value identified during the scan, and _line_no_ is the starting
line number of this chunk. NOTE: neither _token()_
nor _next_token()_ advance the token pointer. The token pointer is manipulated
by _advance()_ and _push_back()_.
* next_token() - returns the next token or FALSE if there isn't one.
* advance() - moves the token pointer one position forward
* push_back($amount = 1) - pushes the current _amount_ tokens back into the input stream.
The final position never goes past the beginning of the token stream

#end-doc
*/

  public function process($str, $file_name = '-')
  {
    static $error_rule = NULL;
    
    if (!$error_rule) {
      $error_rule = new YARule('/^.*$/', $this->initial_state, "push_tag.{$this->error_tag},add_literal.Default Error Rule - probably a state definition error,emit,pop_tag");
    }
    
    // initialize
    $this->chunk_buffer = array();
    $this->state = $this->initial_state;
    $this->raw_string = &$str;
    $this->offset = 0;
    $this->advance = TRUE;
    $len = strlen($str);
    $this->file_name = $file_name;
    $this->line_no = 1;
    $this->error_count = 0;
    $this->chunk_line_no = $this->line_no;

    // process string one rule at a time
    while ($this->offset < $len) {
      $rule = $this->states[$this->state]->apply_rule($str, $this->offset);
      foreach ($rule->actions as $action) {
        if (!$this->apply_action($action, $rule)) {
          if ($this->verbose) echo "stopping action processing " . __LINE__ . "\n";
          break;
        }
      }
      // force_new_state is set by the 'new_state' action
      if ($this->force_new_state) {
        $this->state = $this->force_new_state;
        $this->force_new_state = FALSE;
      } else {
        $this->state = $rule->next_state;
      }
      
      // advance is set to FALSE by the 'no_advance' action
      // advance over the matched string to consume the recognized token(s)
      if ($this->advance) {
        $this->offset += $rule->matched_length;
        $this->line_count = substr_count($rule->match_ar[0], "\n");
        $this->line_no += $this->line_count;
      } else {
        $this->advance = TRUE;
      }
      if ($this->error_count >= $this->error_count_limit) {
        break;
      }
    }
    $this->apply_action('emit', $error_rule);
    
    $this->token_pointer = 0;
    $this->chunk_buffer_len = count($this->chunk_buffer);
  } // end of process()

  
/*
#begin-doc
h4. Private Instance Methods

* private add_state(state, default_actions, array(event, next, actions), ...) -
adds the YAState object for _state_ to the _rules_ attribute. All arguments
are passed to the YAState constructor and _add_rule()_ methods.
#end-doc
*/


  private function add_state($args)
  {
    // trap to make sure we haven't missed an enclosing array
    if (!is_array($args)) {
      var_dump($args);
      throw new YAScannerException("YAScanner::add_state(): args is not an array");
    }
    
    // add the array
    $state = array_shift($args);
    $default_actions = array_shift($args);
    $state_obj =
      $this->states[$state] = new YAState($state, $default_actions, $args);
  } // end of add_state()

/*
#begin-doc
*(#apply_action) private apply_action($action, $event) - performs the specified action. This is a _private_ method
which is never called directly. Actions may take 0, 1 additional parameters by appending
it with a dot (.) separator - as in 'push_tag.foo'.
RETURNS TRUE if to continue the action sequence, FALSE if the action sequence should terminate.
WARNING: this machine is fragile. For example, it provides nested counters, but doesn't bother
to check if any exist.
Actions:
** add_literal - appends _param_ to _chr_buffer_. Use when you need to add specific strings
to the chr_buffer, such as separators when you need to use more than one _add_matched_
** add_matched - appends the matched entry to the _chr_buffer_. May take an integer parameter
to specify the index into _$rule->match_ar. Default index is 0.
** add_matched_if_counter - appends the matched entry to the _chr_buffer_ ONLY if the
top of the counter stack > 0.
** discard_matched - essentially a nop - doesn't do anything
** no_advance - do not consume input from the string - this is used to detect the start
of an escape and then pass that start to another rule which expects the escape start
to be present.
** empty_buffer - discards matched data and sets chr_buffer to ''
** push_tag.tag - pushes the parameter _tag_ onto the tag stack
** push_tag_matched - pushes specified element of _$rule->match_ar_ onto the tag stack.
The specified element is the integer value of _param_. _param_ may be omitted if
it's value is 0 - the default.
** pop_tag - pops the top off the tag stack and discards value
** init_counter - pushes 1 onto the counter stack. Used for counting matching paren's
** inc_counter - adds 1 to the current counter
** dec_counter - subtracts 1 from current counter.
** destroy_counter - pops counter stack
** stop_if_counter - terminates processing of actions if counter > 0
If counter is now zero, then pops the counter stack and continues processing actions
** new_state.param - sets next state to _param_
** emit - if the _chr_buffer_ is not empty, then appends the tagged segment to
the _chunk_buffer_ array. The no stacks are disturbed
** emit_error - appends the chunk array('html', $param) to the _chunk_buffer_. [Note: $param
may include embedded blanks]
#end-doc
*/


  private function apply_action($action, $rule)
  {
    // @list($action, $param) = explode('.', $action);
    @list($action, $param) = preg_split('/\./', $action);
// var_dump($action);
// var_dump($param);
// if ($rule instanceof YARule) {
//   echo $rule->dump(__LINE__);
// } else {
//    echo __LINE__ . " not a rule\n";
//    var_dump($rule);
//    debug_print_backtrace();
//    exit(1);
// }
    if ($this->verbose) {
      echo $rule->dump("---action: $action: line " . __LINE__ . ": source line: {$this->line_no}, count: {$this->line_count}\n");
      echo isset($this->tag) ? "tag: {$this->tag}, " : "tag not set, ";
      echo isset($this->counter) ? "counter: {$this->counter}\n" : "counter not set\n";
      flush();
    }
    switch ($action) {
      case 'add_literal': $this->chr_buffer .= $param ; break;
      case 'add_matched':
        if (($idx = $param ? intval($param) : 0) >= count($rule->match_ar)) {
          throw new YAScannerException("YAScanner::apply_action($action): index into match array ($idx) out of bounds: "
            . count($rule->match_ar));
        }
        $this->chr_buffer .= $rule->match_ar[($param?intval($param):0)];
        break;
      case 'add_matched_if_counter':
        if ($this->counter_stack[0] > 0) {
          $this->chr_buffer .= $rule->match_ar[($param?intval($param):0)];
        }
        break;
      case 'discard_matched': break;
      case 'no_advance': $this->advance = FALSE; break;
      case 'empty_buffer': $this->chr_buffer = '';
      case 'push_tag': $this->tag = $param; break;
      case 'push_tag_matched': $this->tag = $rule->match_ar[($param?intval($param):0)]; break;
      case 'push_cur_tag': $this->tag = $this->tag; break;
      case 'pop_tag': array_shift($this->tag_stack); break;
      case 'init_counter': array_unshift($this->counter_stack, 1); break;
      case 'inc_counter': $this->counter_stack[0] += 1; break;
      case 'dec_counter': $this->counter_stack[0] -= 1; break;
      case 'destroy_counter': array_shift($this->counter_stack); break;
      case 'stop_if_counter': return $this->counter_stack[0] <= 0;
      case 'new_state': $this->force_new_state = $param; break;
      case 'emit':
// echo "state, chr_buffer, tag, chunk_line_no, line_no:\n";
// flush();
// var_dump($this->state);
// flush();
// var_dump($this->chr_buffer);
// flush();
// var_dump($this->tag);
// flush();
// var_dump($this->chunk_line_no);
// flush();
// var_dump($this->line_no);
// flush();
        if ($this->chr_buffer) {
          $this->chunk_buffer[] = array($this->tag, $this->chr_buffer, $this->chunk_line_no);
          $this->chr_buffer = '';
          $this->chunk_line_no = $this->line_no;
        }
        break;
      case 'emit_error':
        $this->chunk_buffer[] = array($this->error_tag,
          "file $this->file_name:near line $this->line_no: " . trim($param) 
              . ": '" . substr($this->raw_string, $this->offset, 40) . "'",
          $this->chunk_line_no);
        $this->chunk_line_no = $this->line_no;
        $this->error_count += 1;
        break;
      case '':
        $action = "(empty action)";
        // intentional fall through
      default:
        throw new YAScannerException("YAScanner::apply_action($action, $rule): Illegal Action '$action'");
    }
    return TRUE;
    // if ($this->verbose) echo "$this->state: apply_action($action, $rule)\n";
  } // end of apply_action()


  public function dump($msg = '')
  {
    $str = $msg ? "$msg\n" : '';
    
    $str .= "States:\n";
    foreach ($this->states as $state_info) {
      $str .= $state_info->dump();
    }

    $saved_token_pointer = $this->token_pointer;
    $this->token_pointer = 0;
    $idx = 0;
    while (($row = $this->token()) !== FALSE) {
      list($tag, $body, $line_no) = $row;
      $str .= "$idx: line_no: $line_no: $tag: '" . preg_replace("/\n/", '(n)', $body) . "'\n";
      $idx += 1;
      $this->advance();
    }
    $this->token_pointer = $saved_token_pointer;
    return $str;
  } // end of dump()

}
