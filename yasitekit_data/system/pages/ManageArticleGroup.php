<?php
/*
#doc-start
h1.  ManageArticleGroup.php - Article Group management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Article Group Management";
Globals::$page_obj->page_title = "Article Group Management";
Globals::$page_obj->form_action = 'ManageArticleGroup.php';
Globals::$page_obj->required_authority = 'A';
$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegFile('tinymce', 'tinymce.html'));

require_once('ArticleGroup.php');

$obj = new ArticleGroupManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
