<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>yatheme_main.tpl</title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="Mike">
	<!-- Date: 2010-11-16 -->
</head>
<body>
<?php
  set_include_path('../system/includes:../system/objects' . PATH_SEPARATOR . get_include_path());
  require_once('aclass.php');
  AClass::define_class('Foo', 'a', array(array('a', 'varchar(255)', 'A')), NULL);
  class Foo {
    public $a = 'a value';
  }
  $foo = new Foo();
  echo "This is output from a PHP echo statement\n";
?>
\{:include: sample_theme.tpl :}
\<h1>yatheme_include1.tpl</h1>
<p>This is static text from yatheme_include1.tpl</p>
<!-- All (other) YATheme Tags -->
<div class="yatheme-annotation">
Annotation Text

</div>


<?php if (AClass::attribute_existsP('Foo', 'a')): ?>
Text if Foo.a exists
<?php endif; ?>
<?php if (AClass::attribute_existsP('Foo', 'b')): ?>
Text if Foo.b exists
<?php else: ?>
Text if Foo.b does NOT Exist
 <?php endif; ?>

<?php if (isset($foo->a)): ?>
Text if Foo.a isset
<?php endif; ?>
<?php if (isset($foo->b)): ?>
Text if Foo.b isset
<?php else: ?>
Text if Foo.b does NOT Exist
 <?php endif; ?>

<?php if (!$foo->a): ?>
Text if Foo.a not-null
<?php endif; ?>
<?php if (!$foo->b): ?>
Text if Foo.b not-null
<?php else: ?>
Text if Foo.b does NOT Exist
 <?php endif; ?>

<?php if ($foo->a === FALSE): ?>
Text if Foo.a not-false
<?php endif; ?>
<?php if ($foo->b === FALSE): ?>
Text if Foo.b not-false
<?php else: ?>
Text if Foo.b does NOT Exist
 <?php endif; ?>

Text if Foo.a if-true
Text if Foo.b if-true
Text if Foo.b does NOT Exist

Text if Foo.a if-false
Text if Foo.b if-false
Text if Foo.b does NOT Exist

<div class="yatheme-error">No Instance declared for Foo</div>
foo</body>
</html>
