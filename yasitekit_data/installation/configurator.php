<?php
/*
#doc-start
h1. configurator.php - A Configuration GUI

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved.
Licensed under the terms of the GNU Lesser Public License, Version 3
"see":http://www.gnu.org/licenses/lgpl.html

The *configurator* provides a web based interfact to YASiteKit configuration file (config.php)
maintenance. It does this by parsing a config.php file or a config-template.php file and
interpreting specially formatted comments.

The parsing action operates in two modes:

* text - which is copied straight through and reproduced verbatum
* var - which interpreted as variable definition source.

Switching between modes is controlled by the configurable start and stop comments:

* configurable-start - causes the parser to enter _var_ mode on the _next_ line of text.
This comment is processed in _text_ mode, so that it is copied to the output file.
* configurable-end - causes the parser to enter _text_ mode. This line is also
processed in _text_ mode.

Within _text_ mode, text is accumulated, line by line.

Within _var_ mode, each line is matched against a set of regular expressions
and parsed accordingly.

h2. _text_ mode parsing

There isn't any parsing as such. We just copy the text through verbatum.

h2. _var_ mode parsing

In _var_ mode, one or more 'comment directives' preceed a PHP variable declaration.
'comment directives' contain information which applies to the immediately succeeding
_variable declaration_. They are accumulated until the _variable declaration_ is
found, at which point they are applied to the _variable declaration_ and all
'comment directives' are cleared.

For example:

pre. // comment: this is a comment which the user will see
// type string
public static $foo = 'initial value';  // this is an optional comment which is passed through to output

_var_ mode comment lines:

* // type &lt;string | int | bool | array | select&gt; - optional (more or less) - defines the data type of the variable
* // required - marks variable as _required_. This sets the
color coding of the form generates error messages if missing
* // recommended - marks variable as _recommended_. This sets the color coding of the form
* // random(length) - tags the variable to be filled with a string of randomly generated characters
of length _length_. The is only used to generate encryption keys. It might be useful for
something else, but that hasn't come up yet.
* // readonly - tags the variable as appearing in the form but not modifiable by the form.
At present this is only used for _site_installation_, but may have other uses. These
variables must be set programatically.
* // default - sets the default value for the variable. The default value supports {foo} type
substitution, so {foo}_bar will expand to the value of _foo_ followed by the literal string '_bar'.
* // annotation - followed by text which is displayed in the form. Contiguous _annotation_
lines are concatenated (with a joining space character). _annotation_ lines may not be
continued to the next line. [That's the reason for the contiguous combination rule]
* // option _choice_ - only used for _select_ types. Specifies one option available for
the variable.
* // comment - optional - used to provide human readable text in the form
* // array-decl - semi-optional - used to define array components. *array-decl* lines follow
a strict format: ' // array-decl key value type comment
** key - is the name of the key field in the array
** value - intitial value of the array entry. May be an empty string ('') or zero, but must not
be missing
** type is one of 'string', 'int', or 'bool'
** comment - rest of text - used to give user a hint

The *configurator* attempts to deduce the type of a variable definition from the _variable declaration_
line. This works if the following rules are followed:

* all variable declarations must begin with 'public static ' and must be separated from each
other and the variable name by at least one white-space character.
* string data must be quoted strings using single or double quote marks (') or (")
* integer data must consist of one or more consecutive digits. For example, at least a
zero (0)
* bool data must be initialized to one of the literals: TRUE of FALSE
* NULL initizializers must have a type word in the trailing comment text. The type word
must be one of 'string', 'int', or 'bool'.

An explicit 'type comment directive' takes precedence over inferred data types.

If a variable is initialized to NULL and declared as an _array_ type, it _must_ be preceeded
by one or more *array-decl* comment lines which define the fields in the array.

#doc-end
*/


// Classes
class Slug {
  const NO_VAR_OK = TRUE;
  const NO_VAR_NOT_OK = FALSE;
  private static $var_regx = array(
    // public static $var_name = array(
    array('array-start', '/^\s*public\s+static\s+\$(\w+)\s*=\s*array\(\s*(.*)$/'),
    //   ),
    array('array-end', '/^\s*\);\s*$/'),
    //  "key" => 'value string', // <string | int | bool> comment
    array('array-component-string',
        '/^\s*"(\w+)"\s+=>\s+([\'"])([^\'"]*)\2\s*,\s*\/\/\s*(string|int|bool)\s+(.*)$/'),
    //  "key" => int-value, // <string | int | bool> comment
    array('array-component-int', '/^\s*"(\w+)"\s+=>\s(\d+)\s*,\s*\/\/\s*(string|int|bool)\s+(.*)$/'),
    //  "key" => TRUE | FALSE // <string | int | bool> comment
    array('array-component-bool', '/^\s*"(\w+)"\s+=>\s+(TRUE|FALSE)\s*,\s*\/\/\s*(string|int|bool)\s+(.*)$/'),
    //  "key" => NULL // <string | int | bool> comment
    array('array-component-null', '/^\s*"(\w+)"\s+=>\s+NULL\s*,\s*\/\/\s*(string|int|bool)\s+(.*)$/'),
    // public static $var_name = 'string value' // comment
    array('var-string', '/^\s*public\s+static\s+\$(\w+)\s*=\s*([\'"])([^\'"]*)\2\s*;\s*(.*)\s*$/'),
    // public static $var_name = int-value // comment
    array('var-int', '/^\s*public\s+static\s+\$(\w+)\s*=\s*(\d+)\s*;\s*(.*)\s*$/'),
    // public static $var_name = TRUE | FALSE // comment
    array('var-bool', '/^\s*public\s+static\s+\$(\w+)\s*=\s*(TRUE|FALSE)\s*;\s*(.*)\s*$/'),
    // public static $var_name = NULL // comment
    array('var-null', '/^\s*public\s+static\s+\$(\w+)\s*=\s*NULL\s*;\s*\/\/\s*(string|int|bool|array)?\s*(.*)\s*$/'),
    // comment some text meaning something
    array('comment', '/^\s*\/\/\s+comment\s*(.*)\s*$/'),
    // type <string | int | bool | array>
    array('type', '/^\s*\/\/\s*type\s*(string|int|bool|array|select)\s*$/'),
    // random
    array('random', '/^\s*\/\/\s*random\s*(\d+)\s*$/'),
    // readonly
    array('readonly', '/^\s*\/\/\s*readonly\s*$/'),
    // required
    array('required', '/^\s*\/\/\s*required\s*$/'),
    // recommended
    array('recommended', '/^\s*\/\/\s*recommended\s*$/'),
    // default <string> with {foo} substitutions
    array('default', '/^\s*\/\/\s*default\s+(.*)\s*$/'),
    // array-decl field_name 'string' string inline comment
    array('option', '/^\s*\/\/\s*option\s+(.*)\s*$/'),
    array('array-decl', '/^\s*\/\/\s*array-decl\s+(\w+)\s+\'([^\']*)\'\s+(string)\s+(.*)\s*$/'),
    // array-decl field_name \d+ int inline comment
    array('array-decl', '/^\s*\/\/\s*array-decl\s+(\w+)\s+(\d+)\s+(int)\s+(.*)\s*$/'),
    // array-decl field_name TRUE|FALSE bool inline comment
    array('array-decl', '/^\s*\/\/\s*array-decl\s+(\w+)\s+(TRUE|FALSE)\s+(bool)\s+(.*)\s*$/'),
    // array-decl field_name NULL string|int|bool inline comment
    array('array-decl', '/^\s*\/\/\s*array-decl\s+(\w+)\s+(NULL)\s+(string|int|bool)\s+(.*)\s*$/'),
    // array-decl field_name NULL select comma-separated-list inline-comment
    array('array-decl', '/^\s*\/\/\s*array-decl\s+(\w+)\s+(NULL)\s+(select)\s+(\w+(,\w+))\s+(.*)\s*$/'),
    // annotation - used to block out the config parameters
    array('annotation', '/^\s*\/\/\s+annotation\s*(.*)\s*$/'),
    // a catch-all
    array('syntax-error', '/.*/'),
    );
  public function __construct($slug_type, $line_no, $line = NULL)
  {
    $this->slug_type = $slug_type;
    $this->line_no = $line_no;
    switch ($this->slug_type) {
      case 'text':
        $this->text_ar = array();
        if ($line) {
          $this->text_ar[] = $line;
        }
        break;
      case 'var':
        $this->annotation = '';
        $this->array_decl = array();
        $this->array_decl_ar = array();
        $this->array_decl_by_key = array();
        $this->comment = '';
        $this->default = NULL;
        $this->if_null_random_value = FALSE;
        $this->importance = NULL;
        $this->inline_comment = '';
        $this->options = NULL;
        $this->readonly = FALSE;
        $this->var_finished = FALSE;
        $this->var_name = '';
        $this->var_type = 'undefined';
        $this->var_value = NULL;
        break;
    }
  } // end of __construct()  

  public function __toString()
  {
    return $this->slug_type == 'text' ? "text: {$this->line_no}" : "var: {$this->var_name}";
  } // end of __toString()

  public function parse($line)
  {
    if ($this->slug_type == 'text') {
      $this->text_ar[] = $line;
      return;
    }
    
// echo "line: "; var_dump($line);
    foreach (Slug::$var_regx as $tmp) {
      list($line_type, $regx) = $tmp;
// echo "line_type: "; var_dump($line_type);
      if (preg_match($regx, $line, $match_obj)) {
// echo "match_obj: "; var_dump($match_obj);
        switch ($line_type) {
          case 'array-start':
            $this->var_name = $match_obj[1];
            $this->var_type = 'array';
            $this->inline_comment = $match_obj[2];
            $this->var_value = array();
            return FALSE;
            break;
          case 'array-end':
            $this->var_finished = TRUE;
            if (!isset($this->comment)) {
              $this->comment = isset($this->inline_comment)
                ? preg_replace('/^\s*\/\/\s*', '', $this->inline_comment) : '';
            }
            return TRUE;
          case 'array-component-string':
            $this->var_value[] = array($match_obj[1], $match_obj[3], $match_obj[4], $match_obj[5]);
            break;
          case 'array-component-select':
            
            break;
          case 'array-component-int':
          case 'array-component-bool':
          case 'array-component-null':
// var_dump($match_obj);
            $this->var_value[] = array_slice($match_obj, 1);
            break;
          case 'var-string':
            $this->var_name = $match_obj[1];
            $this->var_value = $match_obj[3];
            if ($this->var_type == 'undefined') {
              $this->var_type = 'string';
            }
            $this->inline_comment = $match_obj[4];
            if (!isset($this->comment)) {
              $this->comment = preg_replace('/^\s*\/\/\s*/', '', $this->inline_comment);
            }
            $this->var_finished = TRUE;
            return TRUE;
          case 'var-int':
            $this->var_name = $match_obj[1];
            $this->var_value = $match_obj[2];
            if ($this->var_type == 'undefined') {
              $this->var_type = 'int';
            }
            $this->inline_comment = $match_obj[3];
            if (!isset($this->comment)) {
              $this->comment = preg_replace('/^\s*\/\/\s*/', '', $this->inline_comment);
            }
            $this->var_finished = TRUE;
            return TRUE;
          case 'var-bool':
            $this->var_name = $match_obj[1];
            $this->var_value = $match_obj[2];
            if ($this->var_type == 'undefined') {
              $this->var_type = 'bool';
            }
            $this->inline_comment = $match_obj[3];
            if (!isset($this->comment)) {
              $this->comment = preg_replace('/^\s*\/\/\s*/', '', $this->inline_comment);
            }
            $this->var_finished = TRUE;
            return TRUE;
          case 'var-null':
            $this->var_name = $match_obj[1];
            $this->var_value = NULL;
            // save down comment and put type and comment marker back
            $this->inline_comment = "// {$this->var_value} " . $match_obj[3];
            if (!isset($this->comment)) {
              $this->comment = preg_replace('/^\s*\/\/\s*/', '', $this->inline_comment);
            }
            if ($this->var_type == 'undefined') {
              $this->var_type = $match_obj[2];
            }
            if ($this->var_type == 'undefined') {
              return "Syntax Error: No Variable Type for $this->var_name";
            }
            if ($this->var_type == 'array') {
              $this->var_value = $this->array_decl;
            }
            $this->var_finished = TRUE;
            return TRUE;
            break;
          case 'comment':
            $this->comment .= $match_obj[1];
            break;
          case 'type':
            $this->var_type = $match_obj[1];
            break;
          case 'random':
            $this->if_null_random_value = TRUE;
            $this->string_len = intval($match_obj[1]);
            break;
          case 'base64':
            $this->base64 = TRUE;
            break;
          case 'required':
            $this->importance = 'required';
            break;
          case 'recommended':
            $this->importance = 'recommended';
            break;
          case 'readonly':
            $this->readonly = TRUE;
            break;
          case 'default':
            $this->default = $match_obj[1];
            // $this->dump('found default: ' . $match_obj[0]);
            break;
          case 'option':
            if (!$this->options)
              $this->options = array();
            $this->options[] = $match_obj[1];
            break;
          case 'array-decl':
            if (!in_array($match_obj[0], $this->array_decl_ar)) {
              $this->array_decl_ar[] = $match_obj[0];
              $this->array_decl[] = array_slice($match_obj, 1);
              $this->array_decl_by_key[$match_obj[1]] = array_slice($match_obj, 1);
            }
            break;
          case 'annotation':
            $this->annotation .= trim($match_obj[1]) . "\n";
            break;
          case 'syntax-error':
            return "Syntax Error: line: $line\n";
          default:
            return "Internal Error: Regx Exp $line_type not handled\n";
        }
        break;
      }
    }
  } // end of parse()
  
  public function close($no_var_ok)
  {
    if ($this->slug_type == 'text') {
      if (isset($this->text_ar)) {
        $this->text = implode("\n", $this->text_ar) . "\n";
        unset($this->text_ar);
      }
      return $no_var_ok || $this->text ? TRUE : FALSE;
    } else {
      return $no_var_ok || isset($this->var_finished) ? TRUE
        : "Syntax Error: variable definition Incomplete\n";
    }
  } // end of close()
  
  public function as_text()
  {
    if ($this->slug_type == 'text') {
      $this->close(Slug::NO_VAR_OK);
      return $this->text;
    }
    
    $str = '';
    if ($this->annotation) {
      foreach (preg_split('/\n/', $this->annotation) as $tmp) {
        if ($tmp)
          $str .= "  // annotation $tmp\n";
      }
    }
    $str .= implode("\n", $this->array_decl_ar) . "\n";
    if ($this->comment) {
      $str  .= "  // comment {$this->comment}\n";
    }
    $str .= "  // type {$this->var_type}\n";
    if ($this->importance) {
      $str .= "  // {$this->importance}\n";
    }
    if ($this->default) {
      $str .= "  // default {$this->default}\n";
    }
    if ($this->readonly) {
      $str .= "  // readonly\n";
    }
    if ($this->options){
      foreach ($this->options as $option) {
        $str .= "  // option $option\n";
      }
    }
    if ($this->if_null_random_value) {
      $str .= "    // random $this->string_len\n";
    }
    $inline_comment = isset($this->inline_comment) ? " {$this->inline_comment}" : '';
    $inline_comment = preg_replace('/\/\/ (\/\/ )*/', '// ', $inline_comment);
    switch ($this->var_type) {
      case 'select':
      case 'string':
        $var_value = preg_replace(array('/&lt;/', '/&gt;/'), array('<', '>'), $this->var_value);
        $str .= "  public static \${$this->var_name} = '{$var_value}';{$inline_comment}\n";
        break;
      case 'int':
        $str .= "  public static \${$this->var_name} = " . intval($this->var_value)
          . ";{$inline_comment}\n";
        break;
      case 'bool':
        $str .= "  public static \${$this->var_name} = " . ($this->var_value ? 'TRUE' : 'FALSE')
          . ";{$inline_comment}\n";
        break;
      case 'array':
        $str .= "  public static \${$this->var_name} = array({$inline_comment}\n";
        $tmp_ar = array_combine(array_map(create_function('$a','return $a[0];'), $this->var_value),
          $this->var_value);
        // add in any missing values
        foreach ($this->array_decl as $row) {
          $key = $row[0];
          list($key, $value, $var_type, $cmnt) = array_key_exists($key, $tmp_ar) ? $tmp_ar[$key] : $row;
          switch ($var_type) {
            case 'string':
              $value = preg_replace(array('/&lt;/', '/&gt;/'), array('<', '>'), $value);
              $str .= "      \"{$key}\" => '{$value}', // {$var_type} {$cmnt}\n";
              break;
            case 'int':
              $str .= "      \"{$key}\" => " . intval($value) . ", // {$var_type} {$cmnt}\n";
              break;
            case 'bool':
              $str .= "      \"{$key}\" => " . ($value?'TRUE':'FALSE') . ", // {$var_type} {$cmnt}\n";
              break;
            default:
              ob_start(); var_dump($row); $tmp_str = ob_get_clean();
              echo "Internal Error: $tmp_str\n";
          }
        }
        $str .= "     );\n";
        break;
      default:
        break;
    }
    return $str;
  } // end of as_text()
  
  public function assign_array_value($array_key, $new_value)
  {
    if ($this->slug_type != 'var' || $this->var_type != 'array') {
      throw new Exception("Slug::assign_array_value($array_key, ): This Slug is not an array:"
        . $this->dump());
    }
    $len = count($this->var_value);
    for ($idx=0;$idx<$len;$idx += 1) {
      if ($this->var_value[$idx][0] == $array_key) {
        $this->var_value[$idx][1] = $new_value;
        return TRUE;
      }
    }
    throw new Exception("Slug::assign_array_value($array_key, ): Array Key Not Found: " . $this->dump());
  } // end of assign_array_value()
  
  public function dump($msg = '')
  {
    $str = "<div style=\"white-space:pre\" class=\"box\">\n";
    $str .= $msg ? "$msg\n$this->slug_type: " : "$this->slug_type: ";
  	foreach (get_object_vars($this) as $key => $value) {
      if (is_array($value)) {
        ob_start();
        print_r($value);
        $str .= "  this->{$key} => " . ob_get_clean() . "\n";
      } elseif (is_bool($value)) {
        $str .= "  this->{$key} => " . ($value?'TRUE':'FALSE') . "\n";
      } else {
        $str .= "  this->{$key} => $value\n";
      }
  	}
    switch ($this->slug_type) {
      case 'text':
        $this->close(Slug::NO_VAR_OK);
        $str .= $this->text . "\n";
        break;
      case 'var':
        $this->close(Slug::NO_VAR_NOT_OK);
        // if (!isset($this->var_name)) {
        //   ob_start(); var_dump($this); echo ob_get_clean();
        // }
        switch ($this->var_type) {
          case 'string':
            $str .= "{$this->var_name}($this->var_type) = '$this->var_value'\n";
            break;
          case 'int':
          case 'bool':
            $str .= "$this->var_name($this->var_type) = $this->var_value\n";
            break;
          case 'array':
            $str .= " $this->var_name($this->var_type) = array(\n";
            foreach ($this->var_value as $row) {
              list($key, $val, $var_type, $cmnt) = $row;
              switch ($var_type) {
                case 'string':
                  $str .=  "  $key($var_type) => '$val' // $cmnt\n";
                  break;
                case 'int':
                case 'bool':
                  $str .=  "  $key($var_type) => $val // $cmnt\n";
                  break;
                default:
                  $str .= "   $key($var_type:Illegal Type) => $val // $cmnt\n";
                  break;
              }
            }
            $str .= ")\n";
            break;
          default:
            $str .= "unknown variable type: $this->var_type\n";
            break;
        }
        break;
      default:
        $str .= "Illegal Slug Type: '$this->slug_type' " . __LINE__ . "\n";
        break;
    }
    return $str . "\n</div>\n";
  } // end of dump()
}

class Configurator {
  private $var_index = array();
  private $list = array();
  public $error_messages = '';
  public function __construct($template_path)
  {
    $this->template_path = $template_path;
    $this->template = file_get_contents($template_path);
    $this->burst_template();
  } // end of __construct()
  
  public function __get($name)
  {
    if (in_array($name, array_keys($this->var_index))) {
      return $this->var_index[$name]->var_value;
    }
    throw new Exception("Configurator::__get($name): Undefined Variable '$name'");
  } // end of __get()
  
  public function var_names()
  {
    return array_keys($this->var_index);
  } // end of var_names()
  
  // update adds slugs from $other to $this - updating a configuration template
  // as new stuff is added (hopefully). NOTE: This is fragile
  public function update($other)
  {
    $deleted_vars = array_diff($this->var_names(), $other->var_names());
    foreach ($deleted_vars as $var_name) {
      unset($this->list[$this->index_of_var($var_name)]);
    }
    
    $added_vars = array_diff($other->var_names(), $this->var_names());
    foreach ($added_vars as $var_name) {
      $idx = $other->index_of_var($var_name);
      $new_slug = $other->slug_by_idx($idx);
      $preceeds = $other->slug_by_idx($idx - 1);

      switch ($preceeds->slug_type) {
        case 'text':
          $follows = $other->slug_by_idx($idx + 1);
          if ($follows === FALSE) {
            $this->list[] = $new_slug;
            break;
          } else {
            $insert_after_idx = $this->index_of_var($follows->var_name) - 1;
          }
          break;
        case 'var':
          $insert_after_idx = $this->index_of_var($preceeds->var_name);
          break;
        default:
          throw new Exception("Illegal Slug Type: " . $preceeds->dump());
      }
      // the + 1 is because splice is kind of wierd. This is the index to inser the stuff
      //  after ripping out 0 elements. Since I'm not ripping out, I have to increment by 1
      array_splice($this->list, $insert_after_idx + 1, 0, array($new_slug));
    }
  } // end of update()
  
  public function slug_by_idx($idx)
  {
    return $idx >= 0 && $idx < count($this->list) ? $this->list[$idx] : FALSE;
  } // end of slug_by_idx()

  public function index_of_var($var_name)
  {
    reset($this->list);
    $len = count($this->list);
    for ($idx=0;$idx<$len;$idx++) {
      $slug = $this->list[$idx];
      if ($slug->slug_type == 'var' && $slug->var_name == $var_name) {
        return $idx;
      }
    }
    return FALSE;
  } // end of index_of_var()
  
  public function slug_by_var_name($var_name)
  {
    $idx = $this->index_of_var($var_name);
    return $idx !== FALSE ? $this->slug_by_idx($idx) : FALSE;
  } // end of slug_by_var_name()

  private function burst_template()
  {
    $template_as_lines = preg_split('/(\r\n|\n|\r|\n\r)+/', $this->template);
    $this->list = array();
    $collect_vars = FALSE;
    $line_no = 0;
    $cur_slug =
      $this->list[] = new Slug('text', $line_no);
    foreach ($template_as_lines as $line) {
      $line_no += 1;
      if ($collect_vars) {
        if (preg_match('/\/\/\s+configurable-end/', $line)) {
          $cur_slug->close(Slug::NO_VAR_NOT_OK);
          if (!isset($cur_slug->var_name) || !$cur_slug->var_name) {
            array_pop($this->list);
          }
          $collect_vars = FALSE;
          $cur_slug =
            $this->list[] = new Slug('text', $line_no, $line);
        } else {
          switch (($err_msg = $cur_slug->parse($line))) {
            case FALSE:
              break;
            case TRUE:
              if (($err_msg = $cur_slug->close(Slug::NO_VAR_NOT_OK)) !== TRUE) {
                echo "Syntax Error in Line: $line_no: $err_msg\n";
              }
              if (isset($cur_slug->var_name)) {
                $this->var_index[$cur_slug->var_name] = $cur_slug;
              }
              $cur_slug =
                $this->list[] = new Slug('var', $line_no);
              break;
            default:
              echo "Syntax Error in Line: $line_no: $err_msg\n";
              $cur_slug =
                $this->list[] = new Slug('var', $line_no);
              break;
          }
        }
      } else {
        $cur_slug->parse($line);
        if (preg_match('/\/\/\s+configurable-start/', $line)) {
          $collect_vars = TRUE;
          $cur_slug->close(Slug::NO_VAR_OK);
          if (isset($cur_slug->var_name) && $cur_slug->var_finished) {
            $this->var_index[$cur_slug->var_name] = $cur_slug;
          }
          $cur_slug =
            $this->list[] = new Slug('var', $line_no);
        }
      }
    }
  } // end of burst_template()
  
  public function form_helper($slug, $var_name, $var_value, $var_type, $options = NULL)
  {
    switch ($var_type) {
      case 'string':
        return $slug->readonly ? "    <span class=\"float-right bold\">$var_value</span>"
          : "    <input class=\"float-right\" type=\"text\" name=\"$var_name\" value=\"$var_value\" maxlength=\"255\" size=\"80\">\n";
      case 'int':
        return $slug->readonly ? "    <span class=\"float-right bold\">$var_value</span>"
          :"    <input class=\"float-right\" type=\"text\" name=\"$var_name\" value=\"$var_value\" maxlength=\"255\" size=\"10\">\n";
      case 'bool':
        $true_checked = $var_value == 'TRUE' ? ' checked' : '';
        $false_checked = $true_checked ? '' : ' checked';
        return $slug->readonly ? "    <span class=\"float-right bold\">" . ($var_value?'TRUE':'FALSE') . "</span>"
          :"   <span class=\"float-right\">TRUE <input type=\"radio\" name=\"$var_name\" value=\"TRUE\" $true_checked> | "
          . "FALSE <input type=\"radio\" name=\"$var_name\" value=\"FALSE\" $false_checked></span>\n";
      case 'select':
        if ($slug->readonly) {
          return "    <span class=\"float-right bold\">$var_value</span>";
        } else {
          $str = "   <select class=\"float-right\" name=\"$var_name\">\n";
          foreach ($options as $option) {
            $selected = $var_value && $var_value == $option ? 'selected':'';
            $str .= "    <option value=\"$option\" $selected>$option</option>\n";
          }
          $str .= "</select>\n";
        }
        return $str;
      default:
        throw new Exception("Configurator::form_helper(slug, $var_name, $var_value):illegal variable type");
    }
  } // end of form_helper()

  public function form($action)
  {
    $str = "<h2>Currently Editing the <span class=\"box\">{$this->site_installation}</span> config.php file</h2>\n";
    $str .= "<form class=\"box\" action=\"$action\" method=\"post\" accept-charset=\"utf-8\">\n";
    $str .= "To Edit a different Configuration File: click the button:\n";
    foreach (array('development', 'alpha', 'production') as $site_installation) {
      if ($site_installation != $this->site_installation) {
        $str .= "<input type=\"submit\" name=\"_edit_site_installation\" value=\"{$site_installation}\">\n";
      }
    }
    $str .= "</form>\n";

    $str .= "<form action=\"$action\" method=\"post\" accept-charset=\"utf-8\">\n";
    $str .= "<input type=\"hidden\" name=\"_edit_site_installation\" value=\"$this->site_installation\">\n";
    $str .= " <ul>\n";
    $str .= "<li class=\"notice boxed\">Source File: $this->template_path</li>\n";
    $str .= "<li class=\"action boxed\">To Incorporate Changes, Click <input type=\"submit\" name=\"submit\" value=\"Update\">.</li>\n";
    // strip off the template option - which is always on top - so we will never clobber it on a save
    $strip_class = 'odd';
    foreach ($this->list as $slug) {
      if ($slug->slug_type == 'var') {
        if ($slug->annotation) {
          $str .= "<li class=\"annotation clear boxed\">$slug->annotation</li>\n";
        }
        $strip_class = $strip_class == 'odd' ? 'even' : 'odd';
        $class = $slug->importance ? $slug->importance : $strip_class;
        $str .= "<li class=\"$class clear boxed\">\n";
        $str .= "<p class=\"bold smaller\">";
        if ($slug->comment) {
          $str .= "{$slug->comment}";
        }
        if ($slug->inline_comment) {
          $str .= " {$slug->inline_comment}";
        }
        if ($slug->default) {
          $str .= " <span class=\"italic\">Default value: {$slug->default}</span>";
        }
        $str .= "</p>\n";
        
        switch ($slug->var_type) {
          case 'string':
          case 'int':
          case 'bool':
            $str .= $this->form_helper($slug, $slug->var_name, $slug->var_value, $slug->var_type);
            $str .= "    <label for=\"$slug->var_name\">$slug->var_name ($slug->var_type): </label>\n";
            break;
          case 'array':
            $str .= "    <label for=\"$slug->var_name\">$slug->var_name ($slug->var_type): </label>\n";
            $str .= "    <input type=\"hidden\" name=\"$slug->var_name\" value=\"$slug->var_type\">\n";
            $str .= "<ul>\n";
            $tmp_ar = array_combine(array_map(create_function('$a','return $a[0];'), $slug->var_value),
              $slug->var_value);
            foreach ($slug->array_decl as $row) {
              $key = $row[0];
              list($v_name, $v_val, $v_type, $cmt) = array_key_exists($key, $tmp_ar)
                  ? $tmp_ar[$key] : $row;
              $str .= "   <li class=\"clear\">\n";
              $form_name = "{$slug->var_name}-{$v_name}";
              $str .= $this->form_helper($slug, $form_name, $v_val, $v_type);
              $str .=  "    <label for=\"$form_name\">$v_name ($v_type): <span class=\"smaller\">$cmt</span></label>\n";
              $str .= "   </li>\n";
            }
            $str .= "</ul>\n";
            break;
          case 'select':
            $str .= $this->form_helper($slug, $slug->var_name, $slug->var_value, $slug->var_type, $slug->options);
            $str .= "    <label for=\"$slug->var_name\">$slug->var_name ($slug->var_type): </label>\n";
            break;
          default:
            echo $slug->dump("Illegal var_type: " . __LINE__);
            throw new Exception("Configurator::form(): Illegal var_type $slug->var_type");
        }
        $str .= "  </li>\n";
      }
    }
    $str .= "<li class=\"action boxed\">To Incorporate Changes, Click <input type=\"submit\" name=\"submit\" value=\"Update\">.</li>\n";
    $str .= " </ul>\n";
    $str .= "</form>\n";

    return $str;
  } // end of form()
  
  private function fix_db_params()
  {
    $db_type_slug = $this->slug_by_var_name('db_type');
    $db_params_idx = $this->index_of_var('db_params');

    $this->list[$db_params_idx]->assign_array_value('db_engine', $db_type_slug->var_value);

    switch ($db_type_slug->var_value) {
      case 'sqlite':
      case 'sqlite3':
        $private_data_root_slug = $this->slug_by_var_name('private_data_root');
        $site_id_slug = $this->slug_by_var_name('site_id');
        $dbname = $private_data_root_slug->var_value . DIRECTORY_SEPARATOR . 'sqlite_db.d'
          . DIRECTORY_SEPARATOR . $site_id_slug->var_value;
        $this->list[$db_params_idx]->assign_array_value('dbname', $dbname);
        break;
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      case 'mongodb':
        // $this->list[$db_params_idx]->var_value
        break;
      default:
        throw new Exception($db_type_slug->dump("Configurator::fix_db_params(): Internal Error: Illegal db_type:"));
    }
  } // end of fix_db_params()

  public function parse()
  {
    $this->needs_save = FALSE;
    $cleaned = array();
    foreach ($_POST as $key => $val) {
      $cleaned[$key] = preg_replace(array('/</', '/>/'), array('&lt;', '&gt;'), $val);
    }
    foreach ($cleaned as $key => $val) {
      if (!isset($this->var_index[$key])) {
        continue;
      }
      $slug = $this->var_index[$key];
      if ($slug->readonly) {
        continue;
      }
      switch ($slug->var_type) {
        case 'string':
        case 'int':
        case 'bool':
        case 'select':
          if ($val != $slug->var_value) {
            $slug->var_value = $val;
            $this->needs_save = TRUE;
          }
          break;
        case 'array':
          $new_var_value = array();
          foreach ($slug->var_value as $row) {
            list($ar_key, $ar_val, $ar_type, $cmt) = $row;
            $form_name = "{$slug->var_name}-{$ar_key}";
            if ($cleaned[$form_name] != $ar_val) {
              $this->needs_save = TRUE;
              $new_var_value[] = array($ar_key, $cleaned[$form_name], $ar_type, $cmt);
            } else {
              $new_var_value[] = $row;
            }
          }
          $slug->var_value = $new_var_value;
          break;
        default:
          throw new Exception("Configurator::parse(): bad type for variable '$slug->var_name");
      }
    }
    
    // pass 2 - catch NULL's and put in default values, if they exist
    $token_ar = array_map(create_function('$a', 'return "/{{$a}}/";'), array_keys($this->var_index));
    $subs_ar = array_map(create_function('$a', 'return (!$a->var_value || is_string($a->var_value)?$a->var_value:NULL);'), array_values($this->var_index));
    foreach ($this->var_index as $slug) {
      if (!$slug->var_value && $slug->default) {
        if (is_array($slug->default)) {
          echo "default is an array:\n";
          var_dump($slug->default);
        } elseif (is_string($slug->default)) {
          $slug->var_value = preg_replace($token_ar, $subs_ar, $slug->default);
        } else {
          throw new Exception("Illegal Default Value: " . $slug->dump);
        }
      }
      if (!$slug->var_value) {
        if ($slug->if_null_random_value) {
          $char_tmp = '!#$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz{}';
          $len = strlen($char_tmp) - 1;
          $slug->var_value = '';
          for ($i=0;$i<$slug->string_len;$i++) {
            $slug->var_value .= $char_tmp[rand(0, $len)];
          }
          $slug->var_value = urlencode($slug->var_value);
        }
        
        if ($slug->readonly) {
          $this->error_messages .= "<p class=\"warning\">Warning: readonly variable '$slug->var_name' is Not Set</p>\n";
        }
        if ($slug->importance == 'required') {
          $this->error_messages .= "<p class=\"error\">Error: Required variable '$slug->var_name' is Not Set</p>\n";
        }
      }
    }
    
    // fix database params if needed
    $this->fix_db_params();
  } // end of parse()
  
  public function display_as_text()
  {
    $str = "NEXT LINE is the BEGINNING of config file\n";
    $str .= preg_replace('/<\?php/', '// IMPORTANT: Replace this line with "&lt;?php"',
      implode("", array_map(create_function('$x', 'return $x->as_text();'), $this->list)));
    echo $str . "END OF config FILE\n";
  } // end of display_as_text()
  
  public function save()
  {
    $save_path = 'config-dir' . DIRECTORY_SEPARATOR . 'config.php-' . $this->site_installation;
    if ($this->needs_save) {
      if (file_exists($save_path)) {
        $backup_path = $save_path . ".BAK";
        if (file_exists($backup_path)) {
          unlink($backup_path);
        }
        rename($save_path, $backup_path);
      }
      $str = implode("\n", array_map(create_function('$x', 'return $x->as_text();'), $this->list));
      return file_put_contents($save_path, trim($str));
    } else {
      return TRUE;
    }
  } // end of save()
  
  public function dump($msg = '')
  {
    $str = "<div class=\"pre\">\nConfigurator Object for path $this->template_path\n";
    $str .= $this->error_messages;
    foreach ($this->list as $slug) {
      $str .= $slug->dump($msg);
    }
    return $str . "</div>\n";
  } // end of dump()
}
// End Classes

// set up environment

// Global Variables

// define path to config.php-template
$config_template_path = '.' . DIRECTORY_SEPARATOR . 'config.php-template';
$template_configurator_obj = new Configurator($config_template_path);

// construct choices array
// Select a site installation to edit. Default to development
$current_site_installation = isset($_POST['_edit_site_installation'])
  ? htmlentities($_POST['_edit_site_installation']) : 'development';
$path = "config-dir" . DIRECTORY_SEPARATOR . "config.php-" . $current_site_installation;
if (file_exists($path)) {
echo "reading $path\n";
  $current_configurator_obj = new Configurator($path);
echo "read $path\n";
// echo $current_configurator_obj->dump();
  // add any fields in the template which are not in this configuration - this is supposed
  //  to automate adding configuration entities to an existing configuration file
  $current_configurator_obj->update($template_configurator_obj);
} else {
  $current_configurator_obj = new Configurator('config.php-template');
  $idx = $current_configurator_obj->index_of_var('site_installation');
  $current_configurator_obj->slug_by_idx($idx)->var_value = $current_site_installation;
}

// echo $current_configurator_obj->dump();
// echo "</pre>\n";
if (isset($_POST['submit']) && htmlentities($_POST['submit']) == 'Update') {
  $current_configurator_obj->parse();
  $save_result = $current_configurator_obj->save();
}

// End Global Variables

// Dispatch Actions
// End Dispatch Actions


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<head>
  <title>Configuration Editor</title>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <meta http-equiv="cache-control" content="NO-CACHE">
  <style>
    body { background:#ffffe0; padding:5px;}
    li.boxed {
      padding:2px;
      padding-bottom:
      10px;border:1px solid #444444;
      margin-left:-2em;
      list-style: none inside;
    }
    
    #info { margin-left: 5em; margin-right: 5em;}
    #hidden-info { display:none;}
    #info:hover #hidden-info { display:block;}

    .box { padding:5px;border:1px black solid;}
    .vspace { margin-top:1em; margin-bottom:1em;}
    .center { text-alignt: center;}
    .float-left { float:left;}
    .float-right {float:right;}
    .clear {clear:both;}
    .even {background:#ffffe0;}
    .odd {background:#ffffd0;}
    .bold {font-weight:bold;}
    .italic { font-style:italic;}
    .underline { text-decoration: underline;}
    .smaller {font-size:small;}
    .larger {font-size:larger;}
    .notice { background: #a0a0a0;}
    .option { background: #4080e0;}
    .action { background: #20ff20;}
    .action-button { background: #40ff20; padding:2px;border:2px solid black; text-decoration: none;}
    .required { background: #ff2000;}
    .recommended { background:#ff8000;}
    .annotation { background: #dddddd;}
    
    .ok { background: #a0ff80;}
    .error { background: #ff0000;}
    
    .pre { white-space:pre; background:#eeeeee;}
  </style>
</head>
<body>
  <h1 class="center">YASiteKit Configuration File Editor</h1>
  <div class="vspace">
    <span class="bold">Leave the Configurator and <a class="box" href="installerator.php">Go to the Installerator</a></span>
  </div>
  <div id="info" class="box vspace">
    <p>
      <span class="bold">This is the YASiteKit Configuration File Creator Tool.</span>
      <span class="italic">(mouse over for info)</span>
    </p>
    <div id="hidden-info">
      <p><span class="bold">If you think this is Cheesy:</span> you might just be right.
        This is really a cheap hack, but it works well enough to tame the config nightmare
        and it didn't take a whole lot of work. In other words: this is probably about
        as much of a config tool as YASiteKit will have - for a long, long time.
      </p>
      <p><span class="bold">Try it - you might just like it.</span>
        If you find any bugs - email me (mike at clove dot com). If you
        have some bright ideas about how to make it better - hack it up and mail me a patch.
      </p>
      <p>You can use it to create and update your configuration
        files. <span class="italic">Or</span>, you can copy the
         <span class="italic">config-dir/config.php-template</span>
        and just edit it by hand. Either way works.
      </p>
    
      <p><span class="bold">Where Are the config Files?</span> This program tries to save
        your files, but that may not always work (permission issues can muck things up).
        As a fallback, the whole file is displayed at the bottom of this window.
        You copy and paste it into your editor and save it. IMPORTANT: You need to
        stick a PHP escape in first line - where it says to.
      </p>
      <p><span class="bold">What is being edited.</span> This is the <span class="bold">configurator</span>
        examines the subdirectory <span class="bold">config-dir</span> for files named
        'config.php-<span class="italic">something</span> and uses them to build a source
        list. <span class="italic">If</span> you save your files there and name them right,
        then you can use the <span class="bold">configurator</span> to maintain them.
        To start with, the only file there is <span class="italic">config.php-template</span>.
        You can select an existing file from the drop-down list and click <span class="bold">Reload</span>
        at any time to edit a different file. <span class="bold">Warning:</span> there
        is <span class="italic">no</span> 'auto-save' feature in this thing. You can easily
        <span class="italic">lose</span> your work.
      </p>
      <p><span class="bold">How Do I Make a Config File</span>. Fill in the blanks and select
        the options. Then click "Update". The <span class="bold">Configurator</span> will
        create the name using the <span class="italic">site_installation</span> parameter.
        That is, the file will be named:
        <span class="pre">config-dir/config.php-&lt;site_installation&gt;</span>
      </p>
      <p>The <span class="bold">Configurator</span> will attempt to write the
        config file you've created to the <span class="italic">config-dir</span> directory.
        This <span class="italic">may not work.</span> It will Tell you What happened
        in the Red or Green box just below these instructions.
      </p>
      <p> If it doesn't then all is not lost:
        you need to copy and paste the config file
        content at the bottom [between 'BEGINNING of config file' and 'END OF config FILE'].
        WARNING: this works <span class="italic">much</span> better if you 'view page as source'
        and copy from there.
      </p>
      <p><span class="bold">Yes, there are a LOT of Things to Configure!</span> Using
        this script should simplify. The <span class="italic">required</span> stuff
        is in <span class="required box">this color box.</span> Recommended stuff
        is in <span class="recommended box">this color box.</span> Each section is
        preceded by a description in <span class="annotation box">this color box</span>.
      </p>
    </div> <!-- end hidden-info -->
  </div> <!-- info -->
  <div class="box">
<?php
  if (isset($save_result)) {
    if ($save_result) {
      echo "<p class=\"box ok\">Save of <span class=\"bold\">$current_site_installation</span> Succeeded</p>\n";
    } else {
      echo "<p class=\"box error\">Save of <span class=\"bold\">$current_site_installation</span> Failed</p>\n";
    }
  }
  if ($current_configurator_obj->error_messages) {
    echo "<div class=\"box\">$current_configurator_obj->error_messages</div>\n";
  }
?>
<?php
echo $current_configurator_obj->form("configurator.php", $current_site_installation);
// echo "<pre>{$current_configurator_obj->dump()}</pre>\n";
?>
  </div>
  <div class="pre">
<?php $current_configurator_obj->display_as_text(); ?>
  </div>
</body>
</html>
