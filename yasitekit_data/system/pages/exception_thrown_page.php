<?php
/*
#doc-start
h1.  exception_thrown_page.php - displayed when the system throws an exception

Created by  on 2010-04-14.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

?>
<?php
  Globals::$page_obj->page_title = Globals::$site_name . ' - Exception Thrown';
  Globals::$page_obj->page_header = 'PHP Exception Thrown';
  Globals::$page_obj->required_authority = NULL;
?>
<h1>Exception Encountered</h1>
<?php
echo "<div class=\"dump-output\">\n";
echo Globals::$rc->safe_get_error_message;
echo "</div>\n";

if (Globals::$site_installation == 'development') {
  echo Globals::dump('Global Variable Dump');
}
?>
