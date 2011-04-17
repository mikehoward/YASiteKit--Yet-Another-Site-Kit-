<?php
/*

h3. Test Functions

* testReset() - resets test count and error count to zero
* testReport() - prints a two line summary - number of tests and number
of failures.

The following functions print a test result message and increment the counters.

* testTrue(message, value) - print Pass if _value_ is TRUE else Fail followed by
message.
* testFalse(message, value) - prints Pass if _value_ is FALSE, else Fail
* testNoDBError(message, $dbaccess) - prints Pass message if $dbaccess->errorP()
returns TRUE - indicating that the last database operating completed successfully.
Else Fail
* testDBError(message, $dbaccess) - reverses testNoDBError()
* testException(message, $code) - executes _$code_ using eval() inside a try ... catch
construct. Prints Pass if _$code_ generates an exception, otherwise Fail. Couple
of Gotchas:
** $code must be syntactically correct PHP - including semicolons
** $code must NOT include and php escapes (&lt;?php)
** $code must include 'global' directives if you need to access a global variable,
like: "global $dbaccess;$dbaccess->method();"
* testNoException(message, $code) - the reverse of testException(). Same considerations
apply.

Utilities

* test_helper(message, value) - does the actual work for most of the test result functions.
Use if you want to add a test so we keep all the message headers and counters in one place.
* ignore_exception() - an exception handler which does nothing. Useful if you have some
exception handling buried deep enough that a try ... catch ... be able to clean up
any undesired output. If you use it, follow with a _restore_exception_handler()_ as
soon as possible to avoid losing interesting error reports.

*/
set_include_path('..' . PATH_SEPARATOR . get_include_path());
require_once('config.php');
// require('test_common.php');
require_once('test_functions.php');
// require_once('includes.php');
global $dbaccess;

echo "$dbaccess\n";

require_once('yastream.php');
// $incl_save = get_include_path();
// set_include_path( '..' . DIRECTORY_SEPARATOR . 'installation' . DIRECTORY_SEPARATOR
//   . 'sub_systems' . PATH_SEPARATOR . $incl_save);
// include('codes_for_currency_country.php');
// set_include_path($incl_save);

// YAMemFile tests
echo "YAMemFile\n";
// NOTE: there is a trick here. accessing 'content' sets atime (it's an access), so it has to
//  go last in the list
$attrs = array('varname', 'uid', 'gid', 'mode', 'atime', 'mtime', 'ctime', 'blksize', 'size', 'blocks', 'content', );
$mf1_attrs = array(
    'varname' => 'foo',
    'uid' => getmyuid(),
    'gid' => getmygid(),
    'mode' => 0644,
    'atime' => NULL,
    'mtime' => NULL,
    'ctime' => time(),
    'blksize' => 512,
    'size' => 0,
    'blocks' => 0,
    'content' => '',
    );
$mf1 = YAMemFile::get_yamemfile_var('foo');
// echo $mf1->dump();
echo "Attributes after get_yamemfile_var() and before open()\n";
foreach ($attrs as $key) {
  $val = $mf1_attrs[$key];
  testTrue("$key == '$val'", $mf1->$key == $val);
}
testTrue("atime is now set after accessing content", $mf1->atime == time());

$mf1->open();
testTrue("mf1->atime is now", $mf1->atime == time());
testTrue("mf1->open_count is 1", $mf1->open_count == 1);

$mf2 = YAMemFile::get_yamemfile_var('foo');
foreach (array_merge($mf1_attrs, array('atime' => time())) as $key => $val) {
  testTrue("mf1->$key == mf2->$key", $mf1->$key == $mf2->$key);
  testTrue("mf2->$key == $val", $mf2->$key == $val);
}
testTrue("mf1->open_count == 1", $mf1->open_count == 1);
$mf2->open();
testTrue("after mf2->open: mf1->open_count == 2", $mf1->open_count == 2);

$data = 'This is some data';
$mf1->write_data(0, $data);
testTrue("mf1->content == '$data'", $mf1->content == $data);
testTrue("mf1->size == strlen(data)", $mf1->size == strlen($data));
testTrue("mf1->blocks == 1", $mf1->blocks == 1);
foreach ($attrs as $attr) {
  testTrue("mf1->$attr == mf2->$attr", $mf1->$attr == $mf2->$attr);
}
// echo $mf1->dump();

$mf1->close();
testTrue("after mf1->close(): mf2->open_count == 1", $mf2->open_count == 1);
testFalse("attempt to unlink mf2 fails", YAMemFile::unlink("var://" . $mf2->varname));
$mf2->truncate(2);
testTrue("after truncate(2), mf2->content == 'Th'", $mf2->content == 'Th');
testTrue('mf2->size == 2', $mf2->size == 2);
testTrue('mf2->blocks == 1', $mf2->blocks == 1);

if (FALSE):  // beginning of timer counts - slows testing, so shut off for now
$atime = $mf2->atime;
echo "\nSleeping 1 second"; sleep(1);echo "\n";
echo $mf2->read_data(0, 100) . "\n";
testTrue("mf2->atime == " . ($atime + 1), $mf2->atime == $atime + 1);

echo "stat() tests\n";
$stat = $mf2->stat();
foreach ($stat as $key => $val) {
  if (!is_int($key)) {
    testTrue("mf2->$key: {$mf2->$key} == $val", $mf2->$key == $val);
  }
}

echo "\nSleeping 1 second"; sleep(1); echo "\n";
$mf_other = YAMemFile::get_yamemfile_var('bar');

foreach (array('inode', 'varname', 'size', 'ctime', 'atime', 'mtime', 'content') as $attr) {
  testTrue("$attr: {$mf_other->$attr} != {$mf2->$attr}", $mf_other->$attr != $mf2->$attr);
}
endif;  // timer counts

$mf2->close();
testTrue("mf2 link count 0", $mf2->nlink == 0);
// echo $mf2->dump('mf2');
// echo $mf_other->dump('mf_other');

echo "\nYAStream Tests\n";
testTrue("contents of 'foo' is 'Th'" ,file_get_contents('var://foo') == 'Th');
$foo = YAMemFile::get_yamemfile_var('foo');
testTrue("foo->open_count == 0", $foo->open_count == 0);;
testTrue("foo->nlink == 0", $foo->nlink == 0);

// create some random data
$data = '';
for ($idx=0;$idx < 16000;$idx++) {
  $data .= chr(rand(32,186));
}

// test stream_write();
file_put_contents('var://bar', $data);
testTrue('data saved in bar', $data == file_get_contents('var://bar'));
$bar = YAMemFile::get_yamemfile_var('bar');

$foo_as_file = fopen('var://bar', 'r');
// test stream_flush()
testTrue('stream_flush() is TRUE', fflush($foo_as_file) == TRUE);

// test stream_tell()
testTrue('initial ftell() is 0', ftell($foo_as_file) == 0);
// test stream_eof()
testFalse('initial feof() is FALSE', feof($foo_as_file));

// test stream_seek()
fseek($foo_as_file, 100);
testTrue('ftell() is now 100', ftell($foo_as_file) == 100);
fseek($foo_as_file, 0, SEEK_END);
testTrue('ftell() is now bar->size', ftell($foo_as_file) == $bar->size);
testTrue('now feof() is TRUE', feof($foo_as_file));

fseek($foo_as_file, -100, SEEK_END);
testTrue('ftell is now bar->size - 100', ftell($foo_as_file) == $bar->size - 100);
testFalse('now feof() is FALSE', feof($foo_as_file));

fseek($foo_as_file, -100, SEEK_CUR);
testTrue('ftell is now bar->size - 200', ftell($foo_as_file) == $bar->size - 200);

// test stream_stat()
testTrue('fstat(foo_as_file) == bar->stat()', fstat($foo_as_file) == $bar->stat());
testTrue('stat(var://bar) == bar->stat()', stat('var://bar') == $bar->stat());

// test url_stat()
$empty_stream = new YAStream();
testTrue('url_stat() == bar->stat()', $empty_stream->url_stat('var://bar') == $bar->stat());

// test rename()
$empty_stream->rename('bar', 'baz');
testTrue('bar->varname == baz', $bar->varname == 'baz');

// test unlink()
$empty_stream->unlink('var://foo');
$foo = YAMemFile::get_yamemfile_var('var://foo', FALSE);
testFalse('foo should be gone', $foo);
if ($foo) echo $foo->dump(__LINE__);

// test put to file and re-get to make sure it works and rewinds after closed
file_put_contents('var://foo.php', "<\x3fphp echo 'syntax error \x3f>\n");
testTrue('foo.php content valid', file_get_contents('var://foo.php') == "<\x3fphp echo 'syntax error \x3f>\n");
testTrue('var://foo.php auto-rewind on new open', file_get_contents('var://foo.php') == file_get_contents('var://foo.php'));



testReport();
return;
