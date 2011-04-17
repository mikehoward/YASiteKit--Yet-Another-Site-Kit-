<?php
/*
#doc-start
h1.  ManageSubscription.php - Newsletter Subscription Administrative Management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Subscription Management";
Globals::$page_obj->page_title = "Subscription Management";
Globals::$page_obj->form_action = 'ManageSubscription.php';
Globals::$page_obj->required_authority = 'C';

require_once('Account.php');
ObjectInfo::do_require_once('Subscription.php');

function render_email_req_form($msg = NULL)
{
?>
  <p class="larger sans-serif">Please specify your e-mail address so we can get your subscription information:</p>
<?php if ($msg): ?>
  <p><span class="error-formatable"><?php echo $msg; ?></span></p>
<?php endif;  // msg ?>
  <form action="ManageSubscription.php" method="post" accept-charset="utf-8">
   <p> <input type="text" name="email" value="<?php echo Globals::$rc->safe_post_email; ?>"
       maxlength="255" size="40">
    <input name="submit" type="submit" value="Get Subscription Information"></p>
  </form>
<?php
} // end of render_email_req_form()

$authority = Globals::$account_obj instanceof Account ? Globals::$account_obj->authority : '-';
$manager_obj = new SubscriptionManager(Globals::$dbaccess, Globals::$account_obj);

switch ($authority) {
  case 'S':
  case 'X':
    echo $manager_obj->render_form(Globals::$rc);
    break;
  default:
    if (isset(Globals::$rc->safe_post_email)) {
      $obj = new Subscription(Globals::$dbaccess);
      if ($obj->instance_existsP(Globals::$rc->safe_post_email)) {
        echo $manager_obj->render_form(Globals::$rc);
      } else {
        render_email_req_form("We don't have a subscription listed for the email address &ldquo;"
          . Globals::$rc->safe_post_email . "&rdquo; - please submit a correct address");
      }
    } else {
      render_email_req_form();
    }
    break;
}
?>
