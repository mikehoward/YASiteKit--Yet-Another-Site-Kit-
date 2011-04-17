<?php

set_include_path('../objects' . PATH_SEPARATOR . get_include_path());

require_once('PageSeg.php');

// echo "Testing Exceptions:\n";
// foreach ( array(
//     "new PageSeg();",
//     "new PageSeg('foo');new PageSeg('foo');",
//   ) as $stmt) {
//   echo $stmt . "\n";
//   $eval_stmt = "try { eval(\"$stmt\"); echo \"Error: Failed to cause Exception: $stmt\\n\"; } catch (Exception \$e) {}";
//   eval($eval_stmt);
// }
//   
// return;

if (TRUE):
$simple_text_slice = new PageSegText('page_text', "some text\nin two lines\n");
echo "simple_text_slice:\n" . $simple_text_slice->content;
$simple_text_slice->open();
echo "Should not see this: Adding some more text\n";
echo "simple_text_slice: $simple_text_slice\n";

$simple_text_slice->close();
echo "\n simple_text_slice closed\n";

echo $simple_text_slice->render();

// echo "simple_text_slice:: $simple_text_slice\n";
$foo = PageSeg::get_by_name('page_text');
echo "\n\nPageSeg::get_by_name('page_text'): '$foo'\n\n";
endif;

if (TRUE):
$file_seg = new PageSegFile('file_seg', 'page_seg_file.html');

echo "$file_seg\n";

echo "Dump of segment '$file_seg->name'\n";
echo $file_seg->dump();
endif;

if (TRUE):
$l = new PageSegList('list', new PageSegText('a', "aaaaa\naaaaaa"),
  new PageSegFile('same_file', 'page_seg_file.html'),
  new PageSegText('b', "bbbbbbb\nbbbbb"));
echo "\nThis is a simple PageSegList:\n";
echo $l->render();
$l->prepend(new PageSegText('doctype', 'this is a fake doctype'), 'b');
echo "\nPageSegList after prepend,\n";
echo "$l";
$l->append(new PageSegText('trailer', 'This is the Trailer'), 'b');
echo "\nPageSegList after prepend and append\n";
echo "$l";
$l->insert_before(1, new PageSegText('x', 'xxxx'), 'x', 'x');
echo "\nPageSegList after prepend, append, and insert\n";
echo $l->render();
echo "index of 'x': " . $l->get_index_of('x') . "\n";
echo "index of 'a': " . $l->get_index_of('a') . "\n";
echo "index of 'doctype': " . $l->get_index_of('doctype') . "\n";
$l->insert_after('doctype', 'a');
$l->insert_before('doctype', 'a');
echo "$l";
echo "\n------- Start Dump Test ------\n";
echo $l->dump();
echo "-------- end dump test --------\n";


$e = new PageSegElt('elt', 'div',
  'class=foo',
  PageSeg::get_by_name('a'),
  'banana',
  new PageSegText('bb', "bbbbbbb\nbbbbb"),
  $l);
echo "\nThis is a simple PageSegElt:\n";
echo $e->render();
echo "\n-------- start of dump---------\n";
echo $e->dump();
echo "-----------end of dump-----------\n";
endif;


?>