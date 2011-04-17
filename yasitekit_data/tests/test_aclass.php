<?php
/*
#doc-start
h1.  test_aclass

Created by  on 2010-02-14.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

$progname = array_shift($argv);
$verbose = FALSE;
foreach ($argv as $arg) {
  switch ($arg) {
    case '-h': case '--help': echo "You're Helpless\n" ; exit(0) ; break;
    case '-v': case '--verbose': $verbose = TRUE ; break;
    default: echo "Illegal arg: '$arg'\n"; exit(1) ; break;
  }
}

// assert logic stolen from php.net/manual/en/function.assert.php
// Active assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

// Create a handler function
function my_assert_handler($file, $line, $code)
{
    echo "<hr>Assertion Failed:
        File '$file'<br />
        Line '$line'<br />
        Code '$code'<br /><hr />\n";
}

assert_options(ASSERT_CALLBACK, 'my_assert_handler');

// Set up the callback

// global variables
set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
require('test_common.php');
require('test_functions.php');
require_once('dbaccess.php');
global $dbaccess;

echo "$dbaccess\n";
$dbaccess->on_line = 'T';
$dbaccess->database_valid = 'T';
$dbaccess->archive_stale = 'F';
$dbaccess->model_mismatch = 'F';

require_once('aclass.php');

// AnEncryptor Test
echo "AnEncryptor Test\n";
$anencryptor = AnEncryptor::get_encryptor($dbaccess, 'foo');
echo "$anencryptor\n";
$error_count = 0;
$original_values = array('simple', 'more complex stuff', 12357, NULL);
$saved_encryptions = array();
foreach ($original_values as $val) {
  $saved_encryptions[$val] =
    $tmp = $anencryptor->encrypt($val);
  testTrue("Encryptor Round Trip for {$val}", $val == $anencryptor->decrypt($tmp));
}
AnEncryptor::flush_cache();

echo "\nTesting Save/Restore of Encryptor Parameters\n";
$anotherencryptor = AnEncryptor::get_encryptor($dbaccess, 'foo');
foreach ($saved_encryptions as $orig => $encrypted) {
  testTrue("Encryptor Round Trip through DB for ${orig}", $orig == $anotherencryptor->decrypt($encrypted));
}

echo "\nTesting AClassCmp\n";
class CmpTestClass {
  public function __construct($a,$b)
  {
    $this->a = $a;
    $this->b = $b;
  } // end of __construct()
  
  public function __toString()
  {
    return "$this->a, $this->b";
  } // end of __toString()
}
$ar = array(
  new CmpTestClass(1,2),
  new CmpTestClass(2,3),
  new CmpTestClass(2,2),
  new CmpTestClass(2,-1),
  );
function cmp_by_sum($a, $b) {
  $a_sum = $a->a + $a->b;
  $b_sum = $b->a + $b->b;
  return $a_sum - $b_sum;
}
foreach (array(
      array('a,b', array($ar[0], $ar[3], $ar[2], $ar[1],)),
      array('a,b:-', array($ar[0], $ar[1], $ar[2], $ar[3],)),
      array('b,a:desc', array($ar[3], $ar[2], $ar[0], $ar[1],)),
      array('cmp_by_sum', array($ar[3], $ar[0], $ar[2], $ar[1], )),
    ) as $row) {
  list($arg, $ar_expected) = $row;
  $srt = new AClassCmp($arg);
  $ar_tmp = $ar;
  usort($ar_tmp, $srt);
  if (!testTrue("Sort according to $srt", $ar_tmp == $ar_expected)) {
    print_r($ar_tmp);
    print_r($ar_expected);
  }
}

echo "Testing Bad Attribute Definitions\n";
$bad_class_defs = array(
  "array('text_var', 'text(12)', 'Bad Text')",
  "array('char_var', 'char(-1)', 'Bad Char')",
  "array('varchar_var', 'varchar(1024)', 'Bad VarChar')",
  "array('misspelled','misspelled', 'Misspelled')",
  );

foreach ($bad_class_defs as $defs) {
  testException("Class Def Error: {$defs}",
    "AClass::define_class('bad_class', 'foo', array(array('foo', 'int', 'Key'), $defs), NULL);");
}


echo "\n===================================================\n";
echo "Beginning AClass Testing\n";
// global variables
$test_class_def = array(
  array('blob_var', 'blob', 'Blob'),
  array("text_var", "text", "Text"),
  array("char_var","char(10)", "Char"),
  array("varchar_var", "varchar(200)", "Varchar"),
  array("enum_var", "enum(A, B , C )", "A B or C"),
  array('set_var', 'set(A,B,C)', 'set with elements A, B, and/or C'),
  array('file_var', 'file(file-path)', 'File Variable'),
  array("email_var", "email", "Email"),
  array("int_var", "int", "Int"),
  array("float_var", "float", "Float"),
  array("date_var", "date", "Date"),
  array("time_var", "time", "Time"),
  array("datetime_var", "datetime", "DateTime"),
  array('invisible_var', 'char(20)', 'Invisible Variable'),
  );
$test_class_properties = array(
  'invisible_var' => array('invisible')
  );

$test_class = AClass::define_class('TestClass', 'varchar_var', $test_class_def, $test_class_properties);
testTrue('__toString() test', "$test_class" == 'TestClass');


// $foo = AClass::get_class_instance('TestClass');
testTrue("get_class_instance(TestClass)", AClass::get_class_instance('TestClass') == $test_class);
$dbaccess->on_line = 'F';
$test_class->create_table(Globals::$dbaccess, TRUE);
$dbaccess->on_line = 'T';
testNoDBError('Creating Table for TestClass', Globals::$dbaccess);

// obj_to_db and db_to_obj translation test
date_default_timezone_set('America/Denver');
$translation_test_data = array(
  array('blob_var', 'blob'),
  array("text_var", "text"),
  array("char_var", "char(10)"),
  array("varchar_var", "varchar(199)"),
  array("enum_var", 'C'),
  array('set_var', 'A,C'),
  array('set_var', array('B','C')),
  array("file_var", 'file-path'),
  array("email_var", "name-foo.bar@sa.b.email.bar"),
  array("int_var", 14987),
  array("float_var", 12.5952),
  array("date_var", '2010-10-30'),
  array("time_var", '13:05:48'),
  array("datetime_var", "2010-10-30 13:05:48"),// "2010-10-30T13:05:48-06:00"),
  array("invisible_var", 'I am invisible'),
  );
echo "obj_to_db()/db_to_obj() round trip tests\n";
foreach ($translation_test_data as $row) {
  list($attr, $text) = $row;
  $db_version = $test_class->obj_to_db($attr, $text, $anencryptor);
  if (in_array($test_class->get_prop($attr,'type'), array('date', 'time', 'datetime'))) {
    $val = new DateTime($text);
  } elseif (in_array($test_class->get_prop($attr, 'type'), array('set', 'blob'))) {
    $val = $text;
  } else {
    $val = (string)$text;
  }
  if (!testTrue("$attr: $text", $val == $test_class->db_to_obj($attr, $db_version, $anencryptor))) {
    var_dump($val);
    var_dump($test_class->db_to_obj($attr, $db_version, $anencryptor));
  }
}

echo "End of AClass Testing\n";

// instance testing

echo "===================================================\n";
echo "Start of AnInstance Testing\n";

class TestClass extends AnInstance {
  public function __construct($dbaccess, $attr_values = array()) {
    parent::__construct('TestClass', $dbaccess, $attr_values);
  } // end of __construct()
}

$test_instance = new TestClass($dbaccess);

echo "\n<pre>\nTest Instance:\n";
foreach ($translation_test_data as $row) {
  list($attr, $value) = $row;
  $test_instance->$attr = $value;
  // echo "{$test_instance->get_prop($attr, 'title')}: {$test_instance->asString($attr)}\n";
  switch ($test_instance->get_prop($attr, 'type')) {
    case 'set':
      $test_value = is_string($value) ? preg_split('/\s*,\s*/', $value) : $value;
      if (!testTrue($test_instance->get_prop($attr, 'title'), array_values($test_instance->$attr) == $test_value)) {
        echo "Object: "; var_dump(array_values($test_instance->$attr));
        echo "Expected: "; var_dump(array_values($test_value));
      }
      break;
    case 'blob':
      if (!testTrue($test_instance->get_prop($attr, 'title'), $test_instance->$attr == $value)) {
        echo "Object: "; var_dump($test_instance->$attr);
        echo "Expected: "; var_dump($value);
      }
      break;
    case 'pile':
      // tested separately
      break;
    default:
      if (!testTrue($test_instance->get_prop($attr, 'title'), $test_instance->asString($attr) == $value)) {
        echo "Object: {$test_instance->asString($attr)} / Expected $value\n";
      }
      break;
  }
}

$test_instance->date_var = "1999-12-31";
testTrue('change date_var to 1999-12-31', $test_instance->asString('date_var') == '1999-12-31');
// echo 'date is supposed to be 1999-12-31: ' . $test_instance->asString('date_var') . "\n";
$test_instance->time_var = "10:30:02 pm";
// echo 'time is supposed to be 22:30:02 ' . $test_instance->asString('time_var') . "\n";
testTrue('change time_var to 10:30:02 pm', $test_instance->asString('time_var') == '22:30:02');

if ($verbose) {
  echo $test_instance->render();
  echo "\n";
  echo $test_instance->form();
  echo "\n";
}

$test_instance->save();
testNoDBError('Look for DB Error after Save', Globals::$dbaccess);

// php_create_string() is now a class variable which writes to a directory - must
// be tested separately
// echo "------------------\n Text to Create this instance\n";
// echo $test_instance->php_create_string();

echo "Getting new instance of test_instance into variable 'retrieved'\n";
$retrieved = new TestClass($dbaccess, array('varchar_var' => $test_instance->varchar_var));

echo "checking ALL fields\n";
if (!testTrue('retrieved equal test_instance', $retrieved->equal($test_instance, TRUE))) {
  echo $test_instance->dump('Original test_instance');
  echo $retrieved->dump('Retrieved test_instance from database as retrieved');
}
testTrue('test_instance equal retrieved', $test_instance->equal($retrieved, TRUE));
echo "checking only fields which are set\n";
if (!testTrue('retrieved equal test_instance', $retrieved->equal($test_instance, FALSE))) {
  echo $test_instance->dump('Original test_instance');
  echo $retrieved->dump('Retrieved test_instance from database as retrieved');
}
testTrue('test_instance equal retrieved', $test_instance->equal($retrieved, FALSE));
echo "checking with == and !== operators\n";
testTrue('test_instance == retrieved', $test_instance == $retrieved);
testFalse('test_instance != retrieved', $test_instance != $retrieved);
testFalse('test_instance === retrieved', $test_instance === $retrieved);
testTrue('test_instance !== retrieved', $test_instance !== $retrieved);

$retrieved->text_var = 'This is different text';
testFalse('test_instance == retrieved after mod text_var', $test_instance->equal($retrieved));
$retrieved->save();

echo "\nTesting object creation via instantiation\n";
AClass::define_class('A', 'a_key',
  array(array('a_key', 'int', 'Key'), array('data', 'varchar(255)', 'Data')),
  NULL);
$a_class_instance = AClass::get_class_instance('A');
$dbaccess->on_line = 'F';
$a_class_instance->create_table($dbaccess);
$dbaccess->on_line = 'T';
class A extends AnInstance {
  public function __construct($dbaccess, $attr_ar = NULL)
  {
    parent::__construct('A', $dbaccess, $attr_ar);
  } // end of __construct()
}
$a = new A($dbaccess, array('a_key' => 1, 'data' => 'first data'));
$a->save();
$b = new A($dbaccess, 1);
testTrue('Test Auto Creation of AnInstance Object', $a == $b);

testTrue("interpolate string test", $a->interpolate_string('{a_key} {data}') == '1 first data');
testTrue("prefixed field interpolation test",  $a->interpolate_string('{A.a_key} {A.data}') == '1 first data');
testTrue("mixed prefixed and not interpolation string test",
  $a->interpolate_string('{A.a_key} {a_key} {data} {A.data}') == '1 1 first data first data');

$fname = "/tmp/testfile";
file_put_contents($fname, "{A.a_key} {a_key} {data} {A.data}");
testTrue("file interpolation", $a->render_file($fname) == '1 1 first data first data');
$include_path = get_include_path();
set_include_path('/tmp/');
testTrue("include interpolation", $a->render_include(basename($fname)) == '1 1 first data first data');
set_include_path($include_path);
unlink($fname);

echo "===================================================\n";
echo "Start of Pile Testing\n";

$dbaccess->on_line = 'F';
AClass::define_class('WithPile', 'key1',
  array(
    array('key1', 'varchar(10)', 'Key Field'),
    array('data', 'varchar(10)', 'Data Field'),
    array('pile1', 'pile', 'Pile Field 1'),
    array('pile2', 'pile', 'Pile Field 2'),
  ), NULL)->create_table($dbaccess, TRUE, FALSE);
testNoDBError('Created table for WithPile', Globals::$dbaccess);
$dbaccess->on_line = 'T';

class WithPile extends AnInstance {
  public function __construct($dbaccess, $attr_values = array()) {
    parent::__construct('WithPile', $dbaccess, $attr_values);
  } // end of __construct()
}
$with_pile = new WithPile(Globals::$dbaccess, array('key1' => 'key1 value', 'data' => 'data value'));

$test_data = array(
  'pile1' => array(
    'p11' => 'foo',
    'p12' => 'bar'
  ),
  'pile2' => array(
    'p21' => 'foobar',
    'p22' => 'barfoo',
    'p23' => 'barbarbar',
  ),
);

foreach ($test_data as $attr => $ar) {
  foreach ($ar as $key => $val) {
    $with_pile->pile_put($attr, $key, $val);
  }
}
$with_pile->save();
var_dump($with_pile->get_prop('pile1', 'set'));
$db_with_pile = new WithPile(Globals::$dbaccess, 'key1 value');
foreach ($with_pile->attribute_names as $attr) {
  if (!testTrue("$attr retrieved", $with_pile->$attr == $db_with_pile->$attr)) {
    echo "in core: "; var_dump($with_pile->$attr);
    echo "from db: "; var_dump($db_with_pile->$attr);
  }
}
foreach ($test_data as $attr => $ar) {
  $other_attr = $attr == 'pile1' ? 'pile2' : 'pile1';
  foreach ($ar as $key => $val) {
    testTrue("$key is in pile $attr", in_array($key, $db_with_pile->pile_keys($attr)));
    testTrue("pile_get($attr, $key) is $val", $db_with_pile->pile_get($attr, $key) == $val);
    testFalse("$key is not in pile $other_attr", in_array($key, $db_with_pile->pile_keys($other_attr)));
    testFalse("pile_get($attr, $key) is False inf $other_attr", $db_with_pile->pile_get($other_attr, $key));
  }
}

echo "===================================================\n";
echo "Start of AJoin Testing\n";

// join testing
$dbaccess->on_line = 'F';
AClass::define_class('Class1', array('key1'),
  array(
    array('key1', 'varchar(10)', 'Key Field'),
    array('stuff', 'text', 'Stuff'),
  ), NULL)->create_table($dbaccess, TRUE, FALSE);

class Class1 extends AnInstance {
  public function __construct($dbaccess, $attr_values = array())
  {
    parent::__construct('Class1', $dbaccess, $attr_values);
  } // end of __construct()
}
AClass::define_class('Class2', array('key1', 'key2'),
  array(
    array('key1', 'varchar(10)', 'Key 1 Field'),
    array('key2', 'varchar(10)', 'Key 2 Field'),
    array('stuff', 'text', 'Stuff'),
  ), NULL)->create_table($dbaccess, TRUE, FALSE);
$dbaccess->on_line = 'T';
class Class2 extends AnInstance {
  public function __construct($dbaccess, $attr_values = array())
  {
    parent::__construct('Class2', $dbaccess, $attr_values);
  } // end of __construct()
}

$c1_1 = new Class1($dbaccess, array('key1' => 'one', 'stuff' => 'one stuff'));
$c1_1->save();
$c1_2 = new Class1($dbaccess, array('key1' => 'two', 'stuff' => 'two stuff'));
$c1_2->save();
$c1_3 = new Class1($dbaccess, array('key1' => 'three', 'stuff' => 'three stuff'));
$c1_3->save();

$c2_1 = new Class2($dbaccess, array('key1' => '1', 'key2' => '2', 'stuff' => '2 stuff'));
$c2_1->save();
$c2_2 = new Class2($dbaccess, array('key1' => '1', 'key2' => '3', 'stuff' => '2 stuff'));
$c2_2->save();
$c2_3 = new Class2($dbaccess, array('key1' => '1', 'key2' => '4', 'stuff' => '2 stuff'));
$c2_3->save();
$c2_4 = new Class2($dbaccess, array('key1' => '2', 'key2' => '2', 'stuff' => '2 stuff'));
$c2_4->save();

AJoin::destroy_all_joins($dbaccess);
$join = AJoin::get_ajoin($dbaccess, 'Class1', 'Class2');
testTrue("Created join for Class1 and Class2", $join instanceof AJoin);

$join->add_to_join($c1_1, $c2_1);
$join->add_to_join($c1_2, $c2_2);
$join->add_to_join($c1_2, $c2_3);
$join->add_to_join($c1_2, $c2_4);

echo "(string)join: $join\n";

// echo $join->dump('AJoin Dump');
// // print_r($foo);
// foreach ($join->select_joined_objects($c1_2) as $c2) {
//   echo $c2->dump('Join Result');
// }
// echo $join->dump("join before deleting joins for c1_2\n");

foreach (array(
    array($c1_1, $c2_1, TRUE),
    array($c1_1, $c2_2, FALSE),
    array($c1_1, $c2_3, FALSE),
    array($c1_1, $c2_4, FALSE),
    array($c1_2, $c2_1, FALSE),
    array($c1_2, $c2_2, TRUE),
    array($c1_2, $c2_3, TRUE),
    array($c1_2, $c2_4, TRUE)
  ) as $row) {
  list($left, $right, $expect) = $row;
  // print "$left <-> $right: " . ($expect?'Related':'Nothing') . "\n";
  if ($expect) {
    testTrue("$left joined to $right", $join->in_joinP($left, $right));
  } else {
    testTrue("$left not joined to $right", !$join->in_joinP($left, $right));
  }
}

$join->delete_joins_for($c1_2);

echo "After deleting \$c1_2\n";

foreach (array(
    array($c1_1, $c2_1, TRUE),
    array($c1_1, $c2_2, FALSE),
    array($c1_1, $c2_3, FALSE),
    array($c1_1, $c2_4, FALSE),
    array($c1_2, $c2_1, FALSE),
    array($c1_2, $c2_2, FALSE),
    array($c1_2, $c2_3, FALSE),
    array($c1_2, $c2_4, FALSE)
  ) as $row) {
  list($left, $right, $expect) = $row;
  // print "$left <-> $right: " . ($expect?'Related':'Nothing') . "\n";
  if ($expect) {
    testTrue("$left joined to $right", $join->in_joinP($left, $right));
  } else {
    testTrue("$left not joined to $right", !$join->in_joinP($left, $right));
  }
}


$dbaccess->on_line = 'F';
AJoin::destroy_all_joins($dbaccess);
testFalse('_j1 table removed', $dbaccess->table_exists('_j1'));
$dbaccess->on_line = 'T';

echo "===================================================\n";
echo "Start of 'join' field testing Testing\n";

$joiner_class = AClass::define_class('Joiner', 'key_field',
  array(
    array('key_field', 'varchar(255)', 'Key Field'),
    array('data_field', 'varchar(255)', 'Data Field'),
    array('join_field', 'join(Joined.display_name, multiple)', 'Join Field'),
    array('join_limit', 'int', 'Max Key of Joined Field')
    ),
  array(
    'join_field' => array('where' =>'key_name <= {join_limit}'),
    'join_limit' => array('default' => 100),
    ));
$dbaccess->on_line = 'F';
$joiner_class->create_table($dbaccess);
$dbaccess->on_line = 'T';

class Joiner extends AnInstance {
  public function __construct($dbaccess, $attr_values)
  {
    parent::__construct('Joiner', $dbaccess, $attr_values);
  } // end of __construct()
}

$joined_class = AClass::define_class('Joined', 'key_name',
  array(
    array('key_name', 'int', 'Key'),
    array('display_name', 'varchar(255)', 'Display Name')
    ),
  NULL);
$dbaccess->on_line = 'F';
$joined_class->create_table($dbaccess);
$dbaccess->on_line = 'T';

class Joined extends AnInstance {
  public function __construct($dbaccess, $ar = array())
  {
    parent::__construct('Joined', $dbaccess, $ar);
  } // end of __construct()
}

// dump both test classes
// echo $joiner_class->dump("=======================================");
// echo $joined_class->dump("=======================================");


// create some instances
$joiner = new Joiner($dbaccess, array('key_field' => 'key1', 'data_field' => 'data value 1'));
$joiner->save();

$joined_ar = array();
for ($i=1;$i<12;$i++) {
  $joined_ar[] =
    $tmp = new Joined($dbaccess, array('key_name' => $i, 'display_name' => "N $i"));
  $tmp->save();
  // echo $tmp->dump("======================= $i ===========");
}


$joiner->join_field = $joined_ar[0];
testTrue('Assign join via instance', $joiner->join_field[0] == $joined_ar[0]);

$joiner->join_field = $joined_ar[1]->key_name;
testTrue('Assign join via key value', $joiner->join_field[0] == $joined_ar[1]);

$joiner->join_field = array('key_name' => $joined_ar[2]->key_name);
testTrue('Assign join via array(key => value)', $joiner->join_field[0] == $joined_ar[2]);

$joiner->join_field = $joined_ar[3]->encode_key_values();
testTrue('Assigned via encoded key values', $joiner->join_field[0] == $joined_ar[3]);

$joiner->join_field = $joined_ar;
$tmp = count(array_merge(array_diff($joiner->join_field, $joined_ar), array_diff($joined_ar, $joiner->join_field)));
testTrue('Assign join via array of instances', $tmp == 0);

$joiner->delete_from_join('join_field', array(1, array('key_name' => 4), $joined_ar[8]));
testTrue('after deleting joined values 1, 4, and 9', count($joiner->join_field) == 8);
for ($idx = 0;$idx < count($joined_ar); $idx++) {
  $joined = $joined_ar[$idx];
  switch ($idx) {
    case 0:
    case 3:
    case 8:
      testFalse("{$joined->display_name} Present (should be FALSE)", in_array($joined, $joiner->join_field));
      break;
    default:
      testTRUE("{$joined->display_name} Present (should be TRUE)", in_array($joined, $joiner->join_field));
      break;
  }
}

echo "Testing Retreived Joiner\n";
$foo = new Joiner($dbaccess, 'key1');
testTrue('join_field as string matches', $foo->asString('join_field') == $joiner->asString('join_field'));
testTrue('join_field values match', $foo->join_field == $joiner->join_field);

$joiner->add_to_join('join_field', $joined_ar[0]);
testTrue("Added back {$joined_ar[0]}", in_array($joined_ar[0], $joiner->join_field));
for ($idx = 0;$idx < count($joined_ar); $idx++) {
  $joined = $joined_ar[$idx];
  switch ($idx) {
    // case 0:
    case 3:
    case 8:
      testFalse("{$joined->display_name} Present (should be FALSE)", in_array($joined, $joiner->join_field));
      break;
    default:
      testTRUE("{$joined->display_name} Present (should be TRUE)", in_array($joined, $joiner->join_field));
      break;
  }
}

$joiner->add_to_join('join_field', $joined_ar);
$tmp = count(array_merge(array_diff($joiner->join_field, $joined_ar), array_diff($joined_ar, $joiner->join_field)));
testTrue('Added everything back in', $tmp == 0);

$joiner->join_limit = 5;
$select_elt = $joiner->select_elt_join_list('join_field', 'display_name');
// 2 for <select...> and </select>, 5 for <option></option>
testTrue('reassigned join_field with join_limit == 5', count(explode("\n", $select_elt)) == 2 + 5);

// Link tests
echo "===================================================\n";
echo "Start of link data type Testing\n";

AClass::define_class('Linker', 'a_key',
  array(
      array('a_key', 'int', 'Key'),
      array('a_link', 'link(Joined.display_name)', 'Link'),
    ),
    NULL);
$link_class_obj = AClass::get_class_instance('Linker');
$dbaccess->on_line = 'F';
$link_class_obj->create_table($dbaccess);
$dbaccess->on_line = 'T';
class Linker extends AnInstance {
  public function __construct($dbaccess, $attr_values = array()) {
    parent::__construct('Linker', $dbaccess, $attr_values);
  }
}

$link = new Linker($dbaccess, array('a_key' => 1, 'a_link' => $joined_ar[5]->key_name));
testTrue('Linked to joined_ar[5]', $link->a_link == $joined_ar[5]->key_name);
testTrue('Link value_of is joined_ar[5]', $link->link_value_of('a_link') == $joined_ar[5]);
testFalse('Link value_of is NOT joined_ar[6]', $link->link_value_of('a_link') == $joined_ar[6]);

$link->a_link = $joined_ar[6];
testTrue('Linked to joined_ar[6]', $link->a_link == $joined_ar[6]->key_name);
testFalse('Link value_of is NOT joined_ar[5]', $link->link_value_of('a_link') == $joined_ar[5]);
testTrue('Link value_of is joined_ar[6]', $link->link_value_of('a_link') == $joined_ar[6]);
testException('Attempt to assign illegal type to link', '$link=new Linker(Globals::$dbaccess);$link->a_link = $link;');

testReport();
