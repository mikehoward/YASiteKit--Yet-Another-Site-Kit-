<!-- (c) Copyright 2010 Mike Howard. All Rights Reserved.  -->
<div id="footer" class="clear"> <!-- footer -->
  <a href="http://www.yasitekit.org">
    <img class="float-right" src="/img/PoweredBy.png" alt="Powered by YASiteKit">
  </a>
  <ul>
    <li><a href="/article/privacy" title="Privacy Policy">Privacy Policy</a></li>
    <li>|</li>
    <li><a href="/article/terms" title="Terms and Conditions">Terms and Conditions</a></li>
    <li>|</li>
    <li><a href="/Contact.tpl" title="Contact Us">Contact Us</a></li>
<?php if (Globals::$session_obj instanceof Session): ?>
    <li>|</li>
<?php if (Globals::$account_obj instanceof Account && Globals::$account_obj->logged_in()): ?>
    <li><a href="/Login.tpl?logout=Y" title="Logout">Logout</a></li>
<?php else: ?>
    <li><a href="/Login.tpl" title="Login">Login</a></li>
<?php endif; // login/logout buttons ?>
<?php endif; // require session object ?>
  </ul>
</div>
<?php if (Globals::$site_installation == 'development'): ?>
<div class="dump-output">
  <?php echo Globals::dump(); ?>
  <?php var_dump($_SERVER); ?>
</div>
<?php endif; ?>