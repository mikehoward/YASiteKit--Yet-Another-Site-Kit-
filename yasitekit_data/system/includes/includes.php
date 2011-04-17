<?php
/*
#doc-start
h1.  includes.php - Controller common initialization after config and prior to dispatch

Created by  on 2010-02-09.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*includes.php* examines the request for presence of cookies and then
initializes the primary access control objects: Globals::$session_obj
and Globals::$account_obj. It sets four flags which describe the type
of request:

* Globals::$flag_is_robot - TRUE if the request came from a known robot
* Globals::$flag_cookies_ok - TRUE if all required cookies are present
and the 'detector' cookie is valid
* Globals::$flag_session_ok - TRUE if the session object has been initialized.
Essentially equivalent to checking 'Globals::$session_obj instanceof Session',
but faster.
* Globals::$flag_account_ok - TRUE if the account object has been initialized
to an Accont instance. Essentially equivalent to checking
'Globals::$account_obj instanceof Account', but faster.

IMPORTANT: _includes.php_ sets no cookies or headers - other than the session
cookie which is set _if_ it is possible to create a session object. [If it finds
that the session has timed out, that cookie is expired]

This also defines a collection of utility functions as class methods of
the class IncludeUtilities. This creates a namespace which protects from
name collisions and provides some error trapping.

It also modifies the exception handler and provides common diagnostic support
and support for request redirection.

h2. Function Definitions

All functions are wrapped in the IncludeUtilities object by declaring them
as _static public_ methods. This avoids name-space collisions and catches
spelling errors.

h3. Diagnostics:

* IncludeUtilities::report_bad_thing($msg) - sends an error report to the webmaster
* IncludeUtilities::write_to_tracker($msg) - a diagnostic tool. It is essentially a
NOP if _IncludeUtilities::$enable_tracking_ is FALSE.

h3. Utilities:

* IncludeUtilities::array_flatten($ar) - recursively flattens out an array by discarding keys
and creating a linear array of all elements. [does not flatten objects, they
are treated as scalars]
* IncludeUtilities::_encrypt($value, $key_value, $iv_len) -
encrypts _$value_ using the TwoFish algorithm and returns the encrypted value as
a base64 encoded string.
* IncludeUtilities::_encrypt($value, $key_value, $iv_len) -
the matching decryption routine. _$value_ must be a base64 encoded encryption value
created by ==_==encrypt(). Naturally the _$key_value_ and _$iv_len_ must match


h3. Redirection:

* IncludeUtilities::rewrite_qs($url, $add_to_qs = array(), $del_from_qs = array()) -
returns a rewritten url where get parameters in _$add_to_qs_ are redefined and
in _$del_from_qs_ are removed. Both _$add_to_qs_ and _$del_from_qs_ can be arrays
of GET parameter names, GET parameter definitions ( as in foo=bar ), or a string
containing comma separated definitions.
* IncludeUtilities::redirect_to($where, $diverted_from) - sends a Location heder to redirect to
_where_. _diverted_from_ should be a string containing __FILE__ and __LINE__. It
is writen someplace we can find it.
* IncludeUtilities::redirect_to_with_return($where, $diverted_from) - if Sessions are running and the key
'reserved_page_name' is not set, then $_SERVER['REQUEST_URI'] is saved, so
we can return to the page we redirect to. Redirection is implemented by calling
_diverted_to()_

h3. Cookie Management

These routines facilitate monitoring the connecting client's cookie
handling.

Briefly, if a request is made which does not contain all the expected cookies and
the client is not known to be a robot, then we check to see if the unique query string
parameter is set in the GET query string. If it is not, then
cookies are set and the client is redirected back to this page with a query string
parameter which is set uniquely to this request. If it is, then we just die and don't
respond.

These routines help manage this algorithm.

* IncludeUtilities::is_botP() - returns TRUE if the client is a known robot. This
is a sufficient, but not in any way a necessary condition test.
* IncludeUtilities::handle_no_cookies($from_msg) - 
* IncludeUtilities::qs_token_plus_tag() - returns a query string parameter which is specific
to both the client and the request.
* IncludeUtilities::check_qs_token_plus_tag() - Check a returned GET parameter to see if
it makes sense. The token is the md5 hash of the HTTP_USER_AGENT and REMOTE_ADDR values
from the $_SERVER superglobal. The tag is the integer time() value at the time the token
is generated. The check requires that the returned token matches a newly computed value
and that the return come back within 10 seconds of generating the redirect.
* IncludeUtilities::set_all_cookies() - If a session is running (Globals::$session_obj instanceof Session),
then set's the user cookie and detector cookie, saves their values in appropriate places and
returns TRUE. If a session is not running, reports the problem and returns FALSE. The
session id is created and set by creating the "Session object":/doc.d/system-includes/session.html
[which is stored in Globals::$session_obj].
* IncludeUtilities::check_detector_cookie() - this function is used to determine if
cookies are indeed being allowed by the client. A detector cookie is sent with each
response with the value set to the _time()_ value at the time of the response and that
value saved in the session store. Returns TRUE if the cookie is defined, a session
is running, and the value returned matches the value in the session store.

h3. Exception Handling

* IncludeUtilities::exception_handler() - our exception handler.
Packages up a bunch of useful information and passes it to _report_bad_thing()_.
Closes the session if a session is active.
If Globals::$flag_exceptions_on is TRUE, restores the exception handler and re-throws
the exception. If FALSE, redirects to /exceptho_thrown_page
It respects Globals::$flag_exceptions_on.

#doc-end
*/

// require_once's
require_once('dbaccess.php');
require_once('session.php');
require_once('request_cleaner.php');
require_once('Account.php');

// stream_resolve_include_path() is in PHP >= 5.3.2, but not in 5.2.x
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

// class and function definitions

// IncludeUtilities provides a name space for utility functions
class IncludeUtilitiesException extends Exception {}

class IncludeUtilities {
  public static $enable_tracking = NULL;
  private static $mcrypt_rand = FALSE;
  private static $iv_len = FALSE;
  
  private static $cookie_letters = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
    "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O",
    "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
    "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n",
    "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", );
    
  // disable constructor
  private function __construct()  {} // end of __construct()
  
  public static function write_to_tracker($msg)
  {
    static $check_for_reset = TRUE;
    static $reset_session_tracking = FALSE;
    if ($check_for_reset) {
      if (isset(Globals::$rc->safe_get_reset_tracker)) {
        unset(Globals::$rc->safe_get_reset_tracker);
        system("/bin/rm -f /tmp/divert_to");
        system("/bin/rm /tmp/trackers/*");
        rmdir("/tmp/trackers");
        $reset_session_tracking = TRUE;
      }
      $check_for_reset = FALSE;
    }

    if ($reset_session_tracking && Globals::$session_obj instanceof Session) {
      Globals::$session_obj->divert_track = '';
      Globals::$session_obj->rc_track = '';
      Globals::$session_obj->account_dump = '';
      Globals::$session_obj->clear_messages();
      $reset_session_tracking = FALSE;
    }

    // short circuit if tacking is not enabled
    if (!IncludeUtilities::$enable_tracking) {
      return;
    }

    if (!file_exists('/tmp/trackers')) {
      mkdir('/tmp/trackers');
      file_put_contents('/tmp/trackers/next-id', '000');
    }
    if (Globals::$session_obj instanceof Session) Globals::$session_obj->divert_track .= basename($_SERVER['REQUEST_URI']) . "\n";
    $idx = file_get_contents('/tmp/trackers/next-id');
    $tracker_content = "<div class=\"dump-output\">\nTracker Msg: $msg\n</div><!-- end tracker msg -->\n\n"
      . (Globals::$session_obj instanceof Session ? Globals::$session_obj->dump()
        : "<div class=\"dump-output\">No Session Data\n</div>\n")
      . Globals::dump();
    file_put_contents("/tmp/trackers/$idx", $tracker_content);
    file_put_contents("/tmp/trackers/next-id", sprintf("%03d", $idx+1));
    
  } // end of IncludeUtilities::write_to_tracker_file()

  // a handy function for flattening arrays which come in as arguments when
  //  we need a variable number of arguments, but need to originate it as an array
  public static function array_flatten($ar)
  {
    $ret_ar = array();
    foreach ($ar as $elt) {
      if (is_array($elt)) {
        $ret_ar = array_merge($ret_ar, IncludeUtilities::array_flatten($elt));
      } else {
        $ret_ar[] = $elt;
      }
    }
    return $ret_ar;
  } // end of IncludeUtilities::array_flatten()
  
  // Static Fuctions
  public static function camel_to_words($camel) {
    $ar = str_split($camel);
    $words = strtolower(array_shift($ar));
    foreach ($ar as $char) {
      $words .= ctype_upper($char) ? '_' . strtolower($char) : $char;
    }
    return $words;
  } // end of camel_to_words()
  
  public static function words_to_camel($words) {
    $ar = array_map(create_function('$a', 'return ucfirst($a);'), explode('_', $words));
    return implode('', $ar);
  } // end of words_to_camel()

  
  public static function _encrypt($value, $key_value, $iv_len)
  {
    if (IncludeUtilities::$mcrypt_rand === FALSE) {
      // use MCRYPT_RAND on Windows instead  - needed for through php 5.2.x*/
      IncludeUtilities::$mcrypt_rand = preg_match('/window/', strtolower(php_uname('s'))) ? MCRYPT_RAND : MCRYPT_DEV_RANDOM;
      IncludeUtilities::$iv_len = mcrypt_get_iv_size('twofish', 'ofb');
    }
    if (!$value) {
      if (Globals::$site_installation == 'development') {
        echo "IncludeUtilities::_encrypt(): value is empty\n";
        debug_print_backtrace();
      }
      return '';
    }
    /* Open the cipher */
    $td = mcrypt_module_open('twofish', '', 'ofb', '');

    $iv = mcrypt_create_iv($iv_len, IncludeUtilities::$mcrypt_rand);

    /* Intialize encryption */
    mcrypt_generic_init($td, $key_value, $iv);

    /* Encrypt data */
    $encrypted = mcrypt_generic($td, $value);

    /* Terminate encryption handler */
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return base64_encode($iv . $encrypted);
  } // end of _encrypt()
  
  public static function _decrypt($value, $key_value, $iv_len)
  {
    if (IncludeUtilities::$mcrypt_rand === FALSE) {
      // use MCRYPT_RAND on Windows instead  - needed for through php 5.2.x*/
      IncludeUtilities::$mcrypt_rand = preg_match('/window/', strtolower(php_uname('s'))) ? MCRYPT_RAND : MCRYPT_DEV_RANDOM;
      IncludeUtilities::$iv_len = mcrypt_get_iv_size('twofish', 'ofb');
    }
    if (!$value) {
      if (Globals::$site_installation == 'development') {
        echo "IncludeUtilities::_decrypt(): value is empty\n";
        debug_print_backtrace();
      }
      return '';
    }
    if (!$key_value) {
      throw new IncludeUtilitiesException("IncludeUtilities::_decrypt(): Empty Key Vaue");
    }
    /* Open the cipher */
     $td = mcrypt_module_open('twofish', '', 'ofb', '');

     $raw_data = base64_decode($value);
     $iv = substr($raw_data, 0, $iv_len);
     $encrypted_data = substr($raw_data, $iv_len);

     if (!$iv) {
       echo "value '$value'\n";
       echo "iv_len: $iv_len\n";
       debug_print_backtrace();
     }

     /* Intialize encryption */
     mcrypt_generic_init($td, $key_value, $iv);

     /* Encrypt data */
     $decrypted = mdecrypt_generic($td, $encrypted_data);

     /* Terminate encryption handler */
     mcrypt_generic_deinit($td);
     mcrypt_module_close($td);

     return $decrypted;
  } // end of FunctionName()


  public function is_botP()
  {
    static $bot_user_agent_regx = array(
      "/ABACHOBot/i",
      "/Accoona-AI-Agent/i",
      "/AnyApexBot/i",
      "/Arachmo/i",
      "/B-l-i-t-z-B-O-T/i",
      "/Baiduspider/i",
      "/BaiDuSpider/i",
      "/BecomeBotl/i",
      "/Bimbot/",
      "/BlitzBot/i",
      "/boitho.com-dc/i",
      "/boitho.com-robot/i",
      "/btbot/i",
      "/Cerberian Drtrs/i",
      "/Charlotte/i",
      "/ConveraCrawler/i",
      "/cosmos/i",
      "/DataparkSearch/i",
      "/DiamondBot/i",
      "/discobot/i",
      "/DotBot/i",
      "/EmeraldShield.*WebBot/i",
      "/envolk.*spider/i",
      "/EsperanzaBot/i",
      "/Exabot/i",
      "/FAST Enterprise Crawler/i",
      "/FAST-WebCrawler/i",
      "/ FDSE robot/i",
      "/findlinks/i",
      "/FurlBot/i",
      "/FyberSpider/i",
      "/g2Crawler/i",
      "/Gaisbot/i",
      "/GalaxyBot/i",
      "/genieBot/i",
      "/Gigabot/i",
      "/Girafabot/i",
      "/Googlebot/i",
      "/Googlebot-Image/i",
      "/hl_ftien_spider/i",
      "/htdig/i",
      "/ia_archiver/i",
      "/ichiro/i",
      "/IRLbot/i",
      "/IssueCrawler/i",
      "/Jyxobot/i",
      "/LapozzBot/i",
      "/larbin_xy250/i",
      "/LarbinWebCrawler /i",
      "/cn_web_viewer_web/i",
      "/larbin/i",
      "/LinkWalker/i",
      "/lmspider/i",
      "/lwp-trivial/i",
      "/mabontland.com/i",
      "/magpie-crawler/i",
      "/Mediapartners-Google/i",
      "/MJ12bot/i",
      "/Mnogosearch/i",
      "/mogimogi/i",
      "/MojeekBot/i",
      "/Morning Paper/i",
      "/msnbot/i",
      "/MSRBOT/i",
      "/MVAClient/i",
      "/NetResearchServer/i",
      "/NG-Search/i",
      "/nicebot/i",
      "/noxtrumbot/i",
      "/Nusearch Spider/i",
      "/NutchCVS/i",
      "/obot/i",
      "/oegp/i",
      "/OmniExplorer_Bot/i",
      "/OOZBOT/i",
      "/Orbiter/i",
      "/PageBitesHyperBot/i",
      "/polybot/i",
      "/Pompos/i",
      "/psbot/i",
      "/PycURL/i",
      "/RAMPyBot/i",
      "/RufusBot/i",
      "/SandCrawler/i",
      "/SBIder/i",
      "/Scrubby/i",
      "/SearchSight/i",
      "/Seekbot/i",
      "/semanticdiscovery/i",
      "/Sensis Web Crawler/i",
      "/SEOChat::Bot/i",
      "/Shim-Crawler/i",
      "/ShopWiki/i",
      "/Shoula robot/i",
      "/silk/i",
      "/Snappy/i",
      "/sogou/i",
      "/Speedy Spider/i",
      "/Sqworm/i",
      "/StackRambler/i",
      "/SurveyBot/i",
      "/SynooBot/i",
      "/Ask Jeeves/i",
      "/TerrawizBot/i",
      "/TheSuBot/i",
      "/Thumbnail.CZ robot/i",
      "/TinEye/i",
      "/TurnitinBot/i",
      "/updated/i",
      "/Vagabondo.*webcrawler/i",
      "/VoilaBot/i",
      "/Vortex/i",
      "/voyager/i",
      "/VYU2/i",
      "/webcollage/i",
      "/Websquash/i",
      "/http:\/\/www.almaden.ibm.com\/cs\/crawler/i",
      "/WoFindeIch.*Robot/i",
      "/Xaldon_WebSpider/i",
      "/yacybot/i",
      "/yacy/i",
      "/Slurp/i",
      "/YahooSeeker/i",
      "/YandexBot/i",
      "/yoogliFetchAgent/i",
      "/Zao/i",
      "/Zealbot/i",
      "/zspider/i",
      "/ZyBorg/i",

      );
    static $bot_ip_addr = array(
      );

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    foreach ($bot_user_agent_regx as $regx) {
      if (preg_match($regx, $user_agent)) {
        return TRUE;
      }
    }
    $user_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    foreach ($bot_ip_addr as $ip) {
      if ($user_ip == $ip) {
        return TRUE;
      }
    }
    return FALSE;
  } // end of is_botP()

  public static function rewrite_qs($url, $add_to_qs = array(), $del_from_qs = array())
  {
    $parsed_url = parse_url($url);
    $query = isset($parsed_url['query']) ? explode('&', $parsed_url['query']) : array();
    $ar = array();

    if (is_string($add_to_qs)) {
      $add_to_qs = preg_split('/\s*,\s*/', $add_to_qs);
    }
    if (is_string($del_from_qs)) {
      $del_from_qs = preg_split('/\s*,\s*/', $del_from_qs);
    }
    foreach (array_merge($query, $add_to_qs) as $tmp) {
      if (preg_match('/([^\[=\]]*)((\[\])?=(.*))?/', $tmp, $match_obj)) {
        if (in_array($match_obj[1], $del_from_qs)) {
          continue;
        }
        switch (count($match_obj)) {
          case 2:
            // this automatically clobbers any existing value
            $ar[$match_obj[1]] = TRUE;
            break;
          case 5:
            // index 3 contains either '' or [] - for array values.
            if ($match_obj[3]) {
              // arrays are always appended to
              $key = $match_obj[1] . '[]';
              if (!isset($ar[$key])) {
                $ar[$key] = array();
              }
              $ar[$key][] = $match_obj[4];
            } else {
              // this automatically clobbers any existing value
              $ar[$match_obj[1]] = $match_obj[4];
            }
            break;
          default:
            throw new IncludeUtilitiesException("add_qs_values_uniquely($url, ...): illegal query string value: $tmp");
        }
      }
    }

    $str = '';
    foreach ($ar as $key => $val) {
      if (is_array($val)) {
        foreach ($val as $tmp) {
          $str .= "&" . $key . "=" . $tmp;
        }
      } elseif ($val === TRUE) {
        $str .= "&$key";
      } else {
        $str .= "&{$key}={$val}";
      }
    }
    $parsed_url['query'] = substr($str, 1);
  // var_dump($parsed_url);

    // build new url
    $url = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    if (isset($parsed_url['user'])) {
      $url .= $parsed_url['user'] . (isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '') . '@';
    }
    if (isset($parsed_url['host'])) {
      $url .= $parsed_url['host'];
      if (isset($parsed_url['path'])) {
        $url .= '/' . $parsed_url['path'];
      }
    } elseif (isset($parsed_url['path'])) {
      $url .= $parsed_url['path'];
    }

    if (isset($parsed_url['query'])) {
      $url .= '?' . $parsed_url['query'];
    }
    if (isset($parsed_url['fragment'])) {
      $url .= '#' . $parsed_url['fragment'];
    }

    return $url;
  } // end of rewrite_qs()
  
  // IncludeUtilities::redirect_to(where) - performs a redirect without saving current page
  public static function redirect_to($where, $diverted_from)
  {
    // diagnostic code for tracking redirects using the SESSION store
    IncludeUtilities::write_to_tracker("diverting to '$where' from '$diverted_from': " . basename(__FILE__) . ":". __LINE__."\n");
    $redirection_count = Globals::$rc->safe_request_redirection ? Globals::$rc->safe_request_redirection + 1 : 1;
    $qs_additions = IncludeUtilities::$enable_tracking ? array("redirection=$redirection_count", "diverted_from={$diverted_from}")
      : array("redirection=$redirection_count");
    $url = IncludeUtilities::rewrite_qs($where, $qs_additions, array('reset_tracker'));

    if (isset(Globals::$rc->safe_request_redirection) && Globals::$rc->safe_request_redirection > 4) {
      echo "Damned!!!! Redirection Loop";
      $redirection_count -= 1;
      echo '-' . Globals::$page_name . '-' . $diverted_from . " Redirection count: $redirection_count";
      return;
    }

    // tracking divert_to requests to a file
    if (Globals::$site_installation == 'development') {
      $f = fopen("/tmp/divert_to", "a", 0755);
      fwrite($f, "$where from $diverted_from\n"); // Globals::dump("\ndiverting to '$where' from '$diverted_from'"));
      fclose($f);
    }

    if (Globals::$session_obj instanceof Session) Globals::$session_obj->close_session();
    header("Location: $url");
    exit(0);
  } // end of IncludeUtilities::redirect_to()
  
  // IncludeUtilities::redirect_to_with_return(where) - performs a redirect while conditionally saving the current page
  // in session under the key 'reserved_page_name'. Condition is that no page was previously
  // saved
  public static function redirect_to_with_return($where, $redirected_from)
  {
    // FIXME: I don't know why I used the 'basename()' before, but it doesn't work properly
    //  with RequestRouter paths, whereas the full REQUEST_URI does.
    // WATCH THIS
    // if (Globals::$session_obj instanceof Session && !isset(Globals::$session_obj->reserved_page_name)) {
    //   Globals::$session_obj->reserved_page_name = basename($_SERVER['REQUEST_URI']);
    // } elseif (isset($_SESSION) && !isset($_SESSION['reserved_page_name'])) {
    //   $_SESSION['reserved_page_name'] = basename($_SERVER['REQUEST_URI']);
    // }
    if (Globals::$session_obj instanceof Session && !isset(Globals::$session_obj->reserved_page_name)) {
      Globals::$session_obj->reserved_page_name = $_SERVER['REQUEST_URI'];
    } elseif (isset($_SESSION) && !isset($_SESSION['reserved_page_name'])) {
      $_SESSION['reserved_page_name'] = $_SERVER['REQUEST_URI'];
    }
    IncludeUtilities::redirect_to($where, $redirected_from);
  } // end of IncludeUtilities::redirect_to_with_return()
  
  public static function report_bad_thing($subject, $body = NULL)
  {
    ob_start();
    echo "Subject: $subject\n\n";
    if ($body) echo "$body\n\n";
    debug_print_backtrace();
    echo "\n\n";
    echo Globals::dump('A Bad Thing');
    echo "\$_SERVER:\n";  print_r($_SERVER);
    echo "\$_GET\n"; print_r($_GET);
    echo "\$_POST\n"; print_r($_POST);
    echo "\$_COOKIE\n"; print_r($_COOKIE);
    echo "\$_FILES\n"; print_r($_FILES);
    $email_body = ob_get_clean();
    mail(Globals::$webmaster, $subject, $email_body, 'From: ' . Globals::$webmaster);
  } // end of IncludeUtilities::report_bad_thing()
  
  public static function handle_no_cookies($from_msg)
  {
    if (Globals::$flag_is_robot) {
      // set up fake session object and empty account by ensuring the placebo flag is set in their NoObject
      // instances. Then return.
      if (Globals::$session_obj instanceof Session) {
        Globals::$session_obj->vicously_destroy_session();
        Globals::$session_obj = new NoObject('session_obj');
      }
      if (Globals::$account_obj instanceof Account) {
        Globals::$account_obj = new NoObject('account_obj');
      }
      Globals::$flag_session_ok = FALSE;
      Globals::$flag_account_ok = FALSE;
      Globals::$flag_cookies_ok = FALSE;
      Globals::$session_obj->placebo = TRUE;
      Globals::$account_obj->placebo = TRUE;
      return;
    } else {
      if (!Globals::$rc->safe_request_qs_token) {
        if (!(Globals::$session_obj instanceof Session)) {
          Globals::$session_obj = Session::get_session();
        }
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__);
        IncludeUtilities::set_all_cookies();
        IncludeUtilities::redirect_to(IncludeUtilities::rewrite_qs($_SERVER['REQUEST_URI'],
            array('qs_token=' . IncludeUtilities::qs_token_plus_tag())),
            $from_msg);
            // basename(__FILE__) . ":" . __LINE__);
        IncludeUtilities::write_to_tracker('After IncludeUtilities::redirect_to() '
          . $from_msg);
          // . basename(__FILE__) . ':' . __LINE__);
        return;
      } elseif (IncludeUtilities::check_qs_token_plus_tag(Globals::$rc->safe_request_qs_token)) {
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__);
        Globals::$session_obj = Session::get_session();
        IncludeUtilities::set_all_cookies();
        Globals::add_message("Please Turn on Cookies - we really need them. (usually your preferences or Internet Options pop-up)");
        // Globals::$session_obj->placebo = TRUE;
        Globals::$account_obj->placebo = TRUE;
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__ . ' at ' . time());
      } else {
        // this should not display anything
        exit();
      }
    }
  } // end of handle_no_cookies()

  public static function qs_token_plus_tag()
  {
    return  md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']) . dechex(time());
  } // end of qs_token_plus_tag()

  public static function check_qs_token_plus_tag($received)
  {
    $tmp = urldecode($received);
    $token = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    if (substr($tmp, 0, ($len = strlen($token))) != $token) {
      return FALSE;
    }
    $timestamp = hexdec(substr($tmp, $len));
    // OK if returned within last 10 seconds
    return $timestamp >= time() - 10;
  } // end of check_qs_token_plus_tag()

  public static function set_all_cookies()
  {
    
    // set cookie values
    Globals::$user_cookie_value = isset($_COOKIE[Globals::$user_cookie_name])
        && $_COOKIE[Globals::$user_cookie_name]
      ? $_COOKIE[Globals::$user_cookie_name] : IncludeUtilities::new_cookie_value();
    setcookie(Globals::$user_cookie_name, Globals::$user_cookie_value,
        time() + Globals::$cookie_timeout, '/');
    $detector_value = time();
    setcookie(Globals::$detector_cookie_name, $detector_value,
      time() + Globals::$session_timeout, '/');
    if (Globals::$session_obj instanceof Session) {
      Globals::$session_obj->detector_cookie_value = $detector_value;
      return TRUE;
    } else {
      IncludeUtilities::report_bad_thing("Globals::session_obj is not a Session");
      return FALSE;
    }
  } // end of set_all_cookies()

  public static function check_detector_cookie()
  {
    return isset($_COOKIE[Globals::$detector_cookie_name])
      && Globals::$session_obj instanceof Session
      && $_COOKIE[Globals::$detector_cookie_name] == Globals::$session_obj->detector_cookie_value;
  } // end of check_detector_cookie()

  public static function exception_handler($exception) {
    ob_start();
    echo "Exception: " . $exception->getMessage() . " \n";
    echo "Occurred at ". $exception->getFile() . ':' . $exception->getLine() . " \n\n";
    echo "Exception Backtrace:\n";
    echo $exception->getTraceAsString();
    echo "=========================== \n\n";
    $str = ob_get_clean();
    echo $str;
    if (Globals::$flag_email_reports) {
      IncludeUtilities::report_bad_thing(Globals::$site_name . " Exception", $str);
    }

    if (Globals::$session_obj instanceof Session) Globals::$session_obj->close_session();

    if (Globals::$flag_exceptions_on) {
      restore_error_handler();
      throw $exception;
    }
    header("Location: /exception_thrown_page.php?error_message=$str");
  }
  
  public static function new_cookie_value()
  {
    $cookie_letters_len = count(IncludeUtilities::$cookie_letters) - 1;

    $str = '';
    for ($i=0;$i<40;$i++) {
      $str .= IncludeUtilities::$cookie_letters[rand(0, $cookie_letters_len)];
    }
    return $str;
  } // end of new_cookie_value()
  
}
// diagnostic code for tracking redirects using the SESSION store
IncludeUtilities::$enable_tracking = FALSE && Globals::$site_installation == 'development';

// wire in report bad thing to exceptions

set_exception_handler(array('IncludeUtilities', 'exception_handler'));

// End class and function Definitions

// ******************************** Basic Setup **********************************
// initialize database access
// check and set defaults for essential application parameters
Globals::$dbaccess = new DBAccess(Globals::$db_params);
if (!isset(Globals::$dbaccess->on_line)) {
  // This is a guess, but probably a good one.
  Globals::$dbaccess->on_line = 'F';
  Globals::$dbaccess->database_valid = 'T';
  Globals::$dbaccess->archive_stale = 'F';
  Globals::$dbaccess->model_mismatch = 'X';
}

// load the object information class. This will auto-load the object info map and enables
//  ObjectInfo::do_require_once() and ObjectInfo::do_require()
require_once('ObjectInfo.php');

// get and initialize the general request data cleaner. All GET/POST/FILES/COOKIE data
//  are passed through this object. This centralizes user data cleaning
switch (Globals::$site_installation) {
  case 'development':
    Globals::$rc = new RequestCleaner('get', 'post', 'files', 'cookie');
    break;
  default:
    Globals::$rc = new RequestCleaner('get', 'post', 'files');  // original
    break;
}
if (!isset(Globals::$rc->safe_request_page_name) || !Globals::$rc->safe_request_page_name) {
  Globals::$rc->safe_request_page_name = '/index.php';
  Globals::$rc->raw_request_page_name = '/index.php';
}
Globals::$page_name = Globals::$rc->safe_request_page_name;
Globals::$page_ext = preg_replace('/^.*\./', '', Globals::$page_name);
Globals::$flag_is_robot = IncludeUtilities::is_botP();

// set the session object and start the session here so that check_detector_cookie()
//  can check the detector cookie in the session store
if (!Globals::$flag_is_robot && isset($_COOKIE[Globals::$session_cookie_name])) {
  Globals::$session_obj = Session::get_session();
  Globals::$flag_session_ok = TRUE;
}

// From her on, we simply check the request and set the four flags:
//  flag_is_robot, flag_cookies_ok, flag_session_ok, flag_account_ok as appropriate.
//  NOTE: All four flags default to FALSE, so we only have to set the TRUE values
// This tests to see if all the cookies we need are present and that they are current
// NOTE: This test NEVER passes without an active session - which is checked in check_detector_cookie()
if ($_COOKIE
    && isset($_COOKIE[Globals::$detector_cookie_name])
    && isset($_COOKIE[Globals::$session_cookie_name])
    && isset($_COOKIE[Globals::$user_cookie_name])
    && IncludeUtilities::check_detector_cookie()) {
  Globals::$flag_cookies_ok = TRUE;
  Globals::$user_cookie_value = $_COOKIE[Globals::$user_cookie_name];
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__);
} else {
  return;  // we're done
}

// if we have cookies, then delete all redirection references and qs_token references.
//  NOTE: this does not effect redirections which don't have 'qs_token' set.
if (Globals::$rc->safe_get_qs_token) {
  $target = "/qs_token=" . Globals::$rc->safe_get_qs_token . '/';
  foreach ($_SERVER as $key => $val) {
    $_SERVER[$key] = preg_replace(array($target, '/redirection/', '/\&\&/', '/^\&/', '/\&$/', '/\?$/'),
        array('', '', '&', '', '', ''), $_SERVER[$key]);
  }
  unset(Globals::$rc->safe_get_qs_token);
}

// check to see if we need to kill off this session
if (Globals::$flag_session_ok && Globals::$session_obj->session_timed_out) {
  setcookie(Globals::$session_cookie_name, session_id(), time() - 30*86400);
  Globals::$session_obj->vicously_destroy_session();
  Globals::$session_obj = new NoObject('session_obj');
  Globals::$flag_session_ok = FALSE;
  return;
}

// if we get here, then we can assume that the session is OK and running

// ************************* Account Handling Section **************************
// if the user is continuing a session, then continue it
// if user is cookied and there are one or more active accounts on this cookie, guess
require_once('CookieTrack.php');
Globals::$cookie_track = new CookieTrack(Globals::$dbaccess, Globals::$user_cookie_value);
if (Globals::$session_obj->userid) {
  Globals::$account_obj = new Account(Globals::$dbaccess, Globals::$session_obj->userid);
  Globals::$flag_account_ok = TRUE;
} else {
  // now we can try to guess the account by selecting the most recently used
  //  active account for this cookie
  $list_of_accounts = array_filter(Account::list_of_cookied_accounts(Globals::$dbaccess,
          Globals::$cookie_track),
      create_function('$o', 'return $o->state == "A";'));
  switch (count($list_of_accounts)) {
    case 0:
      break;
    case 1:
      Globals::$account_obj = $list_of_accounts[0];
      Globals::$session_obj->userid = Globals::$account_obj->userid;
      break;
    default:
    Globals::$account_obj = $list_of_accounts[0];
    Globals::$session_obj->userid = Globals::$account_obj->userid;
    Globals::$session_obj->alternate_userids = array_map(create_function('$o', 'return array($o->userid,$o->name);'),
        $list_of_accounts);
    break;
  }
  if (count($list_of_accounts) > 0) {
    Globals::$account_obj = $list_of_accounts[0];
    Globals::$session_obj->userid = Globals::$account_obj->userid;
    Globals::$flag_account_ok = TRUE;
  }
}

// *************** Check to see if this user is logged in and exceeded the inactivity timeout
if (Globals::$account_obj instanceof Account
    && ((Globals::$session_obj->login_timed_out && Globals::$account_obj->logged_in())
      || Globals::$rc->safe_get_logout == 'Y')) {
  Globals::$account_obj->logout();
}
IncludeUtilities::write_to_tracker( basename(__FILE__) . ":" . __LINE__)
?>
