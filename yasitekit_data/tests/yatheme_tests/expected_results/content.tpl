<?php

// from yatemplate.tpl
$words[] = "words from yatemplate.tpl";


// from incl2.tpl
$words[] = 'words from incl2.tpl';


// from inner_include.tpl
$words[] = 'words from inner_include.tpl';


// from inner_template.tpl
$inner = "inner var";$foo = "if you see this it is wrong";
$words[] = "words from inner_template.tpl";

$words[] = "words from content.tpl setup";

// from content-include.tpl
$words[] = 'words from content-include.tpl';


// from content.tpl
$foo = "this is a foo";
$words[] = 'words from content.tpl prefix';

?>
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

This is a Template
The PHP var $foo: '<?php echo (isset($foo) ? $foo : '<div class="yatheme-error"><p>Error: variable \'$foo\' is not set</p></div>'); ?>'
The inner var: '<?php echo (isset($inner) ? $inner : '<div class="yatheme-error"><p>Error: variable \'$inner\' is not set</p></div>'); ?>'

<?php
/* PHP Comments in template */
?>

Template content starts here:
------------yatemplate-content starts---------------


First line of Inner Template
Here is $foo: '<?php echo (isset($foo) ? $foo : '<div class="yatheme-error"><p>Error: variable \'$foo\' is not set</p></div>'); ?>'
including inner_include.tpl

-----------------------------
<p>This is inner_template.tpl content</p>
Including incl2.tpl

<p>Incl2.tpl content</p>

-----------------------------

Content:
**********yatemplate-content starts*****************


This is the First Line of Content

<?php
/*
  First line of multi line comment in content part of test
  Second line of multi-line comment
*/
?>
Content line 1
Content line 2
<?php echo "php echoed content line 3\n"; // an inline comment ?>
Content line 4
Including content-include.tpl

<p>This is 'content include'</p>

This is the Last Line of Content

**********yatemplate-content ends***************
Last Line of Inner Template

------------yatemplate-content ends---------------
Template content just ended
<?php    echo     "this is echoed from php\n";     ?>
This is the last line of the template
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

Here are the words, in order of occurance in the prefix:
<pre>
<?php echo implode("\n", $words); ?>
</pre>