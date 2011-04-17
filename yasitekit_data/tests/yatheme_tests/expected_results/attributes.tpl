<?php
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<!-- default_template.tpl -->
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo (isset($page_title) ? $page_title : '<div class="yatheme-error"><p>Error: variable \'$page_title\' is not set</p></div>'); ?></title>
	  <meta name="generator" content="TextMake http://macromates.com/">
  <meta name="author" content="Mike">

	<link rel="stylesheet" href="/css/screen.css" type="text/css" media="screen" charset="utf-8">
<link rel="stylesheet" href="/css/print.css" type="text/css" media="print" charset="utf-8">
<link rel="stylesheet" href="/css/handheld.css" type="text/css" media="handheld" charset="utf-8">

</head>
<body>
<!-- (c) Copyright 2010 Mike Howard. All Rights Reserved.  -->
<div id="header">
  <a id="logo" class="float-left box"  href="/index.php" title="Home Page">
    <img src="/img/YASiteKitLogo.png" height="50px" alt="YASiteKit Logo">
  </a>
<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->key_values_complete()): ?>
<p class="smaller float-right width-20" style="position:absolute;right:0;top:0;">Hi <?php echo Globals::$account_obj->name ?>
   (to access your account click Login)</p>
<?php endif;?>
  <h1 class="center"><?php echo (isset($page_header) ? $page_header : '<div class="yatheme-error"><p>Error: variable \'$page_header\' is not set</p></div>'); ?></h1>
</div>

<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->logged_in()): ?>
<div id="account-nav" class="box click-display" >
<span class="bold title center"><?php echo Globals::$account_obj->name; ?> Menu <span class="smaller">(click or mouse over)</span></span>
<ul id="account-nav-ul" class="display-target">
<?php switch (Globals::$account_obj->authority):
  case 'C': ?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.php">Account Information</a></li>
    <li><a href="/ManageSubscription.php">Newsletter Subscription</a></li>
<?php
    break;
    case 'A':
    case 'M':
    case 'W':
?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.php">Account Information</a></li>
    <li><a href="/ManageSubscription.php">Newsletter Subscriptions</a></li>
    <li><a href="/ManageProduct.php">Products</a></li>
<?php
    break;
    case 'S':
?>
    <li><span class="bigger bold"><?php echo (isset(Globals::$site_name) ? Globals::$site_name : '<div class="yatheme-error"><p>Error: variable \'Globals::$site_name\' is not set</p></div>'); ?> Data -</span></li>
    <li><a href="/ManageAccount.php">Manage Accounts</a></li>
    <li><a href="/ManageSubscription.php">Communication Subscriptions</a></li>
    <li><a href="/ManageProduct.php">Manage Products</a></li>
    <li><a href="/ManageNewsletter.php">Manage Newletter</a></li>
    <li><a href="/ManageProductOrder.php">Order Management</a></li>
    <li><a href="/ManageRMA.php">RMA Management</a></li>
    <li><a href="/ManageArticle.php">Manage Articles</a></li>
    <li><a href="/ManageArticleGroup.php">Manage Article Groups</a></li>
<?php
    break;
    case 'X':
?>
    <li><span class="bigger bold"><?php echo (isset(Globals::$site_name) ? Globals::$site_name : '<div class="yatheme-error"><p>Error: variable \'Globals::$site_name\' is not set</p></div>'); ?> System -</span></li>
<?php
    foreach (array_unique(array_merge(scandir(Globals::$pages_root), scandir(Globals::$system_pages))) as $fname):
      if (preg_match('/^Manage(.*)\.php$/', $fname, $m)):
?>
      <li><a href="/<?php echo (isset($fname) ? $fname : '<div class="yatheme-error"><p>Error: variable \'$fname\' is not set</p></div>'); ?>">Manage <?php echo (isset($m[1]) ? $m[1] : '<div class="yatheme-error"><p>Error: variable \'$m[1]\' is not set</p></div>'); ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
      <li><a href="/AdminTools.php">Admin Tools</a></li>
<?php  break; ?>
<?php endswitch; ?>
  </ul>
</div> <!-- account-nav -->
<?php endif; ?>


<div id="content-container">
  <div id="content" class="box">
<?php
set_include_path("../system/includes:../system/objects:" . get_include_path());
require_once('aclass.php');

class Foo {
  static public $s;
  public $a;
  
  public function __toString() {
    return "Foo({$this->a})";
  } // end of __toString()
}
Foo::$s = 'B VALUE';

$foo = new Foo();
$foo->a = 'A VALUE';
?>

Expect 4 sentences which say 'This is A VALUE xxx'
followed by 2 sentences which say 'That is B VALUE yyy'

This is <?php echo (isset($foo->a) ? $foo->a : '<div class="yatheme-error"><p>Error: variable \'$foo->a\' is not set</p></div>'); ?> xxx
This is <?php echo (isset($foo->a) ? $foo->a : '<div class="yatheme-error"><p>Error: variable \'$foo->a\' is not set</p></div>'); ?> xxx
This is <?php echo (isset($foo->a) ? $foo->a : '<div class="yatheme-error"><p>Error: variable \'$foo->a\' is not set</p></div>'); ?> xxx
This is <?php echo (isset($foo->a) ? $foo->a : '<div class="yatheme-error"><p>Error: variable \'$foo->a\' is not set</p></div>'); ?> xxx
This is <?php echo (isset(Foo::$s) ? Foo::$s : '<div class="yatheme-error"><p>Error: variable \'Foo::$s\' is not set</p></div>'); ?> xxx
This is <?php echo (isset(Foo::$s) ? Foo::$s : '<div class="yatheme-error"><p>Error: variable \'Foo::$s\' is not set</p></div>'); ?> xxx


Expect 1 sentence which says 'This is C VALUE xxx'
followed by 1 sentences which says 'This is B VALUE yyy'

<?php $bar = new Foo(); $bar->a = 'C VALUE'; ?>

This is <?php echo (isset($bar->a) ? $bar->a : '<div class="yatheme-error"><p>Error: variable \'$bar->a\' is not set</p></div>'); ?> xxx
This is <?php echo (isset(Foo::$s) ? Foo::$s : '<div class="yatheme-error"><p>Error: variable \'Foo::$s\' is not set</p></div>'); ?> yyy

Expect one sentence which says 'This is D VALUE.'
<?php $bar->a = 'D Value'; ?>
This is <?php echo (isset($bar->a) ? $bar->a : '<div class="yatheme-error"><p>Error: variable \'$bar->a\' is not set</p></div>'); ?>.

Expect one sentence which says '$not_set is not set'
<?php echo (isset($not_set) ? $not_set : '<div class="yatheme-error"><p>Error: variable \'$not_set\' is not set</p></div>'); ?>    
  </div> <!-- content -->

  <div id="secondary-menu" class="hover-reveal">
    <div id="secondary-nav">
  <span class="smaller bold click-message" style="margin-top:-1.5em;position:absolute;">(mouse over to view)</span>
  <p class="larger bold">Neat Stuff Menu</p>
  <ul>
    <li id="secondary-nav-doc" class="level-1 box">
      <span>
        <a href="/doc.d/index.php" title="YASiteKit Doc">YASiteKit Doc</a>
      </span>
    </li>
    <li id="secondary-nav-download" class="level-1 box">
      Download YASiteKit Code
      <ul>
        <li><a href="/downloads/site-framework-with-system.tar.gz">Complete Generic Site Stubs</a></li>
        <li><a href="/downloads/site-framework-no-system.tar.gz">Generic Site Stubs without System</a></li>
        <li><a href="/downloads/yasitekit-system-latest.tar.gz">YASiteKit System Code 1.0.4 Alpha - to Update Site System</a></li>
        <li><a href="/downloads/yasitekit-doc.d.tar.gz">YASiteKit Documentation - gzip'ed tar file</a></li>
        <li><a href="/downloads/msh-utilities-1.0.0.tar.gz">Some useful System Independent Utilities - requires Python 2.6 - tar.gz format</a></li>
        <li><a href="/downloads/msh-utilities-1.0.0.zip">Some useful System Independent Utilities - requires Python 2.6 - zip format</a></li>
      </ul>
    </li>
    <li id="secondary-nav-videos" class="box">Tutorial Videos
      <ul>   <!-- Videos -->
        <li>
          Creating a New Site using YASiteKit
          <ul>  <!-- Setting Up New Site -->
            <li><a href="http://www.youtube.com/watch?v=UmLE7QHCF2I">Starting: Creating the Local Development Site</a></li>
          </ul>  <!-- End Setting Up New Site -->
        </li>
        <li>
          Setting Up Apache & MySQL
          <ul>   <!-- Apache and Mysql -->
            <li><a href="http://www.youtube.com/watch?v=0W8AvuZDueQ">Installing XCode & MacPorts</a></li>
            <li><a href="http://www.youtube.com/watch?v=QPCPe54v1aM">Setting Up & Configuring Apache on OS X</a></li>
            <li><a href="http://www.youtube.com/watch?v=QrkQNvMP6R0">Setting Up MySQL5 on OS X</a></li>
          </ul>   <!-- Apache and Mysql -->
        </li>
      </ul>   <!-- End Videos -->
    </li>
  </ul>
</div>
  </div>
</div> <!-- end content-container -->

<div id="main-nav">
  <!-- main_nav.tpl -->
  <ul>
    <li id="main-nav-home" class="box"><a href="/article/home">Home</a></li>
    <li id="main-nav-info" class="box"><span><a href="/article/about" title="About">About YASiteKit</a></span></li>
    <li id="main-nav-subscribe" class="box"><span><a href="/NewsletterSubscribe.tpl">Get Our Newsletter</a></span></li>
    <li id="main-nav-help" class="box level-1"><span>Help</span>
      <ul class="level-2">
<?php
  require_once('Article.php');
  require_once('ArticleGroup.php');
  $article_group = new ArticleGroup(Globals::$dbaccess, 'help');
  $help_objects = $article_group->articles();
  foreach ($help_objects as $help_article): ?>
        <li><a href="/article/<?php echo (isset($help_article->name) ? $help_article->name : '<div class="yatheme-error"><p>Error: variable \'$help_article->name\' is not set</p></div>'); ?>"><?php echo (isset($help_article->title) ? $help_article->title : '<div class="yatheme-error"><p>Error: variable \'$help_article->title\' is not set</p></div>'); ?></a></li>
<?php endforeach; ?>
      </ul>
    </li>
  </ul>
</div>

<!-- (c) Copyright 2010 Mike Howard. All Rights Reserved.  -->
<div id="footer" class="clear"> <!-- footer -->
  <a href="http://www.yasitekit.org">
    <img class="float-right" src="/img/PoweredBy.png" alt="Powered by YASiteKit">
  </a>
  <ul>
    <li><a href="/article/privacy" title="Privacy Policy">Privacy Policy</a></li>
    <li>|</li>
    <li><a href="/article/terms" title="Terms and Conditions">Terms and Conditions</a></li>
    <li>|</li>
    <li><a href="/Contact.tpl" title="Contact Us">Contact Us</a></li>
<?php if (Globals::$session_obj instanceof Session): ?>
    <li>|</li>
<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->logged_in()): ?>
    <li><a href="/Login.php?logout=Y" title="Logout">Logout</a></li>
<?php else: ?>
    <li><a href="/Login.php" title="Login">Login</a></li>
<?php endif; // login/logout buttons ?>
<?php endif; // require session object ?>
  </ul>
</div>
<?php if (Globals::$site_installation == 'development'): ?>
<div class="dump-output">
  <?php echo Globals::dump(); ?>
  <?php var_dump($_SERVER); ?>
</div>
<?php endif; ?>
<script type="text/javascript" src="/javascript/jquery-1.4.2.js" charset="utf-8"></script>
<script type="text/javascript" src="/javascript/yasitekit.js" charset="utf-8"></script>

</body>
</html>