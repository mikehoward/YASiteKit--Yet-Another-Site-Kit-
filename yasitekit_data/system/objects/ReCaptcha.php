<?php
/*
#doc-start
h1.  ReCaptcha.php - encapsulates ReCaptcha Captcha service

Created by  on 2010-03-21.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a simple object which encapsulates the reCaptcha protocol. You will
use it by instantiating an object with the required parameters [see below].

Then invoke the verify method using the local RequestCleaner instance.

If the verify method returns FALSE, then expose the captcha form by echoing
the output of _render()_. If FALSE, then the _error_code_ attribute will
_only_ be set if there was a previous catcha which needed to be checked and
that check failed.

The pattern looks something like this:

<pre>
$recaptcha = new ReCaptcha(domain, pub-key, priv-key, theme-name, https = TRUE or FALSE)

<form ...>
. . .
&lt;?php
  if (!$recaptcha->verify(Globals::$rc)) {
    if ($recaptcha->error_code) echo "... $recaptcha->error_code or translation ...";
    echo $recaptcha->render();
  }
?&gt;
. . .
</form>
</pre>

See _test_recaptcha.php_ for an example of use.

h2. Instantiation

$recaptcha = new ReCaptcha($domain, $public_key, $private_key, $theme_name, $https = ReCaptcha::HTTP)

Where:

* domain - is the domain of validity of the reCaptcha account
* public_key - public key associated with this domain. Get this from
recaptcha.net
* private_key - private key associated with this domain. Get this from
recaptcha.net.
* $theme_name - name of a stock reCaptcha theme. Legal names are: red, white, blackglass, and clean.
* error - error string sent back by reCaptcha verification
* https - flag - if TRUE, use HTTPS to communicate with the reCaptcha server

h2. Attributes

* success - boolean - TRUE if last recaptcha was verified; FALSE if it failed; NULL
if no test was made.
* error_code - string - error code from last recaptcha verify(), else NULL

h2. Class Constants

Use these constants (with care) by prefixing with the class name, as in ReCaptcha::HTTPS.
The only ones normally used are HTTP and HTTPS. The server constants were supplied
by _recaptcha.net_ and are here for documentation purposes only. They are used internally,
but not intended for external use.

* RECAPTCHA_API_SERVER = "http://api.recaptcha.net";
* RECAPTCHA_API_SECURE_SERVER = "https://api-secure.recaptcha.net";
* RECAPTCHA_VERIFY_SERVER = "http://api-verify.recaptcha.net/";
* HTTP = FALSE;
* HTTPS = TRUE;

h2. Class Methods

None

h2. Instance Methods

* render() - returns a string consisting of a recaptcha form
* verify(rc) - returns results of verifying a recaptcha response with the reCaptcha server.
_rc_ is a RequestCleaner instance. Returns TRUE on successful verification or
FALSE on verifification failure or if there is nothing to verify.
If the return is FALSE, the _error_code_ attribute is set if the verify failed
and NULL if there was nothing to check.

#end-doc
*/

class ReCaptchaException extends Exception {}

class ReCaptcha {
  const RECAPTCHA_API_SERVER = "http://api.recaptcha.net";
  const RECAPTCHA_API_SECURE_SERVER = "https://api-secure.recaptcha.net";
  const RECAPTCHA_VERIFY_SERVER = "http://api-verify.recaptcha.net/";
  const HTTP = FALSE;
  const HTTPS = TRUE;
  private static $theme_names = array('red', 'white', 'blackglass', 'clean');
  public $success = NULL;
  public $error_code = NULL;
  private $valid;

  public function __construct($domain, $public_key, $private_key, $theme_name = 'red', $https = ReCaptcha::HTTP)
  {
    $this->valid = $domain ? TRUE : FALSE;
    $this->domain = $domain;
    $this->public_key = $public_key;
    $this->private_key = $private_key;
    $this->https = $https;
    $this->theme_name = in_array($theme_name, ReCaptcha::$theme_names) ? $theme_name : ReCaptcha::$theme_names[0];
    $this->server = $https ? ReCaptcha::RECAPTCHA_API_SECURE_SERVER : ReCaptcha::RECAPTCHA_API_SERVER;
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'valid':
        return $this->$name;
        break;
      default:
        throw new ReCaptchaException("ReCaptcha::__get($name): Illegal attribute '$name'");
    }
  } // end of __get()
  
  public function render()
  {
    if (!$this->valid) {
      return '';
    }
    $error_part = $this->error_code ? '&error={$this->error_code}' : '';
    return "
      <script type=\"text/javascript\" charset=\"utf-8\">
        var RecaptchaOptions = { theme : '{$this->theme_name}' };
      </script>
    <script type=\"text/javascript\" src=\"{$this->server}/challenge?k={$this->public_key}{$error_part}\">
    </script>
    <noscript>
    	<iframe src=\"$this->server/noscript?k={$this->public_key}{$error_part}\" height=\"300\" width=\"500\" frameborder=\"0\"></iframe><br/>
    	<textarea name=\"recaptcha_challenge_field\" rows=\"3\" cols=\"40\"></textarea>
    	<input type=\"hidden\" name=\"recaptcha_response_field\" value=\"manual_challenge\"/>
    </noscript>\n";
  } // end of render()
  
  public function verify($rc)
  {
    if (!$this->valid) {
      return TRUE;
    }
    if (!isset($rc->safe_post_recaptcha_challenge_field)) {
      return FALSE;
    }
    require_once('acurl.php');
    $acurl = new ACurl(ReCaptcha::RECAPTCHA_VERIFY_SERVER, 'reCAPTCHA/PHP', $this->https);
    $rsp = $acurl->post_query('/verify',
        "privatekey", $this->private_key,
        'remoteip', $_SERVER['REMOTE_ADDR'],
        'challenge', $rc->raw_post_recaptcha_challenge_field,
        'response', $rc->raw_post_recaptcha_response_field
        );
    
    // return response
    if ($rsp) {
      list($success, $this->error_code) = preg_split('/\s+/', $rsp); // for now
      $this->success = strtolower($success) == 'true';

      return $this->success;
    } else {
      return ($this->success = FALSE);
    }
  } // end of verify()
}
?>
