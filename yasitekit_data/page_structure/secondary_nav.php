<?php
/*
#doc-start
h1.  secondary_menu.php - Secondary Menu Navigation

Created by  on 2010-03-25.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. Secondary Menu Description


#end-doc
*/

// global variables
// end global variables

// class definitions
// end class definitions

// function definitions
// end function defintions

// dispatch actions
?>

<div id="secondary-nav" class="hover-reveal click-display">
  <span class="smaller bold click-message" style="margin-top:-1.5em;position:absolute;">(mouse over to view)</span>
  <p class="larger bold">Neat Stuff Menu</p>
  <ul>
    <li id="secondary-nav-doc" class="level-1 box">
      <span>
        <a href="/doc.d/index.php" title="YASiteKit Doc">YASiteKit Doc</a>
      </span>
    </li>
    <li id="secondary-nav-download" class="level-1 box">
      Download YASiteKit Code
      <ul>
        <li><a href="/downloads/site-framework-with-system.tar.gz">Complete Generic Site Stubs</a></li>
        <li><a href="/downloads/site-framework-no-system.tar.gz">Generic Site Stubs without System</a></li>
        <li><a href="/downloads/yasitekit-system-latest.tar.gz">YASiteKit System Code 1.0.4 Alpha - to Update Site System</a></li>
        <li><a href="/downloads/yasitekit-doc.d.tar.gz">YASiteKit Documentation - gzip'ed tar file</a></li>
        <li><a href="/downloads/msh-utilities-1.0.0.tar.gz">Some useful System Independent Utilities - requires Python 2.6 - tar.gz format</a></li>
        <li><a href="/downloads/msh-utilities-1.0.0.zip">Some useful System Independent Utilities - requires Python 2.6 - zip format</a></li>
      </ul>
    </li>
    <li id="secondary-nav-videos" class="box">Tutorial Videos
      <ul>   <!-- Videos -->
        <li>
          Creating a New Site using YASiteKit
          <ul>  <!-- Setting Up New Site -->
            <li><a href="http://www.youtube.com/watch?v=UmLE7QHCF2I">Starting: Creating the Local Development Site</a></li>
          </ul>  <!-- End Setting Up New Site -->
        </li>
        <li>
          Setting Up Apache & MySQL
          <ul>   <!-- Apache and Mysql -->
            <li><a href="http://www.youtube.com/watch?v=0W8AvuZDueQ">Installing XCode & MacPorts</a></li>
            <li><a href="http://www.youtube.com/watch?v=QPCPe54v1aM">Setting Up & Configuring Apache on OS X</a></li>
            <li><a href="http://www.youtube.com/watch?v=QrkQNvMP6R0">Setting Up MySQL5 on OS X</a></li>
          </ul>   <!-- Apache and Mysql -->
        </li>
      </ul>   <!-- End Videos -->
    </li>
  </ul>
</div>