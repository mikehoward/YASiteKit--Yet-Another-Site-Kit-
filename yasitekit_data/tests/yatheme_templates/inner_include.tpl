{:php-prefix:}
// from inner_include.tpl
$words[] = 'words from inner_include.tpl';
{:end-php-prefix:}
-----------------------------
<p>This is inner_template.tpl content</p>
Including incl2.tpl
{:include incl2.tpl:}
-----------------------------
