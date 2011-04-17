<?php
/*
#doc-start
h1.  DisplayArticle.tpl - Renders Article content

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Displays the article specified by the _name_ field in the URL http://site/article/name.

h2. Variables

These variables (which _default_template.tpl_ expects) are defined as:

* $page_header - from Article _title_ field
* $page_title - Globals::$site_name plus Article _title_ field
* ROBOTS meta tag - set using _folow_index_ field
* DESCRIPTION meta tag - contains Article _description_ field
* $canonical - set to absolute URL if article is defined, otherwise not set.
Creates the Google specified _canonical_ link

If the article specified does not exist or no article is specified, then these
variables are filled in using generic information.

#end-doc
*/

/// global variables
?>
{:php-prefix:}
require_once('Article.php');
if (isset(Globals::$router_obj->article)) {
  if (AnInstance::existsP('Article', Globals::$dbaccess, Globals::$router_obj->article)) {
    $article_obj = new Article(Globals::$dbaccess, Globals::$router_obj->article);
    $page_header = $article_obj->title;
    $page_title = Globals::$site_name . "- $article_obj->title";
    $robots_content = $article_obj->follow_index == 'Y' ? "INDEX, FOLLOW" : "NOINDEX, NOFOLLOW";
    $article = $article_obj->render();
    $description = $article_obj->description;
    $canonical = Globals::$site_url . "/article/{$article_obj->name}";
  } else {
    $article_obj = new Article(Globals::$dbaccess, Globals::$router_obj->article);
    $page_header = "Article Not Found";
    $page_title = Globals::$site_name . " - Article Not Found";
    $robots_content = "NOINDEX, NOFOLLOW";
    $article = '';
    $description = "Access Error: no article named '" . Globals::$router_obj->article . "'";
    $dbaccess = '';  // set for test_DisplayArticle.php
  }
} else {
  $page_header = 'Error: No Article Specified'; 
  $page_title = 'Article Display';
  $robots_content = "NOINDEX, NOFOLLOW";
  $description = "Access Error: no article specified";
  $article = '';
  $dbaccess = '';
}
{:end-php-prefix:}
{:meta robots <?php echo "$robots_content"; ?>:}
<?php if (isset($description)): ?>
{:meta description <?php echo "$description";?>:}
<?php endif; ?>
{:$article:}
<?php // echo Globals::$rc->dump('DisplayArticle.php - dump of request cleaner'); ?>
