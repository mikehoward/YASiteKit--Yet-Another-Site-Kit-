<?php
/*
#doc-start
h1.  Login.php - Login screen

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This page content manages the login process.

An Account is logged in if Globals::$session_obj->userid has a valid value and
the account has not timed out.

#end-doc
*/

Globals::$page_obj->page_header = Globals::$site_name . " - Login Please";
Globals::$page_obj->page_title = "Login Form";
Globals::$page_obj->form_action = "Login.php";
Globals::$page_obj->required_authority = FALSE;

require_once('request_cleaner.php');
require_once('Account.php');
require_once('session.php');
require_once('ReCaptcha.php');
require_once('Message.php');

// verify we have session object
if (!(Globals::$session_obj instanceof Session)) {
  Globals::add_message('Sorry, you must accept cookies in order to log in');
  IncludeUtilities::redirect_to('/index.php',  basename(__FILE__) . ":" . __LINE__ . "\n");
}

// function definitions

function display_login_form($userid, $recaptcha = NULL)
{
  if (!$recaptcha) {
    $recaptcha = new ReCaptcha(Globals::$recaptcha_domain, Globals::$recaptcha_pub_key,
      Globals::$recaptcha_priv_key, Globals::$recaptcha_theme, ReCaptcha::HTTP);
  }
?>
  <div id="login-form">
    <div class="padded">
  <form action="Login.php" method="post" accept-charset="utf-8">
    <ul>
      <fieldset>
      <li>
        <input id="userid" class="varchar first-focus" style="clear:both;float:right" type="text"
          name="userid" value="<?php echo $userid; ?>" maxlength="40" size="40">
        <label for="userid">User Id</label>
      </li>
      <li>
        <input type="password" class="varchar" style="clear:both;float:right" name="password" value="" id="password"
            maxlength="40" size="40">
        <label for="password">Password: </label>
      </li>
  <?php
    if (!$recaptcha->verify(Globals::$rc)) {
      if ($recaptcha->error_code) {
        echo "<li class=\"red\">$recaptcha->error_code</li>\n";
      }
      echo "<li class=\"recaptcha-block clear float-right\">{$recaptcha->render()}</li>\n";
    }
  ?>
      </fieldset>

      <li><input type="submit" name="submit" value="Submit"></li>
    </ul>
  </form>
    </div>
  </div>
<?php
} // end of display_login_form()

function display_email_form($subject, $account_obj)
{
  require_once('Message.php');
  
  $message_obj = new Message(Globals::$dbaccess, ($account_obj && $account_obj->email ? $account_obj->email : NULL));
  $message_obj->subject = $subject;
  echo "<h1>Email Contact Form</h1>\n";
  echo "<p>If you would like to email us regarding this, please fill out this e-mail form</p>\n";
  echo $message_obj->form();
} // end of display_email_form()

function display_message($msg)
{
  echo "<p>$msg</p>\n";
} // end of display_thank_you()

// end function definitions

// echo Globals::$rc->dump('Login.php' . basename(__FILE__) . ':' . __LINE__);
switch (Globals::$rc->safe_post_submit) {
  case 'Send':
    $message_obj = new Message(Globals::$dbaccess);
    if ($message_obj->process_form(Globals::$rc)) {
      if ($message_obj->mail(Globals::$support_email)) {
        display_message("Thank You - your email has been accepted by the system - you should hear something within two days");
      } else {
        display_message("We're sorry, but your email was not accepted by the system - please try again later");
      }
    } else {
      Globals::$session_obj->add_message("No Email message could be constructed - please try again later");
      IncludeUtilities::redirect_to('/index.php', basename(__FILE__) . ':' . __LINE__);
    }
    break;
  case 'Submit':
    // check to see if this is a login to a different account
    if (!isset(Globals::$rc->safe_post_userid) || !Globals::$rc->safe_post_userid) {
      Globals::add_message("Userid is Required - please try again");
      Globals::$session_obj->increment_failure_count();
      Globals::$session_obj->anti_dos_delay();
      display_login_form(Globals::$rc->safe_post_userid,NULL);
      // terminate this chunk of the switch statement
      break; 
    }

    // get account object corresponding to login userid
    if (!Account::existsP(Globals::$dbaccess, Globals::$rc->safe_post_userid)) {
      Globals::add_message("Bad Userid: '" . Globals::$rc->safe_post_userid . "' - please try again");
      Globals::$session_obj->increment_failure_count();
      Globals::$session_obj->anti_dos_delay();
      display_login_form(Globals::$rc->safe_post_userid, FALSE);
      // terminate this chunk of the switch statement
      break; 
    }
    
    // if this is a change of user accounts, then logout
    if (Globals::$session_obj->userid != Globals::$rc->safe_post_userid) {
      Account::logout();
      unset(Globals::$session_obj->userid);
    }
    
    // finally, we can start to log in
    $account_obj = new Account(Globals::$dbaccess, Globals::$rc->safe_post_userid);
    // echo $account_obj->dump('Login.php' . basename(__FILE__) . ':' . __LINE__);
    switch ($account_obj->state) {
      case 'A':
      // echo "account_obj->failed_login_attempts: " . $account_obj->failed_login_attempts . "\n";
      // echo "Globals::max_failed_login_attempts: " . Globals::$max_failed_login_attempts . "\n";
      // echo "Globals::session_obj->failure_count: " . Globals::$session_obj->failure_count . "\n";
        if ($account_obj->failed_login_attempts > Globals::$max_failed_login_attempts) {
          $account_obj->set_state('L');
          Globals::$session_obj->add_message("You have exceeded the maximum number of attempted logins, so we have locked your account - apologies for the inconvenience");
          $account_obj->do_failure_pause();
          IncludeUtilities::redirect_to('/index.php', basename(__FILE__) . ':' . __LINE__);
        } else {
          $recaptcha = new ReCaptcha(Globals::$recaptcha_domain, Globals::$recaptcha_pub_key,
            Globals::$recaptcha_priv_key, Globals::$recaptcha_theme, ReCaptcha::HTTP);
          if (!$recaptcha->verify(Globals::$rc)) {
            Globals::add_message("Please Retry the reCAPTCHA Test");
            // Globals::add_message(Globals::$rc->dump('check recaptcha data'));
            $account_obj->increment_failed_login_attempts();
            display_login_form($account_obj->userid, $recaptcha);
          } elseif (!$account_obj->verify_password(Globals::$rc->safe_post_password)) {
            if ($account_obj->failed_login_attempts > Globals::$max_failed_login_attempts) {
              $account_obj->set_state('L');
              Globals::$session_obj->add_message("You have exceeded the maximum number of attempted logins, so we have locked your account - apologies for the inconvenience");
              IncludeUtilities::redirect_to('/index.php', basename(__FILE__) . ':' . __LINE__);
            }
            $account_obj->increment_failed_login_attempts();
            Globals::add_message("Unrecognized User ID and/or Password: ");
            display_login_form($account_obj->userid, $recaptcha);
          } else {
            $account_obj->login();
            if (isset(Globals::$session_obj->reserved_page_name)) {
              $diversion_page = Globals::$session_obj->reserved_page_name;
              unset(Globals::$session_obj->reserved_page_name);
            } else {
              $diversion_page = '/index.php';
            }
            IncludeUtilities::redirect_to($diversion_page, basename(__FILE__) . ':' . __LINE__);
          }
        }
        break;
      case 'L':
        Globals::add_message("We're sorry, but your account is Locked - which means someone tried to log in too many times and failed. This often an attempt to hack an account.");
        display_email_form("Account '{$account_obj->userid}' Locked", $account_obj);
        break;
      case 'D':
      default:
        Globals::add_message("We're sorry, but your account is not active. If this is an error, please send us an e-mail");
        display_email_form("Account '{$account_obj->userid}' Locked", $account_obj);
        break;
    }
    break;
  default:
    display_login_form('', NULL);
    break;
}

// echo $javascript_seg->dump();
?>
