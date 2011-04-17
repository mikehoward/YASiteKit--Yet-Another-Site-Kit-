<?php
/*
#doc-start
h1.  ManageDownloadAuthorization.php - Administrative download authorization management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - DownloadAuthorization Management";
Globals::$page_obj->page_title = "DownloadAuthorization Management";
Globals::$page_obj->form_action = 'ManageDownloadAuthorization.php';
// Globals::$page_obj->required_authority = 'C';

ObjectInfo::do_require_once('DownloadAuthorization.php');

$obj = new DownloadAuthorizationManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
