{:php-setup:}$words[] = "words from content.tpl setup";{:end-php-setup:}
{:php-prefix:}
// from content.tpl
$foo = "this is a foo";
$words[] = 'words from content.tpl prefix';
{:end-php-prefix:}
This is the First Line of Content
{:yatemplate inner_template.tpl:}
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
{:include content-include.tpl:}
This is the Last Line of Content
