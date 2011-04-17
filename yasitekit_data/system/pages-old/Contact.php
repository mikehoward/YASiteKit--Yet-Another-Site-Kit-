<?php
/*
#doc-start
h1.  Contact.tpl - used to send us e-mail or provide our phone number.

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Contact Form";
Globals::$page_obj->page_title = "Contact Form";
Globals::$page_obj->form_action = 'SendForm.tpl';
// dispatch actions
?>
  <div class="padded">
    <p>The best way contact us via e-mail, so please send us a message:</p>
<?php
  require_once('Message.php');
  $msg = new Message(Globals::$dbaccess);
  echo $msg->form();
// echo $msg->dump(basename(__FILE__) . __LINE__);
?>
  </div>
