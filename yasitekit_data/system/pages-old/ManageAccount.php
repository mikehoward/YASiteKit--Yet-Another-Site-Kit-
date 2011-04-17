<?php
/*
#doc-start
h1.  ManageAccount.php - Administrative Account Management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Account Management";
Globals::$page_obj->page_title = "Account Management";
// Globals::$page_obj->required_authority = 'X';
Globals::$page_obj->required_authority = 'C,M,V,A,W,S,X';
Globals::$page_obj->form_action = 'ManageAccount.php';

require_once('Account.php');

$obj = new AccountManager(Globals::$dbaccess, Globals::$account_obj);
$obj->render_form(Globals::$rc);

?>
