<?php if (Globals::$account_obj instanceof Account): ?>
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
    <li><span class="bigger bold">{: Globals::$site_name :} Data -</span></li>
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
    <li><span class="bigger bold">{:Globals::$site_name:} System -</span></li>
<?php
    foreach (array_unique(array_merge(scandir(Globals::$pages_root), scandir(Globals::$system_pages))) as $fname):
      if (preg_match('/^Manage(.*)\.php$/', $fname, $m)):
?>
      <li><a href="{:$fname:}">Manage {:$m[1]:}</a></li>
<?php endif; ?>
<?php endforeach;
      break;
 ?>
<?php endswitch; ?>
  </ul>
</div> <!-- account-nav -->
<?php endif; ?>