<?php
/*
#doc-start
h1.  request_cleaner - Encapsulates all filtering of all user supplied data into RequestCleaner object

Created by  on 2010-02-28.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

The class RequestCleaner centralizes user input sanitizing by filtering
data source objects through _htmlentities_ or optionally retrieving the
raw object. All data which is retrieved is saved in the class cache -
so that all subsequent retrievals come directly from the cache.

All instances of RequestCleaner are interdependent because they share the
same cache.

h2. Instantiation

Create a object using
<pre>
  $foo = new RequestCleaner([source, source, ...]);
</pre>

Where the optional _source_ arguments are one of _get_, _post_, _cookie_, or _files_.
For each _source_ value, the associated
super global is used as the 'source' and all (_key_, _value_) pairs are filtered
through _htmlentities()_ and saved in class variable $cache.

If _source_ is NULL, then the cache is initialized to an empty array.

h2. Attributes

All attributes are dynamic. They can take one of four forms:

* safe_&lt;source&gt;_&lt;name&gt; - where _source_ is one of 'post', 'get', 'cookie', 'files',
or 'request'
and _name_ is the name of an variable. The value returned has been sanitized by _htmlentities()_.
NOTE: the _source_ _request_ pulls its value from the GET or POST superglobal arrays. This
differs from PHP's REQUEST superglobal which also queries COOKIE.
* raw_&lt;source&gt;_&lt;name&gt; - where _source_ is one of 'post', 'get', 'cookie', or 'files'
and _name_ is the name of an variable. The value returned is the raw value in the array.
* _cache - which is a distinguished token. Returns a copy of the RequestCleaner::$cache
variable.
* _name_ - where _name_ is the name of a value which has been assigned manually. This cannot
be a value retrieved from one of the HTTP request super-globals.

Attributes may be assigned values. The value overwrites the value in the cache, but
does not effect the value in the super-global. Thus it is possible to both change
the values which will be returned from the RequestCleaner for values originally
taken from the super-globals.

Attributes may _not_ be unset, but may be tested to see if they are set.

h2. Methods.

* hiddens - returns a string containing all the cached 'safe_' variable values encoded
as HTML _input_ elements of type _hidden_. The _name_ field of each element is
the string 'hidden_&lt;name&gt;', where _name_ is the name of the variable after
the safe/raw and super-global prefixes have been stripped.
* dump($msg = NULL) - returns a string containing all of the elements in the cache -
suitable for printing. If only a _raw_ version of the element exists, then
it is returned in sanitized form.

#end-doc
*/

// global variables

// end global variables

// class definitions
class RequestCleanerException extends Exception {}

class RequestCleaner {
  private static $cache = array();

  public function __construct()
  {
  } // end of construct()
  
  private function apply_htmlentities($value='')
  {
    if (is_array($value)) {
      return array_map(create_function('$s','return htmlentities($s);'), $value);
    } elseif (is_string($value)) {
      return htmlentities($value);
    } else {
      throw new RequestCleanerException("RequestCleaner::apply_htmlentities(value): value is neither string nor array");
    }
  } // end of apply_htmlentities()
  
  public function __get($name)
  {
    if (!preg_match('/^\w+$/', $name)) {
      return '';
    }
    if (isset(RequestCleaner::$cache[$name])) {
      return RequestCleaner::$cache[$name];
    }
    if (!isset($this->$name)) {
      return FALSE;
    }
    if (preg_match('/^(raw|safe)_request_(\w+)$/', $name, $matches)) {
      $tmp = $matches[1] . "_get_" . $matches[2];
      if (isset($this->$tmp)) { return $this->$tmp; }
      $tmp = $matches[1] . "_post_" . $matches[2];
      if (isset($this->$tmp)) { return $this->$tmp; }
      return '';
    } elseif (preg_match('/^raw_(get|post|cookie|files)_(\w+)$/', $name, $matches)) {
      switch ($matches[1]) {
        case 'get': return (RequestCleaner::$cache[$name] = $_GET[$matches[2]]);
        case 'post': return (RequestCleaner::$cache[$name] = $_POST[$matches[2]]);
        case 'cookie': return (RequestCleaner::$cache[$name] = $_COOKIE[$matches[2]]);
        case 'files': return (RequestCleaner::$cache[$name] = $_FILES[$matches[2]]);
      }
    } elseif (preg_match('/^safe_(get|post|cookie|files)_(\w+)$/', $name, $matches)) {
      switch ($matches[1]) {
        case 'get': return (RequestCleaner::$cache[$name] = $this->apply_htmlentities($_GET[$matches[2]]));
        case 'post': return (RequestCleaner::$cache[$name] = $this->apply_htmlentities($_POST[$matches[2]]));
        case 'cookie': return (RequestCleaner::$cache[$name] = $this->apply_htmlentities($_COOKIE[$matches[2]]));
        case 'files': return (RequestCleaner::$cache[$name] = $this->apply_htmlentities($_FILES[$matches[2]]));
      }
    } elseif ($name == '_cache') {
      return RequestCleaner::$cache;
    } else {
      throw new RequestCleanerException("RequestCleaner::__get($name): illegal attribute name");
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    if (!preg_match('/^\w+$/', $name)) {
      return '';
    }
    if (preg_match('/^(safe|raw)_(get|post|cookie|files|request)_(\w+)$/', $name)) {
      RequestCleaner::$cache[$name] = $value;
    } else {
      throw new RequestCleanerException("RequestCleaner::__set($name, ...): illegal attribute name");
    }
  } // end of __set()
  
  public function __unset($name)
  {
    return FALSE;
  } // end of __unset($name)()
  
  public function __isset($name)
  {
    if (!preg_match('/^\w+$/', $name)) {
      return FALSE;
    }
    if (isset(RequestCleaner::$cache[$name])) {
      return TRUE;
    } elseif (preg_match('/^(raw|safe)_(get|post|cookie|files)_(\w+)$/', $name, $matches)) {
      $key = $matches[3];
      switch ($matches[2]) {
        case 'get': return isset($_GET[$key]);
        case 'post': return isset($_POST[$key]);
        case 'cookie': return isset($_COOKIE[$key]);
        case 'files': return isset($_FILES[$key]);
      }
    } elseif (preg_match('/^(raw|safe)_request_(\w+)$/', $name, $matches)) {
      $get_version = $matches[1] . '_get_' . $matches[2];
      $post_version = $matches[1] . '_post_' . $matches[2];
      return $this->__isset($get_version) || $this->__isset($post_version);
    }
  } // end of __isset()
  
  public function hiddens()
  {
    $str = '';
    foreach (RequestCleaner::$cache as $key => $value) {
      if (preg_match('/^safe_(get|post|cookie|files)_(\w+)$/', $key, $matches)) {
        $name = "hidden_{$matches[2]}";
        $str .= "<input type=\"hidden\" name=\"name\" value=\"$value\" id=\"name\">\n";
      }
    }
    return $str;
  } // end of hiddens()
  
  private function format_value($value, $sanitize = TRUE)
  {
    if (is_array($value)) {
      $str = "\n";
      foreach ($value as $key => $val) {
        $str .= "    $key => " . $this->format_value($val, $sanitize) . "\n";
      }
      return $str;
    } else {
      return $sanitize ? htmlentities($value) : $value;
    }
  } // end of format_value()

  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\">\nRequestCleaner: $msg\n";

    $str .= "Current State of Cache:\n";
    foreach (RequestCleaner::$cache as $key => $value) {
      if (preg_match('/^(raw|safe)_(get|post|cookie|files)_(\w+)$/', $key, $matches)) {
        if ($matches[1] == 'safe') {
          $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value, FALSE));
        } elseif (!isset(RequestCleaner::$cache["safe_{$matches[2]}_{$matches[3]}"])) {
          $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value));
        }
      } else {
        $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value));
      }
    }
    
    // Load cache with all defined request variables
    foreach (array('get', 'post', 'cookie', 'files') as $source) {
      switch ($source) {
        case 'get': $source_global = $_GET ; break;
        case 'post': $source_global = $_POST ; break;
        case 'cookie': $source_global = $_COOKIE ; break;
        case 'files': $source_global = $_FILES ; break;
        default: throw new PageException("RequestCleaner::__construct($source): Illegal data source");
      }
      foreach ($source_global as $key => $value) {
        if (preg_match('/^\w+$/', $key)) {
          $safe_varname = "safe_{$source}_$key";
          $raw_varname = "raw_{$source}_$key";
          $x = $this->$safe_varname;
          $x = $this->$raw_varname;
        }
      }
    }

    $str .= "\nAll Defined Request Variables:\n";
    foreach (RequestCleaner::$cache as $key => $value) {
      if (preg_match('/^(raw|safe)_(get|post|cookie|files)_(\w+)$/', $key, $matches)) {
        if ($matches[1] == 'safe') {
          $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value, FALSE));
        } elseif (!isset(RequestCleaner::$cache["safe_{$matches[2]}_{$matches[3]}"])) {
          $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value));
        }
      } else {
        $str .= sprintf("%-20s %s\n", $key . ":", $this->format_value($value));
      }
    }
    
    return $str . "</div>\n";
  } // end of dump()
}

// end class definitions

?>
