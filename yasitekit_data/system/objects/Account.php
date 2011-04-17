<?php
/*
#doc-start
h1.  Account.php - Account Object

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module defines 2 classes:

* Account - which manages user, artist, staff, and administrator accounts
* AccountManager - which manages the form used for managing account records.

h1. Account Class

The account class holds all the information necessary to identify an account
and control access to various parts of the site

h2. Attributes

* userid - string - account userid - must be unique in the system
* password - string - salted hash of user's passsword
* salt - char(2) - salt - randomly generated - read only
* name - string - user's name
* email - string - user's email address
* cookie - string - value of cookie in current, or latest, session
* prev_access - DateTime - timestamp of previous access
* latest_access - DateTime - timestamp of current access
* authority - char(1) - Authority token - C-Customer, M-Merchant, W-Author,
A-Artist, S-Staff, X-Administrator
* state - char(1) - State token - A-Active, L-Locked, D-Disabled
* failed_login_attempts - int - number of consecutive failed login attempts. Reset
to 0 upon successful login


h2. Class Methods

* cmp_latest_access(left, right) - returns -1, 0, or 1 depending if accounts _left_
has a latest_access time earlier, the same, or later than _right_. Throws exception
if either are not Account instances. Returns 0 if same account.
* existP(dbaccess, attr_ar) - returns TRUE if the Account object exists in
database _dbaccess_ which corresponds to the attributes in _attr_ar_. This is
a convenience method which calls AnInstance::existP()
* list_of_cookied_accounts(dbaccess, cookie_track) - returns array of accounts which are joined
to the CookieTrack instance. Sorted in by latest_access - soonest at head of list.
Throws exception if _cookie_track_ is not a CookieTrack. _dbaccess_ is an instance of
DBAccess.

h2. Instance Methods

* select_account($element_name, $selected, $classes = NULL, $attributes = NULL) -
returns a _select_ element populated by all the accounts in the system. The account
with _userid_ == _$selected_ is marked 'selected'. Arguments _$classes_ and _$attributes_
are added to the openning _select_ tag.
* process_form() - processes results of an account edit taking into account the authority
of the logged in account. This allows Staff and Administrators to modify other accounts,
but restricts normal accounts to only changing their passwords.
* set_password($plain_text_password) - sets the password to the supplied plain text
password.
* verify_password(plain-text-password) - hashes arg and compares with Account value.
Returns TRUE if same, else FALSE
* do_failure_pause() - causes the program to sleep a specified time which increases
with each failed login attempt.
* increment_failed_login_attempts() - increments the _failed_login_attempts_ counter
and pauses a quadratically increases number of seconds before returning
* set_state(new_state) - sets the state to one of A, L, or D or throws an exception.
* has_authority(required_authority) - returns Boolean TRUE or FALSE -
returns TRUE if _required_authority_ is false.
Otherwise, _required_authority_ is either a string containing a comma separated list
of authority values OR and array of authority values. In this case _has_authority()_
only returns TRUE if:
** account authority is X
** or account authority is S and _required_authority_ is anything but a single X
** or required_authority is C and account authority is anything
** or the account authority is listed in the _required_authority_
* logged_in() - return TRUE or FALSE [peeks at Globals::$session_obj->logged_in]
* login() - marks this account as logged in. Clears various failure counts and forces
Globals::$account_obj to be _this_
* logout - marks this account as not logged in.
* dump(msg) - addes the login state of the account to the normal AnInstance dump()
output.

h1. AccountManager Class

The account manager class extends the AManager class and manages hand editting
Account data.

h2. Attributes

None

h2. Class Methods

None

h2. Instance Methods

* render_form($rc) - extends and specializes the AManager _render_form()_ method
to account classes. It exposes appropriate fields depending on the authority
of the class of account Globals::$account_obj. It also guards against account
editting by non-logged in users.

#end-doc
*/

// global variables
require_once('aclass.php');

// end global variables

// class definitions

$test_account_values = array(
  'userid' => 'uid',
  'password' => 'password',
  'name' => 'Account Name',
  'cookie' => 'Cookie Value',
  'latest_access' => '2010-02-15 14:30:32',
  'authority' => 'W',
  );
AClass::define_class('Account', 'userid',
  array(
    array('userid', 'varchar(40)', 'UserId'),
    array('password', 'varchar(255)', 'Password'),
    array('salt', 'char(2)', 'Salt'),
    array('name', 'varchar(255)', 'Name'),
    array('email', 'email', 'Email Address'),
    array('email_addresses', 'join(Email.email,multiple)', 'All Email Addresses'),
    array('addresses', 'join(Address.address_name,multiple)', 'All Addresses'),
    array('cookie', 'varchar(255)', 'Cookie'),
    array('prev_access', 'datetime', 'Previous Access'),
    array('latest_access', 'datetime', 'Last Access'),
    array('authority', 'enum(C,M,W,A,S,X)', 'Authority - C=Customer,M=Merchant,W=Author,A=Artist,S=Staff,X=Admin'),
    array('state', 'enum(A,L,D)', 'State - Active,Locked,Disabled'),
    array('failed_login_attempts', 'int', 'Successive Failed Login Attempts'),
  ),
  array(
    'userid' => 'public',
    'salt' => array('invisible', 'immutable'),
    'password' => array('invisible', 'encrypt'),
    'cookie' => array('invisible', 'encrypt'),
    'prev_access' => array('readonly', 'public'),
    'email' => 'encrypt',
    'latest_access' => array('readonly', 'public'),
    'name' => 'encrypt',
    'failed_login_attempts' => 'readonly',
    'state' => 'readonly',));

class AccountException extends Exception {}

class Account extends AnInstance {
  static private $salt_chars = '!"#$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz{}';
  public function __construct($dbaccess, $attribute_values = NULL)
  {
    parent::__construct('Account', $dbaccess, $attribute_values);
    // this dance makes the access time code work when there are multiple
    //  instantiations in the same page request.
    if (!$this->failed_login_attempts) {
      $this->failed_login_attempts = 0;
    }
    if ($this->latest_access != ($now = new DateTime('now'))) {
      if (isset($this->latest_access)) $this->prev_access = $this->latest_access;
      $this->latest_access = $now;
    }
    if (!isset($this->salt)) {
      $this->salt = Account::$salt_chars[rand(0,strlen(Account::$salt_chars)-1)]
        . Account::$salt_chars[rand(0,strlen(Account::$salt_chars)-1)];
    }
    // if ($this->authority != 'S' && $this->authority != 'X') {
    //   foreach (array('userid', 'authority',) as $attr) {
    //       $this->put_prop($attr, 'readonly');
    //   }
    // }
  } // end of __construct()

  public function __get($name)
  {
    return parent::__get($name);
  } // end of __get()
  
  public static function existsP($dbaccess, $attr_ar = array())
  {
    return AnInstance::existsP('Account', $dbaccess, $attr_ar);
  } // end of existsP()
  
  public static function cmp_latest_access($left, $right)
  {
    if (!($left instanceof Account) || !($right instanceof Account)) {
      throw new AccountException("Account::cmp_latest_access(): argument error: one or both args are not Accounts");
    }
    // is right the same as left?
    if ($left->userid == $right->userid) return 0;
    $left_access_time = $left->latest_access->format('U');
    $right_access_time = $right->latest_access->format('U');
    return $left_access_time == $right_access_time ? 0 : ( $left_access_time < $right_access_time ? -1 : 1);
  } // end of cmp_latest_access()

  // returns list of Account objects joined to the supplied CookieTrack instance
  //  in reverse order of last_access.
  public static function list_of_cookied_accounts($dbaccess, $cookietrack)
  {
    if (!($cookietrack instanceof CookieTrack)) {
      throw new AccountException('Account::list_of_cookied_accounts(cookietrack): cookietrack is not a CookieTrack instance');
    }
    $ajoin = AJoin::get_ajoin($dbaccess, 'Account', 'CookieTrack');
    $list = $ajoin->select_joined_objects($cookietrack);
    if (is_array($list)) {
      usort($list, array('Account', 'cmp_latest_access'));
      return count($list) > 1 ? array_reverse($list) : $list;
    } else {
      return array();
    }
  } // end of list_of_cookied_account()
  
  public function select_account($element_name, $selected, $classes = NULL, $attributes = NULL)
  {
    $account_list = Account::get_objects_where(NULL, 'order by name');
    $ar = array("<select name=\"$element_name\""
      . ($classes ? " class=\"$classes\"" : '')
      . ($attributes ? " $attributes" : '')
      . ">");
    foreach ($account_list as $account) {
      $selected_attribute = $account->userid == $selected ? 'selected' : '';
      $ar[] = "<option value=\"$account->userid\" $selected_attribute>$account->name</option>";
    }
    $ar[] = "</select>";
    return implode("    " . "\n", $ar);
  } // end of select_account()


  public function process_form($rc)
  {
    parent::process_form($rc);
    if (isset($rc->safe_post_new_password)
      && isset($rc->safe_post_new_password_check)
      && $rc->safe_post_new_password == $rc->safe_post_new_password_check) {
      switch (Globals::$account_obj->authority) {
        case 'S':
          if (!isset($rc->safe_post_old_password) || !Globals::$account_obj->verify_password($rc->safe_post_old_password)) {
            break;
          }
          // intentional Fall Through
        case 'X':
          $acnt = new Account($this->dbaccess, $this->decode_key_values($rc->safe_post_key_array));
          if ($rc->safe_post_unlock_account == 'Y') {
            $acnt->set_state('A');
            $acnt->failed_login_attempts = 0;
          }
          $acnt->set_password($rc->safe_post_new_password);
          $acnt->save();
          break;
        default:
          if (isset($rc->safe_post_old_password) && Globals::$account_obj->verify_password($rc->safe_post_old_password)) {
            Globals::$account_obj->set_password($rc->safe_post_new_password);
            Globals::$account_obj->save();
          }
          break;
      }
    }

    // unlock account logic
    if (($this->authority == 'X' || $this->authority == 'S') && $rc->safe_post_unlock_account == 'Y') {
      $acnt = new Account($this->dbaccess, $this->decode_key_values($rc->safe_post_key_array));
      $acnt->set_state('A');
      $acnt->failed_login_attempts = 0;
      $acnt->save();
    }
  } // end of process_form()
  
  public function dump($msg = '')
  {
    if (Globals::$session_obj instanceof Session) {
      $msg .= "\n" . ($this->logged_in() ? "Logged In\n" : "Not Logged In\n");
    }
    return parent::dump($msg);
  } // end of dump()
  
  // Password Handling
  private function compute_password($plain_text_password)
  {
    return hash('md5', $this->salt . $plain_text_password . $this->salt);
  } // end of compute_password()
  
  public function set_password($plain_text_password)
  {
    $this->password =  $this->compute_password($plain_text_password);
  } // end of set_password()

  public function verify_password($plain_text_password)
  {
//    Globals::$session_obj->add_message("verify_password(): plain_text_password: '$plain_text_password'");
//    Globals::$session_obj->add_message("verify_password(): computed password: " . $this->compute_password($plain_text_password));
//    Globals::$session_obj->add_message("verify_password(): from db: $this->password");
    return $this->compute_password($plain_text_password) == $this->password;
  } // end of verify_password()
  
  public function do_failure_pause()
  {
    sleep($this->failed_login_attempts * $this->failed_login_attempts / 4);
  } // end of do_failure_pause()
  
  public function increment_failed_login_attempts()
  {
    $this->failed_login_attempts += 1;
    $this->save();
    $this->do_failure_pause();
    return $this->failed_login_attempts;
  } // end of increment_failed_login_attempt()

  public function has_authority($req_authority)
  {
    if (!$req_authority) {
      return TRUE;
    }
    if (is_string($req_authority)) {
      $req_authority = explode(',', $req_authority);
    }
    if (in_array('ANY', $req_authority)) {
      return TRUE;
    }
    switch ($this->authority) {
      case 'X': return TRUE;
      case 'C': return in_array('C', $req_authority);
      case 'S': return $req_authority != array('X');
      default:
        return $req_authority == 'C' || in_array($this->authority, $req_authority);
    }
  } // end of has_authority()
  
  // state handling
  public function set_state($new_state)
  {
    switch ($new_state) {
      case 'A': case 'L': case 'D':
      case 'a': case 'l': case 'd':
        $this->state = strtoupper($new_state);
        break;
      default:
        throw new AccountException("Account::set_state($new_state): Illegal new state value");
    }
    $this->save();
  } // end of set_state()
  
  public function logged_in()
  {
    return isset(Globals::$session_obj->logged_in) && Globals::$session_obj->logged_in == 'Y';
  } // end of logged_in()
  
  public function login()
  {
    if ($this->state != 'A') {
      return;
    }
    Globals::$account_obj = $this;
    Globals::$session_obj->logged_in = 'Y';
    Globals::$session_obj->clear_failure_count();
    Globals::$session_obj->userid = $this->userid;
    $this->failed_login_attempts = 0;
    $this->save();
    
    if (Globals::$cookie_track instanceof CookieTrack) {
      $ajoin = AJoin::get_ajoin(Globals::$dbaccess, 'Account', 'CookieTrack');
      if (!$ajoin->in_joinP($this, Globals::$cookie_track)) {
        $ajoin->add_to_join($this, Globals::$cookie_track);
      }
    }
  } // end of login()
  
  public static function logout()
  {
    Globals::$session_obj->logged_in = 'N';
    // I think the next line is an error
    // unset(Globals::$session_obj->userid);
  } // end of logout()
}

class AccountManagerException extends Exception {}

class AccountManager extends AManager {
  public function __construct($dbaccess, $account = NULL)
  {
    if (!$account || !($account instanceof Account)) {
      throw new AccountManagerException("AccountManager::__construct(): cannot be called w/o account");
    }
    $this->account = $account;
    parent::__construct($dbaccess, 'Account', 'name',
      array('expose_select' => ($account->authority == 'X' || $account->authority == 'S')));
  } // end of __construct()

  public function render_form($rc)
  {
    if (!$this->account->logged_in()) {
      Globals::$session_obj->add_message("Sorry {$this->account->name}, you must be logged in to access this function");
    }
   switch (Globals::$account_obj->authority) {
      case 'C':
      case 'A':
        $rc->safe_post_key_array = Globals::$account_obj->encode_key_values();
        $field_array = array(array('old_password', 'Current Password'),
            array('new_password', 'New Password'),
            array('new_password_check', 'Repeat New Password'));
        $include_unlock_account = FALSE;
        break;
      case 'S':
        if (!isset($rc->safe_post_key_array))
          $rc->safe_post_key_array = Globals::$account_obj->encode_key_values();
        $include_unlock_account = Globals::$rc->safe_post_state != 'A';
        $field_array = array(array('old_password', 'Your Password'),
          array('new_password', 'New Password'),
          array('new_password_check', 'Repeat New Password'));
        break;
      case 'X':
        if (!isset($rc->safe_post_key_array))
          $rc->safe_post_key_array = Globals::$account_obj->encode_key_values();
        $include_unlock_account = Globals::$rc->safe_post_state != 'A';
        $field_array = array(array('new_password', 'New Password'),
            array('new_password_check', 'Repeat New Password'));
        break;
      default:
        IncludeUtilities::redirect_to('/page_access_denied.php', basename(__FILE__) . ':' . __LINE__);
    }

    $top = '';
    if ($include_unlock_account) {
      $bottom = "<li>
        <input type=\"checkbox\" name=\"unlock_account\" value=\"Y\" style=\"clear:both;float:right\">
        <label for=\"unlock_account\">Unlock Account? (check box on right)</label>
      </li>\n";
    } else {
      $bottom = '';
    }
    $bottom .= "<li>To Change Password, fill in the following lines</li>\n";
    foreach ($field_array as $row) {
      list($attr, $title) = $row;
      $bottom .= "<li style=\"clear:both\">
        <input type=\"password\" name=\"$attr\" class=\"password\" style=\"clear:both;float:right\" id=\"$attr\" value=\"\">\n
        <label for=\"$attr\" >{$title}</label>
      </li>\n";
    }


    parent::render_form($rc, $top, $bottom);
  } // end of render_form()
}
// end class definitions
?>
