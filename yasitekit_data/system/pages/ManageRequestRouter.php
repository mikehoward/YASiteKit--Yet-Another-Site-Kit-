<?php
/*
#doc-start
h1.  ManageRequestRouter.php - pity summary of what this is doing

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Copy and hack.

The file should go in _private_data_root / pages_.

It should be named ManageYourRequestRouter.php - that way _account_menu.php_ will
automatically find it and add it to the administrative menu.

#end-doc
*/

// global variables

Globals::$page_obj->page_header = Globals::$site_name . " - RequestRouter Management";
Globals::$page_obj->page_title = "RequestRouter Management";
// Globals::$page_obj->required_authority = 'C';
// Globals::$page_obj->required_authority = 'M';
// Globals::$page_obj->required_authority = 'W';
// Globals::$page_obj->required_authority = 'A';
// Globals::$page_obj->required_authority = 'S';
Globals::$page_obj->required_authority = 'X';
// Globals::$page_obj->required_authority = 'M,W,A,S,X';

Globals::$page_obj->form_action = 'ManageRequestRouter.php';

require_once('RequestRouter.php');

$obj = new RequestRouterManager(Globals::$dbaccess, Globals::$account_obj);
$obj->render_form(Globals::$rc);

?>
