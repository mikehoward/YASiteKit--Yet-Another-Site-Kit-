<?php
// (c) Copyright 2010 Mike Howard. All Rights Reserved. 

// echo Globals::dump('from account_nav.php');
if (!(Globals::$account_obj instanceof Account) || !Globals::$account_obj->logged_in() || !in_array(Globals::$account_obj->authority, array('C', 'A', 'S', 'X'))) {
//  var_dump(Globals::$account_obj);
//  if (Globals::$account_obj instanceof Account) echo Globals::$account_obj->dump('account_nav.php') . "'\n";
  return;
}

?>
<div id="account-nav" class="box click-display" >
<span class="bold title center"><?php echo Globals::$account_obj->name; ?> Menu <span class="smaller">(click or mouse over)</span></span>
<ul id="account-nav-ul" class="display-target">
<?php switch (Globals::$account_obj->authority):
  case 'C': ?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.php">Account Information</a></li>
    <li><a href="/ManageSubscription.php">Newsletter Subscription</a></li>
<?php
    break;
    case 'A':
    case 'M':
    case 'W':
?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.php">Account Information</a></li>
    <li><a href="/ManageSubscription.php">Newsletter Subscriptions</a></li>
    <li><a href="/ManageProduct.php">Products</a></li>
<?php
    break;
    case 'S':
?>
    <li><span class="bigger bold"><?php echo Globals::$site_name; ?> Data -</span></li>
    <li><a href="/ManageAccount.php">Manage Accounts</a></li>
    <li><a href="/ManageSubscription.php">Communication Subscriptions</a></li>
    <li><a href="/ManageProduct.php">Manage Products</a></li>
    <li><a href="/ManageNewsletter.php">Manage Newletter</a></li>
    <li><a href="/ManageProductOrder.php">Order Management</a></li>
    <li><a href="/ManageRMA.php">RMA Management</a></li>
    <li><a href="/ManageArticle.php">Manage Articles</a></li>
    <li><a href="/ManageArticleGroup.php">Manage Article Groups</a></li>
<?php
    break;
    case 'X':
?>
    <li><span class="bigger bold"><?php echo Globals::$site_name; ?> System -</span></li>
<?php
    $link_ar = array();
    foreach (scandir(Globals::$pages_root) as $fname) {
      if (preg_match('/^Manage(.*)\.php$/', $fname, $m)) {
        echo "<li><a href=\"/$fname\">Manage {$m[1]}</a></li>\n";
        $link_ar[] = $fname;
      }
    }
    if ($link_ar) {
      echo "<hr>\n";
    }
    foreach (scandir(Globals::$system_pages) as $fname) {
      if (preg_match('/^Manage(.*)\.php$/', $fname, $m) && !in_array($fname, $link_ar)) {
        echo "<li><a href=\"/$fname\">Manage {$m[1]}</a></li>\n";
        $link_ar[] = $fname;
      }
    }
?>
    <!-- <li><a href="/ManageAccount.php">Manage Accounts</a></li>
    <li><a href="/ManageSubscription.php">Communication Subscriptions</a></li>
    <li><a href="/ManageProduct.php">Manage Products</a></li>
    <li><a href="/ManageNewsletter.php">Manage Newletter</a></li>
    <li><a href="/ManageProductOrder.php">Order Management</a></li>
    <li><a href="/ManageRMA.php">RMA Management</a></li>
    <li><a href="/ManageArticle.php">Manage Articles</a></li>
    <li><a href="/ManageArticleGroup.php">Manage Article Groups</a></li>
    <li><a href="/ManageParameters.php">Manage Object Parameters</a></li> -->
    <li><a href="/AdminTools.php">Admin Tools</a></li>
    <!-- <li><a href="/ReloadDB.php">Dump and Reload Database</a></li> -->
    <!-- <li><a href="/display_site_state.php">Display Site State</a></li> -->
<?php endswitch; ?>
  </ul>
</div> <!-- account-nav -->
