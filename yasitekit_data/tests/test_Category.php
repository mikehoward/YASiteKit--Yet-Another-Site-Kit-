<?php
set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
global $dbaccess;

echo "$dbaccess\n";

require_once('Category.php');
$cat_class_instance = AClass::get_class_instance('Category');
$cat_class_instance->create_table($dbaccess);

foreach (array('foo_bar', 'foo_baz', 'foo_bloop', 'foo_bar_beep', 'foo_bar_beep_1', 'foo_bar_beep_2', 'bar') as $path)  {
  $cat = new Category($dbaccess, $path);
  echo "$cat [{$cat->parent} _ {$cat->name}] (sibling_ordinal: $cat->sibling_ordinal)\n";
  // system("echo \"select * from _parameters where cls='Category';\" | sqlite sqlite_testdb");
}

echo "\nDump of Category Table\n";
system("echo 'select * from category;' | sqlite sqlite_testdb");

echo "\nHTML Table of Categories\n";
echo $cat->table_of_categories();

$cat = new Category(Globals::$dbaccess, 'foo');
echo "\n\$cat: $cat\n";
echo "Direct children:\n";
foreach ($cat->children() as $child) {
  echo "  $child\n";
}

echo "\nAll descendents of $cat:\n";
foreach ($cat->descendents() as $child) {
  echo "  $child\n";
}

echo "\nHTML Shallow Options of root\n";
echo Category::options_elt_for_category(Globals::$dbaccess, '', 'foo');

echo "\nHTML Deep Options of root\n";
echo Category::options_elt_for_category(Globals::$dbaccess, '', 'foo', TRUE);

echo "\nHTML Shallow Options for foo\n";
echo Category::options_elt_for_category(Globals::$dbaccess, 'foo', array('foo_bar', 'foo_bloop', 'foo_bar_beep_2'));

echo "\nHTML Deep Options for foo\n";
echo Category::options_elt_for_category(Globals::$dbaccess, 'foo', array('foo_bar', 'foo_bloop', 'foo_bar_beep_2'), TRUE);

echo "\nTesting bad category() definitions: expecting exceptions\n";
foreach (array('category()', 'category(FOO)', 'category(a b c)', 'category(1-2)') as $cat_def) {
  testException("attempting to define bad category $cat_def", "AClass::define_class('Bad', 'x', array(array('x', 'int', 'X'), array('bad_cat', '$cat_def', 'Bad Cat')),  NULL);");
}

echo "\nDefining class A\n";
AClass::define_class('A', 'a_key',
    array(array('a_key', 'char(10)', 'A Key'),
    array('a_cat', 'category(foo,bar)', 'A Categories'),
    array('multi_cats', 'category(foo_bar,foo_baz)', 'Multiple Categories')),
  NULL);

class A extends AnInstance {
  public function __construct($dbaccess, $attr_values = NULL)
  {
    parent::__construct('A', $dbaccess, $attr_values);
  } // end of __construct()
  
  public function __toString()
  {
    return "$this->a_key: $this->a_cat";
  } // end of __toString()
}
$a_class = AClass::get_class_instance('A');

echo $a_class->dump('A Class');

$a_class->create_table($dbaccess);
foreach (array('a1', 'a2', 'a3') as $an_a) {
  $a_obj[$an_a] = new A($dbaccess, $an_a);
  $a_obj[$an_a]->a_cat = 'foo_bar,foo_baz';
  $a_obj[$an_a]->save();
}
testTrue('checking a_cat category root is bar,foo, not foo,bar',
    $a_obj['a1']->get_prop('a_cat', 'category_root') == 'bar,foo');
testTrue('a_cat contains only foo_bar and foo_baz', $a_obj['a1']->a_cat == 'foo_bar,foo_baz');

$a_bad = new A($dbaccess, "bad");
testException("Attempt to assign non-subpath to category", 'global $a_bad;$a_bad->a_cat = "not_a_subpath";');


echo "\nDefining class B\n";
AClass::define_class('B', 'b_key',
  array(array('b_key', 'char(10)', 'B Key'), array('b_cat', 'category(foo)', 'B Categories')),
  NULL);
class B extends AnInstance {
  public function __construct($dbaccess, $attr_values = NULL)
  {
    parent::__construct('B', $dbaccess, $attr_values);
  } // end of __construct()
  
  public function __toString()
  {
    return "$this->b_key: $this->b_cat";
  } // end of __toString()
}
$b_class = AClass::get_class_instance('B');
$b_class->create_table($dbaccess);
foreach (array('b1', 'b2', 'b3') as $bn_b) {
  $b_obj[$bn_b] = new B($dbaccess, $bn_b);
  $b_obj[$bn_b]->b_cat = 'foo_bar,foo_bloop';
  $b_obj[$bn_b]->save();
}
$b_obj['b1']->add_category('b_cat', 'foo_bar_beep');
$b_obj['b2']->add_category('b_cat', 'foo_bar_beep_1');
$b_obj['b3']->add_category('b_cat', 'foo_bar_beep_2');

echo "\nAll A Objects:\n";
foreach ($a_obj as $a) {
  echo "$a\n";
}

echo "\nAll B Objects:\n";
foreach ($b_obj as $b) {
  echo "$b\n";
}

echo "\nListing of B instances in common Category of \$a->a_cat: $a->a_cat\n";
echo "expect 3 instances for category foo and foo_bar and zero for others\n";
$foo = new Category(Globals::$dbaccess, 'foo');
foreach ($foo->descendents() as $cat) {
  echo "B objects in category $cat:\n";
  foreach ($a->select_objects_in_category('a_cat', $cat, 'B') as $obj) {
    echo "   $obj\n";
  }
}

echo "\n" . '$a_obj[\'a1\']->a_cat = NULL;' . " - expect nothing afte colon\n";
$a_obj['a1']->a_cat = NULL;
$a_obj['a1']->save();
// echo $a_obj['a1']->dump("\nafter setting a_cat to NULL");
echo "{$a_obj['a1']}\n";

echo "\nvar_dump(\$a_obj['a1']->select_objects_in_category('a_cat', 'B'));\n - expect empty array\n";
var_dump($a_obj['a1']->select_objects_in_category('a_cat', 'foo', 'B'));

echo "\nadding foo_bloop, foo_bloop_1, and foo_bloop_2 to a2\n";
$a_obj['a2']->add_category('a_cat', 'foo_bloop');
$a_obj['a2']->add_category('a_cat', 'foo_bloop_1');
$a_obj['a2']->add_category('a_cat', 'foo_bloop_2');
$a_obj['a2']->save();
echo "a2: {$a_obj['a2']}\n";
echo "AJoin table _j1 for a2\n";
system('echo "select * from _j1 where _f1 = \'a2\';"  | sqlite sqlite_testdb');

echo "\nDeleting category foo_bar_beep\n";

echo "\nA & B Objects Before Deleting foo_bar_beep:\n";
foreach (array_merge($a_obj, $b_obj) as $b) {
  echo "$b\n";
}

echo "\nDatabase Before Delete\n";
echo "\ncategory table\n";
system('echo "select * from category order by parent, sibling_ordinal;"  | sqlite sqlite_testdb');

echo "\njoin table _j1\n";
system('echo "select * from _j1;"  | sqlite sqlite_testdb');

echo "\njoin table _j2\n";
system('echo "select * from _j2;"  | sqlite sqlite_testdb');


$cat_to_delete = new Category(Globals::$dbaccess, 'foo_bar_beep');
$cat_to_delete->delete();

echo "\nA & B Objects should NOT have any 'foo_bar_beep' categories:\n";
// This strange looking code compensates for the FACT that deleting a category
//  will invalidate synchronization between any effected AnInstance objects
//  which are currently in memory. See notes in aclass.php
foreach (array_merge($a_obj, $b_obj) as $b) {
  $obj_name = get_class($b);
  $obj = new $obj_name(Globals::$dbaccess, $b->decode_key_values($b->encode_key_values()));
  echo "$obj\n";
}

echo "\nDatabase After Delete\n";
echo "\ncategory table\n";
system('echo "select * from category order by parent, sibling_ordinal;"  | sqlite sqlite_testdb');

echo "\njoin table _j1\n";
system('echo "select * from _j1;"  | sqlite sqlite_testdb');

echo "\njoin table _j2\n";
system('echo "select * from _j2;"  | sqlite sqlite_testdb');



$cat_to_delete = new Category(Globals::$dbaccess, 'foo_bar');
echo "\nAttempt to delete Category foo_bar\n - should generate exception inasumch as used as category root\n";
try {
  $cat_to_delete->delete();
} catch (Exception $e) {
  echo "Delete Failed: Exception Caught\n";
  echo $e . "\n";
}

echo "\nDatabase After Delete Attempt\n";
system('echo "select * from category order by parent, sibling_ordinal;"  | sqlite sqlite_testdb');

echo "\n\$a_obj['a2']->delete_category('a_cat', 'foo_bloop')\n";
echo "Before: {$a_obj['a2']}\n";
$a_obj['a2']->delete_category('a_cat', 'foo_bloop');
echo "After: {$a_obj['a2']}\n";

echo "\nDatabase After Delete delete_category()\n";
system('echo "select * from category order by parent, sibling_ordinal;"  | sqlite sqlite_testdb');

testReport();
