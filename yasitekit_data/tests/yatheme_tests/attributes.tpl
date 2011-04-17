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

This is {: $foo->a :} xxx
This is {: $foo->a :} xxx
This is {: $foo->a :} xxx
This is {: $foo->a :} xxx
This is {: Foo::$s :} xxx
This is {:Foo::$s:} xxx


Expect 1 sentence which says 'This is C VALUE xxx'
followed by 1 sentences which says 'This is B VALUE yyy'

<?php $bar = new Foo(); $bar->a = 'C VALUE'; ?>

This is {: $bar->a :} xxx
This is {: Foo::$s :} yyy

Expect one sentence which says 'This is D VALUE.'
<?php $bar->a = 'D Value'; ?>
This is {: $bar->a :}.

Expect one sentence which says '$not_set is not set'
{:$not_set:}