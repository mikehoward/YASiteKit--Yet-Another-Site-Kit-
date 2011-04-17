<?php
/*
#doc-start
h1.  DisplayWallpaper - Displays Wallpaper Product

Created by  on 2010-05-06.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This simply packages up a selected wallpaper image with some instructions for
saving on the client computer.

It also updates the wallpaper download statistics.
#end-doc
*/

// global variables
ObjectInfo::do_require_once('Product.php');
$product_obj = new Product(Globals::$dbaccess, Globals::$rc->safe_request_product_name);
// FIXME: add robot detection
$product_obj->wallpaper_downloads += 1;
$product_obj->save();
$product_size_name = Globals::$rc->safe_request_product_size_name;
$img_url = $product_obj->interpolate_string($product_obj->get_prop($product_size_name, 'path'));

Globals::$page_obj->page_title = "Editions of One - Downloading &ldquo;$product_obj->title&rdquo;";
Globals::$page_obj->page_header = Globals::$site_name . " - Downloading &ldquo;$product_obj->title&rdquo;";
Globals::$page_obj->add_meta('ROBOTS', 'INDEX, FOLLOW');

// end global variables

?>
<h2>Here's your Product</h2>
<p>Thank you for Downloading this digital copy of &ldquo;<?php echo $product_obj->title; ?>&rdquo;.</p>
<p>Now put your mouse over the image and do a Right Click - or Command Click on a Mac -
  to bring up your browser's menu. Then choose the option which saves the image
  where you want it.</p>
<p><img src="<?php echo $img_url; ?>"></p>

