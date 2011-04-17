<?php
// Add Article groups for infrastructure and help
$article_stubs = array(
  array("home", "Home Article"),
  array("about", "About Article"),
  array("terms", "Terms & Conditions Article"),
  array("privacy", "Privacy Policy Article"),
  );
// require_once('ArticleGroup.php');
$artricle_groups = array(
  array('help', 'Help Articles'),
  array('infrastructure', 'Infrastructure Articles'),
  );
foreach ($artricle_groups as $row) {
  list($name, $description) = $row;
  if (!AnInstance::existsP('ArticleGroup', Globals::$dbaccess, $name)) {
    echo "Article Group: $name / $description\n";
    $agrp = new ArticleGroup(Globals::$dbaccess, $name);
    $agrp->title = $description;
    $agrp->description = $description;
    $agrp->save();
  }
}
$agrp = new ArticleGroup(Globals::$dbaccess, 'infrastructure');
$infr_key = $agrp->encode_key_values();

// require_once('Article.php');
foreach ($article_stubs as $ar) {
  list($name, $title) = $ar;
  if (!AnInstance::existsP('Article', Globals::$dbaccess, $name)) {
    echo "Creating Article: $name / $title\n";
    $art = new Article(Globals::$dbaccess, $name);
    $art->article_group = $infr_key;
    $art->title = $title;
    $art->description = $title;
    $art->follow_index = 'N';
    $art->article_body = "<h1>$title</h1>"
      . "<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
    labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
    aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore
    eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
    mollit anim id est laborum.</p>";
    $art->save();
  }
}
