<?php
/*
#doc-start
h1.  ManageNewsletter.php - Newsletter Issue management and Editing

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Newsletter Management";
Globals::$page_obj->page_title = "Newsletter Management";
Globals::$page_obj->form_action = 'ManageNewsletter.php';
Globals::$page_obj->required_authority = 'S';
$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegFile('tinymce', 'tinymce.html'));

ObjectInfo::do_require_once('Newsletter.php');
$obj = new NewsletterManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
