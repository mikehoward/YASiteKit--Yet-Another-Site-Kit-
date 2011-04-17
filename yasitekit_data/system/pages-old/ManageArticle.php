<?php
/*
#doc-start
h1.  ManageArticle.php - Article ownere article management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Article Management";
Globals::$page_obj->page_title = "Article Management";
Globals::$page_obj->form_action = 'ManageArticle.php';
Globals::$page_obj->required_authority = 'A';
$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegFile('tinymce', 'tinymce.html'));

require_once('Article.php');

$obj = new ArticleManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
