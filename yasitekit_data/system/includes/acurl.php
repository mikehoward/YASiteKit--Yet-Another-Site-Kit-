<?php
/*
#doc-start
h1.  acurl.php - abstracts cURL operations into an object.

Created by  on 2010-03-29.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module defines a - if not simplified, at least - congealed interface
to the PHP "cURL":http://us2.php.net/manual/en/book.curl.php module.

* the *ACurlData* class manage query string and post data. ACurlData objects
differ from PHP associative arrays because data is retrieved in the order
it is created. "see":#acurldata
* the *ACurl* class are a convenience package to make HTTP GET, POST, PUT, HEAD
and DELETE requests more or less painless. "see":#acurl

h2(#acurldata). ACurlData class

Instantiate an ACurlData object by:

$acurldata = new ACurlData([key_case_mode,] [key1, value1, key2, value2, ...])

The _first_ parameter may be 'mixed', 'upper', or 'lower' to set the key case
mode. If it is not, then the key case mode is not defined until EITHER
them method _set_key_case()_ is called OR the first key is assigned a
value [which causes the key case to default to 'mixed'].

The _key case mode_ determines how keys - implemented as object instance attributes
- are interpreted w.r.t. case sensitivity and how they are stored.

If the mode is:

* mixed - then keys are case sensitive and 'frog', 'Frog', and 'FROG' are all
different keys with distinct values.
* upper - then keys are case insensitive and all keys are translated to and
stored as upper case keys. Thus 'frog', 'Frog', and 'FROG' all refer to
the key 'FROG' and have a single, shared value.
* lower - similar to _upper_, but with the obvious difference.

The data which follows the (optional) key-case-mode MUST occur in pairs.
Each (key, value) pair is put away in the order it occurs.

p(#value_rules). Rules for values:

* if the value is a boolean - either TRUE or FALSE, then the query string
will just contain the key name, not the value
* if the value is anything else, then the query string will be the string
representation of the value

Additional key, value pairs may be added to the object by simply assignment.
For example,

bq. $acurldata->foo = 'this is a bar';

creates or assigns the attribute _foo_ the value 'this is a bar'. If _foo_
already exists, it's value is updated. Otherwise both _foo_ and it's value
are appended to the (key, value) list.

h3. Attributes

All _key_ values are accessible as attributes.

h4. Attribute Names

Attribute names must satisfy the regular expression [a-zA-Z]\w*, where \w is a 'word character'.

Attribute names may or may not be case sensitive. See section on instantiation for
details and how to contro _key case mode_.

Attribute names may be specified in any case, but will be retrieved according to
the current key case mode: _mixed_, _lower_, or _upper_.

The retrieved value when retrieved using the _keys()_ method or a constructed
query string is according to key case mode.

h4. Assignment

Attributes may be assigned in three different ways:

# construction - attribute, value pairs are assigned when the object is constructed.
# assignment - writing $acurldata->att_name = 'foo'; assigns the attribute, creating
the key if required.
# merging - all the key, value pairs defined in another ACurlData instance may be merged
with an instance by invoking the _merge_ method. This results in the new values
overwriting any existing values, so merging is _not_ a symetric or non-destructive
operation. Write: $acurldata->merge($other_acurldata);

h4. Access

Attributes may be accessed in  different ways:

# as attributes - $acurldata->attr_name returns the value of _attr_name_ or ''.
_attr_name_ is interepreted according to the existing current key case mode.
# iteration - use _first_key_value()_ and _next_key_value()_ - both of which
return two element arrays of the form array(attribute-name, attribute-value)
# as query string - convert the ACurlData instance to a string:
## (string)$acurldata
## "$acurldata"
## $acurldata->asString()

h3. Class Methods

None

h3. Instance Methods

The magic methods __toString(), __get(), __set(), __isset(), and __unset() are
defined so that ACurlData instances may be used in a natural way.

* __get(name) - $acurldata->foo -  returns the value of the foo, if defined, else NULL.
* __set(name, val) - $acurldata->foo = 'something'; - re-assigns or creates
the variable _foo_ and assigns it the value 'something'. Order of assignment is
preserved.
* __isset($name) - isset($name) . . . - returns TRUE if $name is a defined attribute, else FALSE
* __unset($name) - unset($name); - unsets the specified attribute.
* __toString() method returns the query string made up of the (key, value) pairs
_in order of definition_. See "above":#value_rules for value rules.

Non-magic methods

* legal_key_case($mode) - returns TRUE if _mode_ is one of 'mixed', 'upper', or 'lower'.
* set_key_case($mode) - where _mode_ is one of 'mixed', 'upper', or 'lower'. This
method may only be called once and NOT after any keys are assigned. If the key
case is not set in the constructor and no keys have been assigned, then it may
be assigned using _set_key_case()_. If this method is called after the key case
has been assigned, an exception is thrown.
* key_case() - returns current key case as one of the strings 'mixed', 'upper', or 'lower'.
* keys() - returns an array of all defined keys in the order they were defined.
* asString() - convenience function which returns the value of the ACurlData object
as a string. Identical to (string)$acurldata;
* emptyP() - returns TRUE if the ACurlData instance has no keys set
* first_key_value() - returns the array(key, value) for the first (key, value) pair
and sets the iteration index.
* next_key_value() - returns the the next (key, value) pair as an array OR FALSE
if there are no more. Advances iteration index
* merge($other) - assigns all the (key, value) pairs in _other_ to _this_ ACurlData
instance as though they were assigned using as usual assignments. If _other_ is
not an ACurlData, throws an exception.
* parse_array(array) - works through an array of (key, value) pairs and puts
them away. See "value rules":#value_rules above.
* parse_query_string($str, $urldecode_flag = FALSE) - parses the query string _str_ and assigns it's
values to _this_ object. This is useful for parsing, reformatting and merging query string
data. If _urldecode_flag_ is TRUE, then the data values are url-decoded. 
Default is to snarf them down raw.
* dump(msg = NULL) - returns a string defining the state of this ACurlData instance.

h2(#acurl). ACurl class

Use by instantiating an object and then invoking the objects methods. Each
of the public methods takes a URL. This URL may be either a fully qualified
URL - in which case it is used as is - or it can be a relative or server absolute
URL, in which case the instantiation values for server and scheme/protocol are
used.

h3. Instantiation

$a_curl = new aCurl($url, $agent_str = "Mozilla/4.0", $https = FALSE);

where:

* $url - string - url ACurl will be talking with. This string is parsed
and the _scheme_, _host_, _user_, _password_, values _port_ are saved, if they
exist and define the defaults for subsequent requests.
* $agent_str - string - is the string passed in the 'User-Agent' header
* $https - boolean - is used to deterimine if no-fully specified urls use the HTTP
or HTTPS scheme.

h3. Attributes

* agent_str - string - the text sent in the user agent header
* body - string or NULL - response to last request executed or NULL
* fragment - string - always disregarded - NULL or derived from the instantiation URL
* host - string - the host this aCurl object talks to - derived from instantiation URL
* https - boolean - if TRUE, curl uses the HTTPS [aka SSL aka TLS]. Default is FALSE.
* http_headers - associative array or NULL - headers from last executed request. Keys are header
field names.
* http_start_line - string or NULL - the HTTP start line response from last executed
request
* include_headers - boolean - TRUE to populate http_headers and http_start_line attributes.
Setting _include_headers_ TRUE, automatically sets _verbose_ to FALSE.
* pass - string - NULL or derived from the instantiation URL
* path - string - always diregarded - derived from instantiation URL
* port - string - NULL or derived from the instantiation URL
* query - string - NULL or derived from the instantiation URL
* scheme - string - default string. This will either be set by the instantiating URL or set to _http_ or _https_ depending on the _$https_ boolean
* url - string - url used when instantiating instance - see constructor
* user - string - NULL or derived from the instantiation URL
* verbose - boolean - passed to undelying curl library. Default is FALSE. Must be set programatically after instiating object.

h3. Class Methods

None

h3. Instance Methods

All Data parameters are associative arrays. These are used to construct
proper URL encoded strings to send to the host specified when creating the
aCurl object. Keys to the arrays are NOT url encoded and are NOT sanity
checked.

The actual URL used is constructed using the _$url_ argument augmented
by parts of the _$url_ used in constructing the ACurl object. This is
done by parsing the _$url_ argument using _parse_url()_ to produce an array with
keys: _scheme_, _host_, _port_, _user_, _pass_, _path_, _query_, and _fragment_.

The actual URL is built as follows:

# if _scheme_ is missing, $this->scheme is used
# if _host_ is missing, then _this->host_ is used
# if any of _port_, _user_, or _pass_ is present, it is used. If not, then _this->whatever_
is used.
# if _query_ is supplied allong with query data [as in get()] then it is merged
with the supplied get data. The query string from the URL _preceeds_ the query
data constructed from parameters, so that the arguments to the method take precidence
in the case of duplicated, non-array style query parameters.
# _path_ and _fragment_ are alwyas ignored

This is really simpler than it reads. Basically, it does what you want it to
do.

Query and Post data are specified using pairs of parameters to the _get_ and
_post_ methods respectively. Each pair consists of the variable name, followed
immediately by the variable value. This is done so that the order of occurance
of query and post data may be guarenteed.

For example: $foo->get(url, 'parm1', 'value1', 'parm2', 'value2') will
result in a URL which looks (roughly) like: http://url?parm1=urlencode(value1)&parm2=urlencode(value2)

* get($url, qp1, qv1, ...) - returns of the result of a GET request with the query string
defined by the variable pairs following $url.
* post_data($url, $post_data) - sends _post_data_ to _url_ using POST request. Returns
result.
* post_query($url, pp1, pv1, ...) - returns of the result of a POST request with the post data
defined by the variable pairs following $url
* put_data($url, $data) - same as _post_data()_ except uses PUT request
* put_query($url, pp1, pv1, ...) - same as _post_query()_ but uses PUT HTTP request.
* put_json($url, $put_data) - encodes _put_data_ using json_encode() and passes it to put_data
* put_file($url, $file_path) - returns of the result of a PUT request. The specified
file is read and the contents are PUT to the supplied URL
* delete($url) - returns of the result of a DELETE request to the specified
url.
* dump(msg = NULL) - returns a string which dumps the state of the ACurl object.

#end-doc
*/

// class definitions

class ACurlDataException extends Exception {}

class ACurlData {
  const MIXED = 1;
  const UPPER = 2;
  const LOWER = 3;
  private $_keys = array();
  private $_values = array();
  private $_next_idx = FALSE;
  private $_keys_len = 0;
  private $_key_case = NULL;
  public function __construct()
  {
    $args = func_get_args();
    if ($args && in_array(strtolower($args[0]), array('mixed', 'lower', 'upper'))) {
// echo "\nsetting key case to {$args[0]}\n";
      $this->set_key_case(array_shift($args));
    } else {
// echo "\nNOT SETTING KEY CASE\n";
// debug_print_backtrace();
    }
    $this->parse_array($args);
  } // end of __construct()
  
  public function legal_key_case($mode)
  {
    return in_array(strtolower($mode), array('mixed', 'upper', 'lower'));
  } // end of legal_key_case()
  
  public function set_key_case($mode)
  {
    if ($this->_key_case) {
      throw new ACurlDataException("ACurlData::set_key_case($mode): key_case change not allowed after initializing any keys");
    }
    switch (strtolower($mode)) {
      case 'mixed': $this->_key_case = ACurlData::MIXED; break;
      case 'upper': $this->_key_case = ACurlData::UPPER; break;
      case 'lower': $this->_key_case = ACurlData::LOWER; break;
      default:
        throw new ACurlDataException("ACurlData::set_key_case($mode): Illegal value - must be one of "
          . 'mixed, upper, or lower');
    }
  } // end of set_key_case()
  
  public function key_case()
  {
    switch ($this->_key_case) {
      case ACurlData::MIXED: return 'mixed';
      case ACurlData::UPPER: return 'upper';
      case ACurlData::LOWER: return 'lower';
      case NULL: return 'not-set';
      default:
        throw new ACurlDataException("ACurlData::key_case(): Illegal key case - internal error: $this->_key_case");
    }
  } // end of key_case()
  
  public function keys()
  {
    return $this->_keys;
  } // end of keys()
  
  private function _map_key($name)
  {
    if (!$this->_key_case) {
      $this->set_key_case('mixed');
    }
    switch ($this->_key_case) {
      case ACurlData::MIXED:
        return $name;
      case ACurlData::UPPER:
        return strtoupper($name);
      case ACurlData::LOWER:
        return strtolower($name);
      default:
        throw new ACurlDataException("ACurlData::_map_key($name): internal error - illegal key case: '$this->_key_case'");
    }
  } // end of _key_map()

  public function __get($name)
  {
    if (($i = array_search($this->_map_key($name), $this->_keys)) !== FALSE) {
      return $this->_values[$i];
    }
    return NULL;
  } // end of __get()
  
  public function __set($name, $val)
  {
    if (!preg_match('/^[a-zA-Z]\w*$/', $name)) {
      throw new ACurlDataException("ACurlData::__set($name, value): illegal key name '$name' - must be a word and start with a letter");
    }
    // handle singletons - which are assigned a boolean
    $val = is_bool($val) ? TRUE : (string)$val;

    // replace or append name,value pair
    if (($i = array_search($this->_map_key($name), $this->_keys)) !== FALSE) {
      $this->_values[$i] = $val;
    } else {
      $this->_keys[] = $this->_map_key($name);
      $this->_values[] = $val;
      $this->_keys_len += 1;
    }
  } // end of __set()
  
  public function __isset($name)
  {
    return in_array($this->_map_key($name), $this->_keys);
  } // end of __isset()
  
  public function __unset($name)
  {
    if (($i = array_search($this->_map_key($name), $this->_keys))) {
      unset($this->_keys[$i]);
      unset($this->_values[$i]);
      $this->_keys_len -= 1;
    }
  } // end of __unset()
  
  public function __toString()
  {
    $amph = '';
    $str = '';
    $lim = count($this->_keys);
    for ($i=0;$i<$lim;$i++) {
      $str .= "{$amph}{$this->_keys[$i]}";
      if (!is_bool($this->_values[$i])) {
        $str .= '=' . urlencode($this->_values[$i]);
      }
      $amph = '&';
    }
    return $str;
  } // end of __toString()
  
  public function asString()
  {
    return (string)$this;
  } // end of asString()
  
  public function emptyP()
  {
    return count($this->_keys) == 0;
  } // end of emptyP()
  
  public function first_key_value()
  {
    $this->_next_idx = 0;
    $this->_keys_len = count($this->_keys); // I don't trust myself
    return $this->next_key_value();
  } // end of first_key_value()
  
  public function next_key_value()
  {
    if ($this->_next_idx >= $this->_keys_len) {
      return FALSE;
    }
    $tmp = array($this->_keys[$this->_next_idx], $this->_values[$this->_next_idx]);
    $this->_next_idx += 1;
    return $tmp;
  } // end of next_key_value()
  
  public function merge($other)
  {
    if (!($other instanceof ACurlData)) {
      throw new ACurlDataException("ACurlData::merge(other): other is not an instance of ACurlData");
    }
    for ($row = $other->first_key_value();$row;$row=$other->next_key_value()) {
      list($key, $val) = $row;
      $this->$key = $val;
    }
  } // end of merge()
  
  public function parse_array($args)
  {
    if (count($args) % 2 == 1) {
      throw new ACurlDataException("ACurlData::__construct(): argument count error: number of arguments must be even");
    }
    while ($args) {
      $key = array_shift($args);
      $this->$key = array_shift($args);
    }
    $this->_keys_len = count($this->_keys);
  } // end of parse_array()
  
  public function parse_query_string($str, $urldecode_flag = FALSE)
  {
    $ar = explode('&', $str);
    foreach ($ar as $qp) {
      
      // regx: do non-greed match on all text prior to first '=' and then grab greed match on trailing text
      if (preg_match('/^((?U).*)=(.*)$/', $qp, $match_obj)) {
        $key = $match_obj[1];
        $val = $match_obj[2];
        $this->$key = $urldecode_flag ? urldecode($val) : $val;
      } else {
        $this->$qp = TRUE;
      }
    }
    $this->_keys_len = count($this->_keys);
  } // end of parse_query_string()
  
  public function dump($msg = '')
  {
    $str = ($msg ? $msg . "\n" : '') . "ACurlData Contents $this->_keys_len Variables\n";
    $str .= "Key Case is: {$this->key_case()}\n";
    $str .= "Variables in definition order:\n";
    $len = count($this->_keys);
    for ($i=0;$i<$len;$i++) {
      $str .= "  {$this->_keys[$i]}: {$this->_values[$i]}\n";
    }
    return $str;
  } // end of dump()
}

class ACurlException extends Exception {}

class ACurl {
  private $agent_str;
  private $body;
  private $fragment;
  private $host;
  private $https;
  private $http_headers;
  private $http_start_line;
  private $include_headers;
  private $pass;
  private $path;
  private $port;
  private $query;
  private $scheme;
  private $url;
  private $user;
  private $verbose;
  private static $mutable_attributes = array('verbose', 'include_headers');
  private static $attribute_names = array(
    'agent_str',
    'body',
    'fragment',
    'host',
    'https',
    'http_headers',
    'http_start_line',
    'include_headers',
    'pass',
    'path',
    'port',
    'query',
    'scheme',
    'url',
    'user',
    'verbose',
    );

  public function __construct($url, $agent_str = "Mozilla/4.0", $https = FALSE)
  {
    $this->url = $url;
    $ar = parse_url($url);
    if (array_key_exists('scheme', $ar)) {
      $https = strtolower($ar['scheme']) == 'https';
      $this->scheme = strtolower($ar['scheme']);
    } else {
      $this->scheme = $https ? 'https' : 'http';
    }
    if (!array_key_exists('host', $ar)) {
      throw new ACurlException("ACurl::__construct($host, ...): host name not specified");
    }

    $this->host = $ar['host'];
    foreach (array('user', 'pass', 'port', 'path', 'query', 'fragment') as $key) {
      $this->$key = array_key_exists($key, $ar) ? $ar[$key] : NULL;
    }
    $this->agent_str = $agent_str ? $agent_str : "Mozilla/4.0";
    $this->https = $https;
    $this->verbose = FALSE;
    $this->include_headers = FALSE;
    $this->http_start_line = NULL;
    $this->http_headers = NULL;
    $this->body = NULL;
  } // end of __construct()
  
  public function __toString()
  {
    return "ACurl($this->url / $this->agent_str)";
  } // end of __toString()
  
  public function __set($name, $value)
  {
    switch ($name) {
      case 'include_headers':
        if ($value) {
          $this->include_headers = TRUE;
          $this->verbose = FALSE;
        } else {
          $this->include_headers = FALSE;
        }
        break;
      default:
        if (in_array($name, ACurl::$mutable_attributes)) {
          $this->$name = $value;
        } else {
          throw new ACurlException("ACurl::__set($name, value): attribute '$name' is read only or illegal");
        }
    }
  } // end of __set()
  
  public function __get($name)
  {
    if (in_array($name, ACurl::$attribute_names)) {
      return $this->$name;
    } else {
      throw new ACurlException("ACurl::__get($name): Attempt to access undefined attribute '$name'");
    }
  } // end of __get()

  private function rewrite_url($url, $additional_query_params = NULL)
  {
    $ar = parse_url($url);
    
    // scheme
    $new_url = array_key_exists('scheme', $ar) ? $ar['scheme'] : $new_url = $this->scheme;
    $new_url .= '://';
    
    // userid / password
    if (array_key_exists('user', $ar)) {
      $new_url .= $ar['user'];
      if (array_key_exists('pass', $ar)) $new_url .= ':' . $ar['pass'];
      $new_url .= '@';
    } elseif ($this->user) {
      $new_url .= $this->user;
      if (array_key_exists('pass', $ar)) $new_url .= ':' . $ar['pass'];
      elseif ($this->pass) $new_url .= ':' . $this->pass;
      $new_url .= '@';
    }
    
    // host 
    $new_url .= array_key_exists('host', $ar) ? $ar['host'] : $this->host;
    if (array_key_exists('port', $ar)) {
      $new_url .= ':' . $ar['port'];
    } elseif ($this->port) {
      $new_url .= ':' . $this->port;
    }
    
    // path - strip extra slashes
    // NOTE: ;we always disregard $this->query
    if (array_key_exists('path', $ar)) {
      $new_url .= preg_replace('/\/+/', '/', $ar['path']);
    } else {
      $new_url .= '/';
    }
    
    // query string - use supplied or instantiated, then append additional
    $acurldata = new ACurlDAta();
    if (array_key_exists('query', $ar)) {
      $acurldata->parse_query_string($ar['query']);
    } elseif ($this->query) {
      $acurldata->parse_query_string($this->query);
    }
    if ($additional_query_params) {
      $acurldata->parse_array($additional_query_params);
    }
    if (!$acurldata->emptyP()) $new_url .= "?{$acurldata}";
    
    // fragment - NOTE we always disregard $this->fragment
    if (array_key_exists('fragment', $ar)) $new_url .= '#' . $ar['fragment'];

    return $new_url;
  } // end of rewrite_url()

  private function start_curl_init($url, $get_data = NULL)
  {
    $this->http_start_line = NULL;
    $this->http_headers = NULL;
    $this->body = NULL;
    $ch = curl_init($this->rewrite_url($url, $get_data));
    if ($ch === FALSE) {
      return $ch;
    }

    curl_setopt_array($ch, array(
      CURLOPT_FORBID_REUSE => TRUE,   // as a connection hijack preventative
      CURLOPT_RETURNTRANSFER => TRUE,  // gets return from curl_exec() so we can return it
      CURLOPT_VERBOSE => $this->verbose,     // debugging to stderr. Set to FALSE for production
      CURLOPT_HEADER => $this->include_headers, // causes headers to included in output
      CURLOPT_CONNECTTIMEOUT => 30, // connection timeout in seconds. 0 blocks
      CURLOPT_TIMEOUT => 30,       // execution timeout. Don't know if this fits inside connectiontimeout
      CURLOPT_ENCODING => '',      // requests all supported encoding types
      CURLOPT_USERAGENT => $this->agent_str,
    ));
    // CURLOPT_PROTOCOLS is not defined until curl 7.19.4, so we have to test for it prior to setting
    if (defined("CURLOPT_PROTOCOLS")) {
      curl_setopt($ch, CURLOPT_PROTOCOLS, $this->https ? CURLPROTO_HTTPS : CURLPROTO_HTTP);
    }

    return $ch;
  } // end of start_curl_init()
  
  public function finish_curl($ch)
  {
    $rsp = curl_exec($ch);
    if (($error_str = curl_error($ch))) {
      throw new ACurlException("ACurl::finish_curl(): Failed: $error_str");
    }
    curl_close($ch);
    
    // parse out headers into http_start_line and http_headers
    if ($this->include_headers) {
      $rsp_ar = preg_split("/\r\n/", $rsp);
      $this->http_start_line = array_shift($rsp_ar);
      $this->http_headers = array();
      while (($tmp = array_shift($rsp_ar))) {
        if (!$tmp) {
          if (isset($field_name)) {
            $this->http_headers[$field_name] = $field_value;
            unset($field_name);
          }
          break;
        }
        if (preg_match('/^\s/', $tmp)) {
          $field_value .= ' ' . trim($tmp);
        } else {
          if (isset($field_name)) {
            $this->http_headers[$field_name] = $field_value;
          }
          $colon_idx = strpos($tmp, ':');
          $field_name = substr($tmp, 0, $colon_idx);
          $field_value = trim(substr($tmp, $colon_idx + 1));
        }
      }

      // return body of message
      return ($this->body = implode("\r\n", $rsp_ar));
    } else {
      return ($this->body = $rsp);
    }
  } // end of finish_curl()

  public function get()
  {
    $get_data = func_get_args();
    $url = array_shift($get_data);
    if (!($ch = $this->start_curl_init($url, $get_data))) {
      return FALSE;
    }

    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);

    return $this->finish_curl($ch);
  } // end of get()

  public function post_data($url, $post_data)
  {
    if (!($ch = $this->start_curl_init($url))) {
      return FALSE;
    }

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    return $this->finish_curl($ch);
  } // end of post_data()

  public function post_query()
  {
    $post_data = func_get_args();
    $url = array_shift($post_data);
    $acurldata = new ACurlData();
    if ($post_data && $acurldata->legal_key_case($post_data[0])) {
      $acurldata->set_key_case(array_shift($post_data));
    } 
    $acurldata->parse_array($post_data);
    return $this->post_data($url, $acurldata->asString());
  } // end of post_query()

  public function put_data($url, $put_data)
  {
    if (!($ch = $this->start_curl_init($url))) {
      return FALSE;
    }

    $tmp = base64_encode($put_data);
    $fp = fopen("data://text/plain;base64,$tmp", 'r');

    curl_setopt($ch, CURLOPT_PUT, TRUE);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, strlen($put_data));

    $rsp = $this->finish_curl($ch);
    fclose($fp);
    return $rsp;
  } // end of put()
  
  public function put_query()
  {
    $put_data = func_get_args();
    $url = array_shift($put_data);
    $acurldata = new ACurlData();
    $acurldata->parse_array($put_data);
    return $this->put_data($url, $acurldata->asString());
  } // end of post_query()

  public function put_json($url, $put_data)
  {
    return $this->put_data($url, json_encode($put_data));
  } // end of put_json()

  public function put_file($url, $infile_path = NULL)
  {
    if (!($ch = $this->start_curl_init($url))) {
      return FALSE;
    }

    curl_setopt($ch, CURLOPT_PUT, TRUE);
    if ($infile_path) {
      if (($fd = fopen($infile_path, "r")) === FALSE) {
        curl_close($ch);
        return FALSE;
      }
      $fd_len = filesize($infile_path);
      curl_setopt($ch, CURLOPT_TIMEOUT, 600);
      curl_setopt($ch, CURLOPT_INFILE, $fd);
      curl_setopt($ch, CURLOPT_INFILESIZE, $fd_len);
    }

    return $this->finish_curl($ch);
  } // end of put()
  
  public function delete($url)
  {
    if (!($ch = $this->start_curl_init($url))) {
      return FALSE;
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    return $this->finish_curl($ch);
  } // end of delete()
  
  public function dump($msg = NULL)
  {
    $str = $msg ? $msg : '';
    $str .= "Dump of ACurl($this->host)\n";
    foreach (array('scheme', 'host', 'port', 'user', 'pass', 'path', 'fragment') as $key) {
      $str .= " $key: {$this->$key}\n";
    }
    $str .= " agent_str: $this->agent_str\n";
    $str .= " https: " . ($this->https ? 'TRUE' : 'FALSE') . "\n";
    $str .= " verbose: " . ($this->verbose ? 'TRUE' : 'FALSE') . "\n";
    $str .= " include_headers: " . ($this->include_headers ? 'TRUE' : 'FALSE') . "\n";

    if ($this->http_start_line) {
      $str .= "Start Line: $this->http_start_line\n";
      $str .= "Headers:\n";
      foreach ($this->http_headers as $field_name => $field_value) {
        $str .= "  $field_name: $field_value\n";
      }
    }
    
    if ($this->body) {
      $str .= "Body:\n$this->body\n";
    }

    return $str;
  } // end of dump()
}

// end class definitions

?>
