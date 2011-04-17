<?php
/*
#doc-start
h1.  ManageRMA.php - Return Material Authorization management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - RMA Management";
Globals::$page_obj->page_title = "RMA Management";
Globals::$page_obj->form_action = 'ManageRMA.php';
Globals::$page_obj->required_authority = 'S';

ObjectInfo::do_require_once('RMA.php');

$tmp = new RMAManager(Globals::$dbaccess);
$tmp->render_form(Globals::$rc);
?>
