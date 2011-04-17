{:php-prefix:}
  set_include_path('../system/objects:../system/includes:' . get_include_path());
  require_once('YATheme.php');
{:end-php-prefix:}
Tests of miscellaneous syntax. Expect no more output
other than blank lines.

{:yatheme off:}
See {:this literally:}
{:yatheme on:}
{:errors email foo@example.com:}
{:errors ignore:}
{:errors display:}
<?php $php_var = 'this is a php var'; ?>
Including 'include-file.tpl':
------------------------------------
{:include include-file.tpl:}
------------------------------------
<?php $php_var = 'php_var defined AFTER including include-file'; ?>
Including 'include-file.tpl' again
----------------------------
{:include include-file.tpl:}
------------------------------
Failing Including 'bad-file.tpl':
------------------------------------
{:include bad-file.tpl:}
------------------------------------
{:authority ANY:}

{:yatheme off:}
{:yatheme on:}

Expirements:

What is $foo?: {: $foo | <?php echo ($foo = 'setting foo'); ?> :}
