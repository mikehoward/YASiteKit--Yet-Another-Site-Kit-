<?php
/*
#doc-start
h1. CreateAccount.php - Used to Self-Register Users
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#doc-end
*/
?>
<form id="register" action="CreateAccount.php" method="post" accept-charset="utf-8">
  <p class="larger">Just Fill in the Next Three Spaces to Create your account. The other stuff
    is optional.</p>
  <p class="larger">Click <input type="submit" name="submit" value="Create Account"> when you're
    ready.</p>
      <fieldset>
        <input class="float-right" type="text" name="userid" value="<?php echo Globals::$rc->safe_post_userid; ?>" id="userid" maxlength="255" size="40">
        <label for="userid">Enter a Userid - just letters, numbers and underscores (the '_')</label>
      </fieldset>

      <fieldset>
        <input class="float-right" type="password" name="password" value="" id="password" maxlength="255" size="40">
        <label for="password">Your Secret Password</label>
      </fieldset>

      <fieldset>
        <input class="float-right" type="password" name="password_check" value="" id="password_check" maxlength="255" size="40">
        <label for="password_check">Repeat your Password</label>
      </fieldset>
      
      <p class="larger">Don't expect more than one Newsletter a month - more likely every
        other one.
        </p>
 
     <fieldset>
        <input class="float-right" type="text" name="email" value="<?php echo Globals::$rc->safe_post_email; ?>" id="email" maxlength="255" size="40">
        <label for="email">Subscribe by typing your Email Address</label>
      </fieldset>
      
      <fieldset>
        <span class="float-right">
          <span style="border-right:white solid 2px;padding-right:.3em">
            <input type="radio" name="include_pictures" value="Y" 
            <?php echo !Globals::$rc->safe_post_include_pictures || Globals::$rc->safe_post_include_pictures == 'Y' ? 'checked':''; ?>>
            Yes - include them
          </span>
          <input type="radio" name="include_pictures" value="N"
            <?php echo !Globals::$rc->safe_post_include_pictures && Globals::$rc->safe_post_include_pictures == 'N' ? 'checked':''; ?>>
            No - just the news and links
          </span>
          <label for="include_pictures">Include Pictures in the Newsletter?</label>
      </fieldset>
      
      <p class="larger">Thanks for wanting to get to know us better.</p>
      <fieldset>
        <textarea class="float-right rte" name="aboutness" rows="10" cols="60">
          <?php echo Globals::$rc->safe_post_aboutness ?>
        </textarea>
        <label for="aboutness">We'd like to know you
          a little better too, so if you have time, please write a little about yourself.</label>
      </fieldset>

  <p>Click <input type="submit" name="submit" value="Create Account"> when your ready.</p>
  <br>
</form>
