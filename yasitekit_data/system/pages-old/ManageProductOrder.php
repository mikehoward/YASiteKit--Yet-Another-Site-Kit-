<?php
/*
#doc-start
h1.  ManageProductOrder.php - Product Order Management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - ProductOrder Management";
Globals::$page_obj->page_title = "ProductOrder Management";
Globals::$page_obj->form_action = 'ManageProductOrder.php';
Globals::$page_obj->required_authority = 'S';

ObjectInfo::do_require_once('ProductOrder.php');
$obj = new ProductOrderManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
