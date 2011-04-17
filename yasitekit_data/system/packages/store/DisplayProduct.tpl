<?php
/*
#doc-start
h1.  DisplayProduct.php - Renders an Product Object

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is exclusively taylored for YASiteKit.org and is both the primary
display page for images and the gateway to purchases.

#end-doc
*/

// global variables
ObjectInfo::do_require_once('Product.php');
ObjectInfo::do_require_once('Category.php');
if (Globals::$session_obj instanceof Session) {
  foreach (array('safe_get_product_menu_mode', 'safe_get_product_menu_start_offset',
      'safe_get_product_menu_end_offset', 'safe_get_product_menu_grouping') as $key) {
    if (isset(Globals::$rc->$key)) {
      $session_key = preg_replace('/^safe_(get|post|request)_/', '', $key);
      Globals::$session_obj->$session_key = Globals::$rc->$key;
    }
  }
}

// get image name and create $product_obj, if possible
// set view count flag so we only count new loads of each image.
if (isset(Globals::$rc->safe_get_image)
    && AnInstance::existsP('Product', Globals::$dbaccess, Globals::$rc->safe_get_image)) {
  $product_obj = new Product(Globals::$dbaccess, Globals::$rc->safe_get_image);
  Globals::$page_obj->page_title = "Product &ldquo;$product_obj->title&rdquo;";
  Globals::$page_obj->page_header = Globals::$site_name . " - &ldquo;$product_obj->title&rdquo;";
  $product_obj->save_as_current_product();
  echo $product_obj->render_product();

  if (Globals::$cookie_track &&
      Globals::$session_obj instanceof Session && (Globals::$session_obj->product_name || Globals::$rc->safe_get_image != Globals::$session_obj->product_name)) {
    if (!class_exists('ProductView')) ObjectInfo::do_require_once('ProductView.php');
    $imageview_obj = new ProductView(Globals::$dbaccess, array('product_name' => $product_obj->name,
        'cookie' => Globals::$user_cookie_value));
    $imageview_obj->timestamp = new DateTime('now');
    $imageview_obj->view_count += 1;
    $imageview_obj->save();
  }
} elseif (Globals::$session_obj instanceof Session && Globals::$session_obj->product_name) {
  $product_obj = new Product(Globals::$dbaccess, Globals::$session_obj->product_name);
  Globals::$page_obj->page_title = "Product &ldquo;$product_obj->title&rdquo;";
  Globals::$page_obj->page_header = Globals::$site_name . " - &ldquo;$product_obj->title&rdquo;";
  echo $product_obj->render_product();
} else {
  Globals::$page_obj->page_title = "Products";
  Globals::$page_obj->page_header = Globals::$site_name . " - Please Select an Product";
 
  echo Product::product_thumbnails(Globals::$dbaccess, 'image');
  echo "<p>Please Select an Product from Either the Product Group Menu or the Product List above.</p>\n";
}

?>
