{:php-prefix:}
// from inner_template.tpl
$inner = "inner var";$foo = "if you see this it is wrong";
$words[] = "words from inner_template.tpl";
{:end-php-prefix:}
{:yatemplate yatemplate.tpl:}
First line of Inner Template
Here is $foo: '{:$foo:}'
including inner_include.tpl
{:include inner_include.tpl:}
Content:
**********yatemplate-content starts*****************
{:yatemplate-content:}
**********yatemplate-content ends***************
Last Line of Inner Template
