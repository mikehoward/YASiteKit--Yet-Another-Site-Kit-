<div id="main-nav">
  <ul>
    <li id="main-nav-home" class="box"><a href="/article/home">Home</a></li>
    <li id="main-nav-info" class="box"><span><a href="/article/about" title="About">About YASiteKit</a></span></li>
    <li id="main-nav-subscribe" class="box"><span><a href="/NewsletterSubscribe.php">Get Our Newsletter</a></span></li>
    <li id="main-nav-help" class="box level-1"><span>Help</span>
      <ul class="level-2">
<?php
  require_once('Article.php');
  require_once('ArticleGroup.php');
  $article_group = new ArticleGroup(Globals::$dbaccess, 'help');
  $help_objects = $article_group->articles();
  foreach ($help_objects as $help_article) {
    echo "<li><a href=\"/article/{$help_article->name}\">{$help_article->title}</a></li>\n";
  }
?>
      </ul>
    </li>
  </ul>
</div>
