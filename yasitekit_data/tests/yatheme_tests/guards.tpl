<?php
  set_include_path("../system/includes:../system/objects:" . get_include_path());
  require_once('aclass.php');
  
  class Foo {
    static public $foo;
    public $a;
  }
  $foo = new Foo();
  $foo->a = 'a';
  $class_name_var = 'Foo';
?>

{:guards on:}
With guards on: $foo->a is {:$foo->a:}

{:guards off:}
With guards off: $foo->a is {:$foo->a:}


test Foo::$foo->a:
{:test Foo::$foo->a:}

test $foo-->a - should generate syntax warning
{:test $foo-->a:}