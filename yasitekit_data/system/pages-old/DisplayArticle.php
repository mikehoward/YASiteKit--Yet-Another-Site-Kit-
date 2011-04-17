<?php
/*
#doc-start
h1.  DisplayArticle.php - Renders Article content

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Requires one GET parameter: _article_ - which is the name of an Article object

#end-doc
*/

/// global variables
require_once('Article.php');
if (isset(Globals::$rc->safe_get_article)) {
  if (AnInstance::existsP('Article', Globals::$dbaccess, Globals::$rc->safe_get_article)) {
    $article_obj = new Article(Globals::$dbaccess, Globals::$rc->safe_get_article);
    Globals::$page_obj->page_header = $article_obj->title;
    Globals::$page_obj->page_title = Globals::$site_name . " - $article_obj->title";
    Globals::$page_obj->add_meta('ROBOTS', $article_obj->follow_index == 'Y'
      ? 'INDEX, FOLLOW' : 'NOINDEX, NOFOLLOW');
    Globals::$page_obj->add_meta('DESCRIPTION', $article_obj->description);
    echo $article_obj->render();
  } else {
    Globals::$page_obj->page_header = Globals::$site_name . " -Article " . Globals::$rc->safe_get_article . " Not Found";
    Globals::$page_obj->page_title = Globals::$site_name . " - Article Not Found";
    Globals::$page_obj->add_meta('ROBOTS', 'NOINDEX, NOFOLLOW');
  }
} else {
  Globals::$page_obj->page_header = Globals::$site_name . " -No Article Specified";
  Globals::$page_obj->page_title = Globals::$site_name . " - No Article Specified";
  Globals::$page_obj->add_meta('ROBOTS', 'NOINDEX, NOFOLLOW');
}
// echo Globals::$rc->dump('DisplayArticle.php - dump of request cleaner');
?>
