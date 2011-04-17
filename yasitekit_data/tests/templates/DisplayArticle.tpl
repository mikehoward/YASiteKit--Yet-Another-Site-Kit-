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
?>
{:php-prefix:}
require_once('Article.php');
if (isset(Globals::$rc->safe_get_article)) {
  if (AnInstance::existsP('Article', Globals::$dbaccess, Globals::$rc->safe_get_article)) {
    $article_obj = new Article(Globals::$dbaccess, Globals::$rc->safe_get_article);
    $page_header = $article_obj->title;
    $page_title = Globals::$site_name . "- $article_obj->title";
  } else {
    $article_obj = new Article(Globals::$dbaccess, Globals::$rc->safe_get_article);
    $page_header = "Article Not Found";
    $page_title = Globals::$site_name . " - Article Not Found";
  }
} else {
  $page_header = 'Error: No Article Specified'; 
  $page_title = 'Article Display';
}
{:end-php-prefix:}
{:if not-false $article_obj:}
    {:if  $article_obj->follow_index == 'Y':}
      {:meta ROBOTS INDEX, FOLLOW:}
    {:else :}
      {:meta ROBOTS NOINDEX, NOFOLLOW:}
    {:endif:}

    <?php echo $article_obj->render(); ?>
{:else:}
  {:meta_robot = <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">:}
{:endif:}
<?php // echo Globals::$rc->dump('DisplayArticle.php - dump of request cleaner'); ?>
