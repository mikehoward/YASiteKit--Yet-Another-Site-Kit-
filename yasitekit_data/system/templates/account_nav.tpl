<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->logged_in()): ?>
<div id="account-nav" class="box click-display" >
<span class="bold title center"><?php echo Globals::$account_obj->name; ?> Menu <span class="smaller">(click or mouse over)</span></span>
<ul id="account-nav-ul" class="display-target">
<?php switch (Globals::$account_obj->authority):
  case 'C': ?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.tpl">Account Information</a></li>
    <li><a href="/ManageSubscription.tpl">Newsletter Subscription</a></li>
<?php
    break;
    case 'A':
    case 'M':
    case 'W':
?>
    <li><span class="larger bold">Manage My -</span></li>
    <li><a href="/ManageAccount.tpl">Account Information</a></li>
    <li><a href="/ManageSubscription.tpl">Newsletter Subscriptions</a></li>
    <li><a href="/ManageProduct.tpl">Products</a></li>
<?php
    break;
    case 'S':
?>
    <li><span class="bigger bold">{: Globals::$site_name :} Data -</span></li>
    <li><a href="/ManageAccount.tpl">Manage Accounts</a></li>
    <li><a href="/ManageSubscription.tpl">Communication Subscriptions</a></li>
    <li><a href="/ManageProduct.tpl">Manage Products</a></li>
    <li><a href="/ManageNewsletter.tpl">Manage Newletter</a></li>
    <li><a href="/ManageProductOrder.tpl">Order Management</a></li>
    <li><a href="/ManageRMA.tpl">RMA Management</a></li>
    <li><a href="/ManageArticle.tpl">Manage Articles</a></li>
    <li><a href="/ManageArticleGroup.tpl">Manage Article Groups</a></li>
<?php
    break;
    case 'X':
?>
    <li><span class="bigger bold">{:Globals::$site_name:} System -</span></li>
<?php
    foreach (array_unique(array_merge(scandir(Globals::$pages_root), scandir(Globals::$system_pages))) as $fname):
      if (preg_match('/^Manage(.*)\.tpl$/', $fname, $m)):
?>
      <li><a href="/{:$fname:}">Manage {:$m[1]:}</a></li>
<?php endif; ?>
<?php endforeach; ?>
      <li><a href="/AdminTools.tpl">Admin Tools</a></li>
<?php  break; ?>
<?php endswitch; ?>
  </ul>
</div> <!-- account-nav -->
<?php endif; ?>
