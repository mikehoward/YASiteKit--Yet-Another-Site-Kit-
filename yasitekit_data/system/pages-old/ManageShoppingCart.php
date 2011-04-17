<?php
/*
#doc-start
h1.  ManageShoppingCart.php - Shoppng Cart Management

Created by  on 2010-04-05.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
ObjectInfo::do_require_once('ShoppingCart.php');

Globals::$page_obj->page_header = Globals::$site_name . " - ShoppingCart Management";
Globals::$page_obj->page_title = "ShoppingCart Management";
Globals::$page_obj->form_action = 'ManageShoppingCart.php';
Globals::$page_obj->required_authority = 'C';

ObjectInfo::do_require_once('ShoppingCart.php');
$obj = new ShoppingCartManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);

?>
