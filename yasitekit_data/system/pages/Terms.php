<?php
/*
#doc-start
h1.  Terms.php - Obsolete Terms and Conditions

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Terms and Conditions";
Globals::$page_obj->page_title = "Terms and Conditions";
Globals::$page_obj->form_action = "SendForm.php";
$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegFile('tinymce', 'tinymce.html'));
?>
<div class="box content">
  <div class="padded">
    <h2>Quality</h2>
    <p>We use the materials which are - in our opinion and experience - the
      best available.</p>
    <p>We strive to make our prints and framing the best quality we possibly can.</p>

    <h2>Returns</h2>
    <p>If you're not happy with the print or framing or anything, then return it -
      <span class="bold">but don't wait around because we don't accept returns or make refunds unless
      arrangements are made within 30 days after we ship.</span></p>
    <p class="bold">But There's More: We don't accept returns or make refunds unless the print is received within
      10 business days after we authorize the return.</p>
    <p>Returned Prints are accepted ONLY if you arrange for the return by
      contacting us. Contact may be made by telephone, e-mail, or submitting
      a note through the site's contact form below.</p>
    <p>You must arrange for the return within 30 days of our shipment date. Again,
      This is 30 calendar days after We Ship.</p>
    <p>We don't require any reason for the return.</p>
    <p>Again: We MUST receive the return within 10 business days of our authorizing
      the return. If it is received late, we will refuse receipt and will not
      grant a refund.</p>
    <p>We do NOT authorize returns after 30 days after we ship.</p>
    <p>We will refund your purchase price on the following scale, based on the
      condition of the print and frame when it arrives at our location. <span class="bold">You should
      purchase insurance to cover damage in transit.</span></p>
    <ol>
      <li>Pristine Condition - just like it left - Full purchase cost less shipping and insurance.</li>
      <li>Pristine Print, but Damaged Framing - 70% of purchase cost less shipping and
        insurance.</li>
      <li>Pristine Print, but Damaged Matting and/or Framing - 50% of purchase cost, less shipping
        and insurance. This is a compromise we don't like to make because the print will most
        likely not be saleable.</li>
      <li>Damaged Print - nothing. We will cooperate with you in collecting your insured amount</li>
    </ol>
    <div class="box">
<?php
 require_once('Message.php');
 $msg = new Message(Globals::$dbaccess);
 echo $msg->form();
?>
    </div>
  </div>
</div>
