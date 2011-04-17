{:php-prefix:}
// from yatemplate.tpl
$words[] = "words from yatemplate.tpl";
{:end-php-prefix:}
{:include header.tpl:}
This is a Template
The PHP var $foo: '{:$foo:}'
The inner var: '{:$inner:}'

<?php
/* PHP Comments in template */
?>

Template content starts here:
------------yatemplate-content starts---------------
{:yatemplate-content:}
------------yatemplate-content ends---------------
Template content just ended
<?php    echo     "this is echoed from php\n";     ?>
This is the last line of the template
{:include footer.tpl:}

Here are the words, in order of occurance in the prefix:
<pre>
<?php echo implode("\n", $words); ?>
</pre>