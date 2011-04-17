<!-- (c) Copyright 2010 Mike Howard. All Rights Reserved.  -->
<div id="header">
  <a id="logo" class="float-left box"  href="/index.php" title="Home Page">
    <img src="/img/YASiteKitLogo.png" height="50px" alt="YASiteKit Logo">
  </a>
<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->key_values_complete()): ?>
  <p class="smaller float-right width-20" style="position:absolute;right:0;top:0;">Hi <?php echo Globals::$account_obj->name ?>
     (if it's not you - click <a class="larger underline" href="/Login.php">here</a>)</p>
<?php endif;?>
  <h1 class="center">{page_header}</h1>
</div>
