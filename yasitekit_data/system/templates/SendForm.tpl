{:php-setup:}
$page_header = Globals::$site_name . " - Send Message Confirmation";
$page_title = "Send Message Confirmation";
{:end-php-setup:}
<?php
function send_message()
{
  if (Globals::$rc->safe_post_email != Globals::$rc->safe_post_email_check || 
      !mail($toaddress, Globals::$rc->safe_post_subject, Globals::$rc->safe_post_message,
          "From: " . Globals::$rc->safe_post_->email . "\r\nX-OrderNumber: " . Globals::$rc->safe_post_order_no)) {
    Globals::$rc->safe_post_error_message = "Message Not Sent - please check your values";
    ObjectInfo::do_require_once(Globals::$rc->safe_get_from);
    return;
  }

} // end of send_message()

// end function definitions
?>
<div class="box content">
  <div class="padded">
    <p>Hi {:Globals::$rc->safe_post_name:},</p>
    <p>Thanks for sending us a message about {:Globals::$rc->safe_post_subject:}</p>
  </div>
</div>
