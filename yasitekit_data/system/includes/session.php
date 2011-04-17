<?php
/*
#doc-start
h1.  session - Encapsulates all Session handling into an Object

Created by  on 2010-02-28.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. Session Object

The Session Object packages up PHP session management into a nice, neat
bundle, providing a uniform interface to configuration, access to session
variables, etc.

The Session object implements a Singleton pattern to create a single
entry point to all session data and functioning.

h3. Attributes

Session variables are mapped to attributes of the Session object. One
feature of this is that only specific session variables are allowed -
thus trapping spelling errors.

Here they are

h4. diagnostic keys

* account_dump - boolean - turns on diagnostic stuff if TRUE
* divert_track - string - an accumlation of page diversions. This allows
tracking page redirects. This is used in _includes.php_ functions _IncludeUtilities::redirect_to_with_return()_
and _IncludeUtilities::redirect_to()_
* rc_track - string - contains a dump of the request cleaner at each stage of page
redirecting. This helps diagnosing errors in redirecting code

h4. infrastructure keys

* cookie_request_retries - count of number of attempts to get user to turn
on cookies.
* reserved_page_name - name of page which was redirected from - saved automatically
by the function _IncludeUtilities::redirect_to_with_return()_ in _includes.php_
* user_cookie_value - value of application cookie
* detector_cookie_value - value of _last_ detector cookie sent to client
* javascript_ok - boolean - TRUE if user agen allows Javascript

h4. user keys

* logged_in - boolean - True if user is logged in. Should duplicate return of
Globals::$account_obj->logged_in()
* userid - string - userid associated with the current value of _user_cookie_value_
* time_now - int - time value of when the current request was received by the server
* timeout - int - time value after which the current request is _stale_. This is used
to time out the logged in status of the current account. Is set by the previous
request to _time_now_ + Globals::$inactivity_timeout

h4. Category keys

see "Category.php":/doc.d/system-objects/Category.html for details

* category_defaults - associative array - maps Category parents to paths

h4. normal information and naviagation keys

* product_name - string - name of current image - used to restore image when returning
to DisplayProduct.php
* product_gallery_style - string - either 'list' or 'table'
* product_gallery_sort_by - string - name of sort field - defaults to 'title'
* product_gallery_max_per_page - int - defaults to 20
* product_gallery_page_number - int -  defaults to 0 (I think)
* shopping_cart_order_number - string - current shopping cart


h3. Class Methods

The constructor is private, so cannot be called directly.

*Session::get_session(debug_flag = FALSE)* - returns the single Session instance.

* use_cookies - boolean - if TRUE, then the session id is retrieved from the user
via the session cookie. If FALSE, then it is embedded in the URL
* debug_flag - boolean - turns on debugging via the session attributes described above.

h3. Instance Methods

h4. Error Message Handling

Error messages may be communicated between page loads by putting them away in the
session data. One method is available to add messages to the session data and
two methods for display.

These methods directly manipulate the super global $_SESSION. These messages
are _not_ available as object attributes.

* add_message($msg) - appends the message to the super global $_SESSION under
the key 'messages'. If _$msg_ does not end in a new line, one is appended.
* clear_messages() - unsets $_SESSION['messages'].
* render_messages($element = <div class="error-fixed-formats">) - returns $_SESSION['messages']
it it is set, otherwise the empty string: ''. If _$element_ is an HTML element start tag,
then the corresponding end tag is created and the return string is enclosed in the
element. If _$element_ is _not_ an HTML element (no leading '<'), then it is still
prepended to the return string, but no trailing text is synthesized and added.
* render_messages_and_clear($element = <div class="error-fixed-formats")>) - is identical
with _render_messages()_ except that it also unsets $_SESSION['messages'].

These methods allow failure counts to be accumulated in a session:

* clear_failure_count() - sets the failure count to 0. This should be called on a
known good event, before a _bad thing_ happens
* increment_failure_count() - adds 1 to the failure_count variable
* anti_dos_delay() - approximates a quadratic delay function - to increasingly
slow down responses failure conditions.

h4. Session Handling and Diagnostics

* start_session() - starts the session, if it not already running. Returns TRUE
if it did something, FALSE if the session was already up.
* dump(msg) - displays the session variables wrapped in a _div_ with class 'dump-output'
* close_session() - just calls _session_write_close()_
* viciously_destroy_session() - destroys the session as recommended in the 
"PHP manual":http://www.php.net/manual/en/function.session-destroy.php

#end-doc
*/

/*
Session Logic

1. Should we bother with sessions w/o Cookies?
1.1 robots? - NO
1.2 humans? - insecure
1.3 human/robot detection

2. If no cookies

2.1 Test to see if they accept cookies -
know how to do this
*/

// class definitions
# session handling

class SessionException extends Exception {}

class Session {
  const TABLENAME = 'sessions';
  static public $field_definitions = array(
    array('id', 'varchar(255)', TRUE),
    array('latest_access', 'varchar(255)'),
    array('data', 'text')
    );
  static private $instance = NULL;
  static private $legal_session_keys = array(
    // diagnostic keys
    'account_dump',
    'divert_track',
    'rc_track',

    // infrastructure keys
    'cookie_request_retries',
    'failure_count',
    'reserved_page_name',
    'user_cookie_value',
    'detector_cookie_value',
    'javascript_ok',
    
    // archive keys
    'dump_dir',

    // user keys
    'logged_in',
    'userid',
    'alternate_userids',   // if set, this is a list of all array(userids, name) pairs associated with this cookie
    'time_now',
    'session_timeout',
    'inactivity_timeout',

    // category keys - see Category.php for details
    'category_defaults',

    // product information and naviagation keys
    'product_name',
    'product_gallery_style',
    'product_gallery_sort_by',
    'product_gallery_max_per_page',
    'product_gallery_page_number',
    'shopping_cart_order_number',
    
    // shopping keys
    'continue_shopping',  // URL to return to from cart operations
     );
  private $debug_flag = FALSE;
  private $session_timed_out;
  private $login_timed_out;

  public static function get_session($debug_flag = FALSE)
  {
    if (!Session::$instance) {
      Session::$instance = new Session($debug_flag);
    }
    return Session::$instance;
  } // end of get_session()

  private function diag_output($msg)
  {
    static $f = NULL;
    if (!$this->debug_flag) {
      return;
    }
    if (!$f) {
      $f = fopen('/tmp/session-foo', 'a');
      fwrite($f, "-----------------------------------------------------\n");
      if (Globals::$rc) {
        fwrite($f, Globals::$rc->dump('Request Cleaner From Sessions Constructor') . "\n");
        ob_start();
        echo "_GET: \n";
        var_dump($_GET);
        fwrite($f, ob_get_clean() . "\n");
      } else {
        fwrite($f, "request cleaner not yet initialized\n");
      }
    }
    // echo $msg . "\n";
    fwrite($f, $msg);
  } // end of diag_output()

  private function __construct($debug_flag)
  {
    $this->debug_flag = $debug_flag;

    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    if ($this->debug_flag) {
      ini_set('session.gc_probability', 1000);
      ini_set('session.gc_divisor', 1000);
      ini_set('session.gc_maxlifetime', 60);
    }
    // cookie lifetime: 8 * 3600 seconds 
    session_name(Globals::$session_cookie_name);
    // Globals::$messages .= $this->make_string('session_get_cookie_params() before setting', session_get_cookie_params()) . "\n";
    // set session to expire in 1/2 hour
    session_set_cookie_params(Globals::$session_timeout, '/', NULL, FALSE);
    // Globals::$messages .= $this->make_string('session_get_cookie_params() after setting', session_get_cookie_params()) . "\n";
    session_set_save_handler(
      array($this, "_session_open"),
      array($this, "_session_close"),
      array($this, "_session_read"),
      array($this, "_session_write"),
      array($this, "_session_destroy"),
      array($this, "_session_gc"));

    // add in any additional session keys for this site
    if (isset(Globals::$additional_session_keys) && is_array(Globals::$additional_session_keys)) {
      Session::$legal_session_keys = array_unique(array_merge(Session::$legal_session_keys,
          Globals::$additional_session_keys));
    }

    // start session here
    // return if session already started
    $this->diag_output("Starting Session\n");
    if (!session_start()) {
      $this->diag_output('Failed to start session ' . Globals::$session_name . "\n");
      throw new Exception('Failed to start session ' . Globals::$session_name);
    }

    // if session has timed out, then DEstroy and restart
    $this->session_timed_out = isset($_SESSION['session_timeout']) && intval($_SESSION['session_timeout']) < time();
    $this->login_timed_out = isset($_SESSION['inactivity_timeout']) && intval($_SESSION['inactivity_timeout']) < time();
    $_SESSION['time_now'] = time();
    $_SESSION['session_timeout'] = time() + Globals::$session_timeout;
    $_SESSION['inactivity_timeout'] = time() + Globals::$inactivity_timeout;

    Globals::$session_id = session_id();
    Globals::$dbaccess->register_close_function(array($this, 'close_session'));
    return TRUE;
  } // end of __construct()

  public function __toString()
  {
    return "Session(): " . Globals::$session_id;
  } // end of __toString()

  public function __get($name)
  {
    switch ($name) {
      case 'session_timed_out':
      case 'login_timed_out':
        return $this->$name;
      default:
        if (in_array($name, Session::$legal_session_keys)) {
          return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
        }
        throw new SessionException("Session::__get($name): illegal attribute name");
    }
  } // end of __get()

  public function __set($name, $val)
  {
    if (in_array($name, Session::$legal_session_keys)) {
      $_SESSION[$name] = $val;
    } else {
      throw new SessionException("Session::__set($name, $val): attempt to set illegal session key: $name");
    }
  } // end of __set()

  public function __isset($name)
  {
    switch ($name) {
      case 'session_timed_out':
      case 'login_timed_out':
        return TRUE;
      default:
        return in_array($name, Session::$legal_session_keys) && isset($_SESSION[$name]);
    }
  } // end of __isset()

  public function __unset($name)
  {
    if (in_array($name, Session::$legal_session_keys)) {
      unset($_SESSION[$name]);
    }
  } // end of __unset()
  
  public function add_message($msg)
  {
//      echo "add_message(): $msg\n";
    if (substr($msg, strlen($msg) - 1) != "\n") $msg .= "\n";
    if (!isset($_SESSION['messages']))
      $_SESSION['messages'] = $msg;
    else
      $_SESSION['messages'] .= $msg;
  } // end of add_message()
  
  public function clear_messages()
  {
    if (isset($_SESSION['messages'])) unset($_SESSION['messages']);
  } // end of clear_messages()

  public function render_messages($element = "<div class=\"error-fixed-format\">")
  {
    if (!isset($_SESSION['messages']))
      return '';
    $tail_element = preg_match('/^<(\w+)/', $element, $match_obj) == 1 ? "</{$match_obj[1]}>\n" : '';
    return $element . "\n" . $_SESSION['messages'] . $tail_element;
  } // end of display_messages()
  
  public function render_messages_and_clear($element = "<div class=\"error-fixed-format\">")
  {
    $str = $this->render_messages($element);
    $this->clear_messages();
    return $str;
  } // end of display_messages_and_clear()
  
  public function clear_failure_count()
  {
    $this->failure_count = 0;
  } // end of clear_failure_count()

  public function increment_failure_count()
  {
    $this->failure_count += 1;
  } // end of increment_failure_count()
  
  public function anti_dos_delay()
  {
    sleep(intval($this->failure_count * $this->failure_count/2.0));
  } // end of anti_dos_delay()
  
  public static function php_create_string($dbaccess, $dump_dir)
  {
    if (!is_dir($dump_dir)) {
      if (!mkdir($dump_dir)) {
        echo "Skipping Session data\n";
        return FALSE;
      }
    }

    $tmp = $dbaccess->select_from_table(Session::TABLENAME);
    $str = "<?php\n\$dbaccess->create_table('" . Session::TABLENAME . "', unserialize('"
      . serialize(Session::$field_definitions) . "'), \$drop_first)"
      . " or die(\"Unable to create sessions table\\n{\$dbaccess->error()}\\n\");\n";
    if ($tmp) {
      foreach ($tmp as $row) {
        $str .= "\$dbaccess->insert_into_table('" . Session::TABLENAME . "', unserialize(base64_decode('"
          . base64_encode(serialize($row)) . "')));\n";
      }
    }
    $str .= "?>\n";

    $fname = $dump_dir . DIRECTORY_SEPARATOR . '_sessions.php';
    echo "Dumping Session data\n";
    return file_put_contents($fname, $str);
  } // end of php_creat_string()

  private function dump_helper($val)
  {
    if (is_string($val) || is_int($val) || is_float($val)) {
      return "$val";
    } elseif (is_bool($val)) {
      return $val ? 'TRUE' : 'FALSE';
    } elseif (method_exists($val, 'dump')) {
      return $val->dump('from Session::dump()');
    } elseif ($val instanceof DateTime) {
      return $val->format('c');
    } else {
      ob_start();
      print_r($val);
      return ob_get_clean();
    }
  } // end of dump_helper()

  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\">\n";
    if ($msg) $str .= $msg . "\n";
    $str .= "Start of Session Dump:\n";
    $keys = Session::$legal_session_keys;
    sort($keys);
    foreach ($keys as $session_key) {
      $str .= "Key: $session_key: ";
      if (!isset($_SESSION[$session_key])) {
        $str .= "(not set)\n";
      } else {
        $str .= $this->dump_helper($_SESSION[$session_key]) . "\n";
      }
    }
    $str .= (isset($_SESSION['messages']) ? "Messages: " . $_SESSION['messages'] : "Messsages: (not set)") . "\n";
    return $str . "</div> <!-- End of Session Dump -->\n";
  } // end of dump()

  public function close_session()
  {
    session_write_close();
  } // end of close_session()

  public function viciously_destroy_session()
  {
    IncludeUtilities::write_to_tracker('Session: ' . basename(__FILE__) . ":" . __LINE__ . "\n");
    $this->diag_output('Destroying session - timeout: ' . $_SESSION['session_timeout'] . '/' . time() . "\n");
    session_destroy();
    $_SESSION = array();
  //  if (isset($_COOKIE[session_name()])) {
    $session_cookie_params = session_get_cookie_params();
    setcookie(session_name(), FALSE, time() - 3600);
    Session::$instance = NULL;
  } // end of viciously_destroy_session()

  private function make_string($msg, $thing)
  {
    ob_start();
    echo "$msg: ";
    var_dump($thing);
    return ob_get_clean();
  } // end of make_string()

  public function _session_open($save_path, $session_name) {
    global $session_tmp;
    $this->diag_output("_session_open($save_path, $session_name)\n");
    //$this->diag_output("_session_open(): Globals::$session_id: " . Globals::$session_id . "\n");
    return TRUE;
  }

  public function _session_close() {
    global $session_tmp;
    $this->diag_output("_session_close()\n");
    // Globals::$messages .= "<pre>Called _session_close()</pre>\n";
    return TRUE;
  }

  public function _session_read($id) {
    global $session_tmp;
    $this->diag_output("_session_read($id)\n");
    // Globals::$messages .= "<pre>Called _session_read($id)</pre>\n";

    $where = Globals::$dbaccess->escape_where(array('id' => (string)$id));
    $this->diag_output($this->make_string('_session_read(): where clause', $where) . "\n");

    $tmp = Globals::$dbaccess->select_from_table('sessions', NULL, $where);
    $this->diag_output($this->make_string('_session_read(): sessions table data', $tmp) . "\n");
    Globals::$dbaccess->update_table('sessions', array('latest_access' => (string)time()), $where);
    return $tmp && isset($tmp[0]['data']) ? $tmp[0]['data'] : '';
  }

  public function _session_write($id, $sess_data) {
    global $session_tmp;
    $this->diag_output("_session_write($id, " . $this->make_string("sess_data", $sess_data) . ")\n");
    // Globals::$messages .= "<pre>Called _session_write($id, $sess_data)</pre>\n";
    Globals::$dbaccess->delete_from_table('sessions', array('id' => $id));
    $tmp = Globals::$dbaccess->insert_into_table('sessions', array('id' => $id,
      'latest_access' => (string)time(), 'data' => $sess_data));
    $this->diag_output($this->make_string("_session_write($id, ...): Result: ", $tmp));
    return TRUE;
  }

  public function _session_destroy($id) {
    global $session_tmp;
    $this->diag_output("_session_destroy($id)\n");
    // Globals::$messages .= "<pre>Called _session_destroy($id)</pre>\n";
    $tmp = Globals::$dbaccess->delete_from_table('sessions', array('id' => $id));

    return TRUE;
    return $tmp;
  }

  public function _session_gc($maxlifetime) {
    global $session_tmp;
    $this->diag_output("_session_gc($maxlifetime)\n");
    // Globals::$messages .= "<pre>Called _session_gc($maxlifetime)</pre>\n";
    Globals::$dbaccess->delete_from_table('sessions', "where latest_access < " . (string)(time() - intval($maxlifetime)));
    return TRUE;
  }
}
?>
