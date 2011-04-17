<?php
/*
#doc-start
h1.  test_acurl

Created by  on 2010-03-29.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


#end-doc
*/

// global variables

set_include_path('../includes' . PATH_SEPARATOR . get_include_path());
require_once('acurl.php');

function test_result($msg, $value, $expected)
{
  echo "$msg: $value\n";
  if ($value != $expected) {
    echo "ERROR: $value != $expected\n";
    return 1;
  } else {
    return 0;
  }
} // end of test_result()

// ACurlData test
if (TRUE):
echo "================= ACurlData Tests ================\n";
$fail = 0;
$empty = new ACurlData();
if (!$empty->emptyP()) {
    $fail += 1;
  echo "ERROR: empty ACurlData object does not test false\n";
}

$acurldata = new ACurlData('foo', 'foo-value', 'bar', 'bar value');
echo "key case: {$acurldata->key_case()}\n";
$fail += test_result('default ACurlData()', "$acurldata", 'foo=foo-value&bar=bar+value');

$ar = array(array('a', TRUE, 'foo=foo-value&bar=bar+value&a'),
  array('boing', 12.3, 'foo=foo-value&bar=bar+value&a&boing=12.3'),
  );
foreach ($ar as $row) {
  list($key, $val, $expected) = $row;
  $acurldata->$key = $val;
  $fail += test_result("\$acurldata->$key", "$acurldata", $expected);
}
$fail += test_result("\$acurldata->foo", $acurldata->foo, 'foo-value');

foreach (array(array('mixed', 'foo=foo+%22value%22&BAR=%2Fbar+value%27'),
  array('lower', 'foo=foo+%22value%22&bar=%2Fbar+value%27'),
  array('upper', 'FOO=foo+%22value%22&BAR=%2Fbar+value%27')) as $row) {
  list($key_case, $expected) = $row;
  $acurldata = new ACurlData($key_case, 'foo', 'foo "value"', 'BAR', "/bar value'");
  echo "key case: {$acurldata->key_case()}\n";
  
  $fail += test_result("\$acurldata->asString()", "$acurldata", $expected);
  $fail += test_result("\$acurldata->foo", $acurldata->foo, 'foo "value"');
}

echo ($fail ? "Failed $fail Tests\n" : "All Tests Passed\n");
endif;

// remote test using yasitekit.org
if (FALSE):
$acurl = new ACurl('http://www.yasitekit.org');
$acurl->verbose = TRUE;

echo $acurl->dump('simple dump');
echo "\n===========GET Test==============\n";
var_dump($acurl->get('/','foo', 'bar', 'bar', 'baz'));
echo "\n===========POST Test==============\n";
var_dump($acurl->post_query('/', 'foo', 'bar', 'bar', 'baz'));
endif;



// couchdb test
if (TRUE):
$acurl = new ACurl('http://127.0.0.1:5984');
$acurl->include_headers = TRUE;
//$acurl->include_headers = TRUE;
echo "\n=================== RUTHERE Test============\n";
var_dump($acurl->get('/'));
echo $acurl->dump('RUTHERE Test');

echo "\n=================== GET Test ===============\n";
var_dump($acurl->get('/_all_dbs'));
var_dump($acurl->get('/foo'));
echo $acurl->dump('GET Test');

echo "\n=================== Delete Test============\n";
var_dump($acurl->delete('/foo'));
var_dump($acurl->get('/_all_dbs'));
var_dump($acurl->get('/foo'));
echo $acurl->dump('DELETE Test');

echo "\n=================== Put Test============\n";
var_dump($acurl->put_file('/foo'));
var_dump($acurl->get('/_all_dbs'));
echo $acurl->dump('PUT Test');

echo "===================== POST Test ==============\n";
$doc_data = json_encode(array('foo' => 'bar', 'twelve' => 12));
$rsp = $acurl->post_data('/foo', $doc_data);
echo $rsp;
$rsp_ar = json_decode($rsp);
echo $acurl->get('/foo/' . $rsp_ar->id);

echo "\n=================== Put Test with specified id ============\n";
$str = $acurl->get('/_uuids');
echo "$str\n";
$ar = json_decode($str);
var_dump($ar);
$doc_1 = "/foo/" . $ar->uuids[0];

$doc_data = json_encode(array('foo' => 'foo two', 'twelve' => 14));
var_dump($doc_data);
echo $acurl->put_data($doc_1, $doc_data);
echo $acurl->get($doc_1);
return;
echo $acurl->get('/foo');
endif;
?>
