<?php
/*
#doc-start
h1.  Subscription.php - Encapsulates Newletter Subscription

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


#end-doc
*/

// global variables
require_once('aclass.php');

$test_subscription_values = array(
  'name' => 'Subscriber Name',
  'email' => 'email@address',
  'active' => 'N',
  'start_date' => '2009-12-31',
  'end_date' => '2010-2-28',
  'cancel_reason' => '1',
  );
AClass::define_class('Subscription', 'email',
  array( // attribute defintions
    array('email', 'email', 'Email Address'),
    array('userid', 'varchar(255)', 'Account Userid'),
    array('name', 'varchar(255)', 'Subscriber Name'),
    array('active', 'enum(Y,N)', 'Active'),
    array('start_date', 'date', 'Start Date'),
    array('end_date', 'date', 'End Date'),
    array('include_pics', 'enum(Y,N)', 'Include Pictures'),
    array('cancel_reason', 'enum(1,2,3,4)', 'Cancellation Reason: 1-Boring,2-Content Bad,3-Feet Smell,4-Other'),
  ), NULL);
// end global variables

// class definitions
class Subscription extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Subscription', $dbaccess, $attribute_values);
    if (!isset($this->start_date)) {
      $this->start_date = new DateTime('now');
      $this->active = 'Y';
    }
    switch ($this->active) {
      case 'Y':
        $this->put_prop('end_date', 'invisible');
        $this->put_prop('cancel_reason', 'invisible');
        break;
      case 'N':
        $this->del_prop('end_date', 'invisible');
        $this->del_prop('cancel_reason', 'invisible');
        break;
    }
  } // end of __construct()
  
  public function subscribe($cookietrack)
  {
    $ajoin = AJoin::get_ajoin(Globals::$dbaccess, 'Subscription', 'CookieTrack');
    $ajoin->add_to_join($this, $cookietrack);
  } // end of subscribe()
  
  public function cancel($reason)
  {
    if (!isset($this->end_date)) {
      $this->end_date = new DateTime('now');
    }
    $this->cancel_reason = $reason;
    $this->active = 'N';
    $this->del_prop('end_date', 'invisible');
    $this->del_prop('cancel_reason', 'invisible');
    $ajoin = AJoin::get_ajoin(Globals::$dbaccess, 'Subscription', 'Account');
    // FIXME: If we drop this link, we will be orphaning the subscription
//    $ajoin->delete_from_join($this, $account); 
  } // end of cancel()
}

class SubscriptionManager extends AManager {
  public function __construct($dbaccess, $account = NULL)
  {
    if (!class_exists('Account')) require_once('Account.php');
    parent::__construct($dbaccess, 'Subscription', 'name',
      array('expose_select' => ($account instanceof Account && ($account->authority == 'X' || $account->authority == 'S'))));
  } // end of __construct()
}
// end class definitions
?>
