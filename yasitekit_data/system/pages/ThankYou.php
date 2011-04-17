<?php
/*
#doc-start
h1. ThankYou.php - Displayed to acknowledge a purchases

Created by  on 2010-04-26.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


#end-doc
*/

// global variables
ObjectInfo::do_require_once('Product.php');

Globals::$page_obj->page_title = 'Thank You For Your Purchase';
Globals::$page_obj->page_heading = 'Thank You For Your Purchase';
$product_obj = new Product(Globals::$dbaccess, Globals::$rc->safe_request_image);
$tmp = $product_obj->get_prop('product_320', 'path');
$product_path = preg_replace('/{name}/', $product_obj->name, $tmp);
$product_obj->sold = "Y";
$product_obj->save();
?>
<img class="float-right" src="<?php echo $product_path;?>" alt="Small Product of <?php echo $product_obj->title; ?>">
<p>Thank you for your purchase of &ldquo;<?php echo $product_obj->title ?>&rdquo;</p>
<p>We will be in touch with you by email soon with estimated shipping date.</p>
<p>Click <a style="text-decoration:underline;color:#8888ff;font-size:larger" href="/index.php">here</a> to continue browsing our images.</p>
