<?php
/*
#doc-start
h1.  DisplayGlobals.php - used to display Global Variables

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a diagnosstic page which is restricted to Administrator Access. It displays
sensitive information, so should never be used on a production site.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " -Global Variable Display";
Globals::$page_obj->page_title = "Global Variables";
Globals::$page_obj->required_authority = "X";

?>
<body>
  <div>&nbsp;</div>
  <div id="image" class="clear content"> <!-- image display -->
  <p>In DisplayGlobals.php</p>
  </div>
