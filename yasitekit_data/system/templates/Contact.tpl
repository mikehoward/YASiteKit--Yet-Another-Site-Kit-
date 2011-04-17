<?php
/*
#doc-start
h1.  Contact.tpl - used to send us e-mail or provide our phone number.

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/
?>
{:php-setup:}
  $page_header = Globals::$site_name . " - Contact Form";
  $page_title = "Contact Form";
{:end-php-setup:}
{:php-prefix:}
require_once('Message.php');
$msg = new Message(Globals::$dbaccess);
{:end-php-prefix:}

<?php function display_form($msg) { ?>
  <div class="padded">
    <p>The best way contact us via e-mail, so please send us a message:</p>
<?php echo $msg->form(); ?>
  </div>
<?php } // end display_form() ?>

<?php
  switch (Globals::$rc->safe_post_submit){
    case 'Send':
      $msg->process_form(Globals::$rc);
      if ($msg->mail(Globals::$info_email)) {
?>
  <p>Thanks {:$msg->name:} for taking the time to send us a message.</p>
<?php
      } else {
        Globals::add_message("Message Send Failed");
        display_form($msg);
      }
      break;
    case 'Cancel':
      Globals::add_message("Message Canceled");
      $msg->process_form(Globals::$rc);
      display_form($msg);
      break;
    default:
      display_form($msg);
      break;
  } 
?>