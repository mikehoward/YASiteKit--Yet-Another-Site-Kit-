<?php
/*
#doc-start
h1.  CookieTrack.php - Infrastructure for gather visitation data

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

CookieTrack objects provide minimal persistence for users across
sessions.

h2. Instantiation

The CookieTrack object is usually instantiated in _includes.php_ prior
to any request processing and stored in *Globals::$cookie_track*.

h2. Attributes

* cookie - string - value of cookie
* first_access - datetime - timestamp of first access for this cookie
* prev_access - datetime - timestamp of the second most recent access
* latest_access - datetime - timestamp of the most recent access

h2. Class Methods

None

h2. Instance Methods

All the default AnInsance instance methods

#end-doc
*/

// global variables
require_once('aclass.php');

$test_cookietrack_values = array(
  'cookie' => 'COOKIEVALUE',
  );
AClass::define_class('CookieTrack', array('cookie'), array(
  array('cookie', 'varchar(40)', 'Cookie'),
  array('first_access', 'datetime', 'First Access'),
  array('prev_access', 'datetime', 'Previous Access'),
  array('latest_access', 'datetime', 'Last Access'),
  ), NULL);
// end global variables

// class definitions
class CookieTrack extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('CookieTrack', $dbaccess, $attribute_values);

    // if this is a real cookie which has been previously used, then update timestamps
    if (isset($this->cookie)) {
      if (isset($this->latest_access)) {
        $this->prev_access = $this->latest_access;
      }
      $this->latest_access = new DateTime('now');
      if (!isset($this->first_access)) $this->first_access = new DateTime('now');
    }
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'time_between_requests':
        if ($this->latest_access instanceof DateTime && $this->prev_access instanceof DateTime) {
          $this_access = $this->latest_access->format('U');
          $prev_access = $this->prev_access->format('U');
          return $this_access - $prev_access;
        } else {
          return FALSE;
        }
        // not reached
      default:
        return parent::__get($name);
    }
  } // end of __get()
  
  public function save()
  {
    $this->latest_access = new DateTime('now');
    parent::save();
  } // end of save()
}
// end class definitions


// function definitions
?>
