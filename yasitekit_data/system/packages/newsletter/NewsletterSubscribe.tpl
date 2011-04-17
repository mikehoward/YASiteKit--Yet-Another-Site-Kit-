<?php
/*
#doc-start
h1.  NewsletterSubscribe.php - Subscribe to Newsletter Form

Created by  on 2010-04-26.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/
?>
{:style:}
#register fieldset {
  background-color: #dddddd;
  color:black;
}
#register input[type=submit] {
  background-color:green;
/*  font-size:larger; /* */
  padding:.3em;
  color:#ffffdd;
  margin-left:.5em;
  margin-right: .5em;
}
{:end-style:}
{:php-setup:}
global $pate_title, $page_header;
$page_title = Globals::$site_name . ' - Create an Account';
$page_header = 'Create an Account';
{:end-php-setup:}

<?php
function unsubscribe()
{
  $subscription_obj = new Subscription(Globals::$dbaccess);
  if ($subscription_obj->instance_existsP(Globals::$rc->safe_request_email)) {
    $subscription_obj = new Subscription(Globals::$dbaccess, Globals::$rc->safe_request_email);
    if ($subscription_obj->active == 'Y') {
      $subscription_obj->active = 'N';
      $subscription_obj->cancel_reason = Globals::$rc->safe_request_cancel_reason;
      $subscription_obj->save();
      echo "<p>OK - we've deactivated your subscription.</p>\n";
      echo "<p>Sorry to see you go . . . and hope you come back</p>\n";
    } else {
      echo "<p>It looks like we've already cancelled the subscription for this email
      address. If you really are receiving it, please send us a note
      now.</p>\n";
      require_once("Message.php");
      $msg_obj = new Message(Globals::$dbaccess);
      echo $msg_obj->form();
    }
  } else {
    echo "<p>We're sorry, but we have no record of your having a subscription to our
    Newsletter using this email address. If you really are receiving it, please send us a note
    now.</p>\n";
    require_once("Message.php");
    $msg_obj = new Message(Globals::$dbaccess);
    echo $msg_obj->form();
  }
} // end of unsubscribe()

function process_subscription()
{
  if (!Globals::$rc->safe_post_email) {
    Globals::add_message('Please fill in your email address');
    return FALSE;
  }

  ObjectInfo::do_require_once('Subscription.php');
  $subscription_obj = new Subscription(Globals::$dbaccess, Globals::$rc->safe_post_email);
  $subscription_obj->userid = NULL;
  $subscription_obj->name = Globals::$rc->safe_post_name;
  $subscription_obj->active = 'Y';
  $subscription_obj->start_date = new DateTime('now');
  $subscription_obj->end_date = NULL;
  $subscription_obj->include_pics = Globals::$rc->safe_post_include_pictures;
  $subscription_obj->cancel_reason = '0';
  $subscription_obj->save();
?>
  <h2>Thank you</h2>
  <p>Thank you for subscribing to our newsletter. We will make sure you get the
    next one we put out.</p>
    
  <p>We archive all of our newsletters
    <a style="text-decoration:underline;color:#8888ff;font-size:larger" href="/Newsletters.php">here</a> so they are
    always available to read.</p>
<?php

  return TRUE;
} // end of process_subscription()

function display_subscription_form()
{
  global $page_title;
  global $page_header;
  $page_title = Globals::$site_name . " - Newsletter Subscription Form";
  $page_header = Globals::$site_name . " - Newsletter Subscription Form";
?>
  <h2>Newsletter Subscription Form</h2>
 <form id="register" action="Register.php" method="post" accept-charset="utf-8">
   <p class="larger">Thank you for wanting to keep track of us.</p>
   <p class="larger">Please fill in your email address, your name (so we know who to address it to),
     and whether or not you'd like
     us to include small pictures of our new works in the newsletter. They give you
     a preview before going to our web site and don't take
     much space - so it shouldn't hurt to leave Yes checked.
     </p>

   <p class="larger">Click <input type="submit" name="submit" value="Subscribe"> when you're
     ready.</p>


       <p class="larger">Don't expect more than one Newsletter every month or two.
         </p>

      <fieldset>
         <input class="float-right" type="text" name="email" value="<?php echo Globals::$rc->safe_post_email; ?>" id="email" maxlength="255" size="40">
         <label for="email">Subscribe by typing your Email Address</label>
       </fieldset>

       <fieldset>
        <input class="float-right" type="text" name="name" value="<?php echo Globals::$rc->safe_post_name ?>" id="name" maxlength="255" size="40">
        <label for="name">Your Name</label>
       </fieldset>

       <fieldset>
         <span class="float-right">
           <span style="border-right:white solid 2px;padding-right:.3em">
             <input type="radio" name="include_pictures" value="Y" 
             <?php echo !Globals::$rc->safe_post_include_pictures || Globals::$rc->safe_post_include_pictures == 'Y' ? 'checked':''; ?>>
             Yes - include them
           </span>
           <input type="radio" name="include_pictures" value="N"
             <?php echo !Globals::$rc->safe_post_include_pictures && Globals::$rc->safe_post_include_pictures == 'N' ? 'checked':''; ?>>
             No - just the news and links
           </span>
           <label for="include_pictures">Include Pictures in the Newsletter?</label>
       </fieldset>
       
       <br>

       <fieldset>
         <textarea class="float-right rte" name="aboutness" rows="10" cols="60">
           <?php echo Globals::$rc->safe_post_aboutness ?>
         </textarea>
         <label for="aboutness">We'd like to know you
           a little better too, so if you have time, please write a little about yourself.</label>
       </fieldset>

   <p>Click <input type="submit" name="submit" value="Subscribe"> when your ready.</p>
   <br>
 </form>

<?php
} // end of display_subscription_form()



// end function definitions

// initial processing of POST data

// dispatch actions

switch (Globals::$rc->safe_request_submit) {
  case 'unsubscribe':
  case 'Unsubscribe':
    unsubscribe();
    break;
  case 'Subscribe':
    if (!process_subscription()) {
      display_subscription_form();
    }
    break;
  case 'subscribe':
  default:
    display_subscription_form();
    break;
}
?>