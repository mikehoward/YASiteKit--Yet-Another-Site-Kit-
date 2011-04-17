<?php
/*
#doc-start
h1. AdminTools.php - administration tools to run in Off-Line Mode

bq. Created one 2010-06-10
(c) Copyright 2010 Mike Howard. All Rights Reserved.

#doc-end
*/

// global variables
require_once('StateMgt.php');
Globals::$page_obj->page_title = Globals::$site_name . ' - AdminTools';
Globals::$page_obj->page_header = 'AdminTools';
Globals::$page_obj->required_authority = 'X';
// end global variables

// function definitions

function set_color()
{
  static $background = '#80ffff';
  if ($background == '#80ffff') {
    $background = '#c0ffa0';
    $color = '#3fff1f';
  } else {
    $background = '#80ffff';
    $color = '#9fff1f';
  }
  return "style=\"background:$background;color:black; text-decoration:underline;\"";
}

function menu()
{
  // FIXME: this is probably not a good idea - 
  Globals::clear_messages();
  switch (Globals::$dbaccess->on_line) {
    case 'F': $current_site_state = '<span class="advisory">Off Line</span>'; break;
    case 'R': $current_site_state = '<span class="warning">Read Only</span>'; break;
    case 'T': $current_site_state = '<span class="ok">On Line</span>'; break;
  }
?>
<div class="box width-80">
  <h2>Site is <?php echo $current_site_state; ?></h2>
  <h2>Pick an Action:</h2>
  <?php if (Globals::$dbaccess->database_valid == 'T' && Globals::$dbaccess->model_mismatch == 'F'
        && Globals::$dbaccess->on_line != 'T'): ?>
      <p>System Ready to Go Online:
        <a href="AdminTools.php?admin_cmd=go_online" style="background:#c0ffa0;text-decoration:underline;">
          <span class="ok">Click for Online Mode</span>
        </a>
      </p>
  <?php elseif (Globals::$dbaccess->on_line != 'F'): ?>
      <p>System is <span class="bold">NOT</span> Offline:
        <a href="AdminTools.php?admin_cmd=go_offline" style="background:#c0ffa0;text-decoration:underline;">
          <span class="advisory">Click to Go Offline</span>
        </a>
      </p>
  <?php endif; ?>
  <ol>
    <li><a <?php echo set_color(); ?> href="AdminTools.php?admin_cmd=reload_db">Create Archive and/or Reload Database</a></li>
    <li><a <?php echo set_color(); ?> href="AdminTools.php?admin_cmd=rebuild_aclass_hashes_1">Rebuild AClass Hashes Table</a></li>
    <li><a <?php echo set_color(); ?> href="AdminTools.php?admin_cmd=display_site_state">Display Site Specific PHP Variables</a></li>
    <li><a <?php echo set_color(); ?> href="AdminTools.php?admin_cmd=phpinfo">Display PHP Info</a></li>
  </ol>
</div>
<?php

  echo dbaccess_state();
} // end of menu()

function dbaccess_state()
{
  $str = "<div class=\"box width-80\">\n";
  $str .= "<p>Database State Variables:</p>\n<ul>\n";
  $str .= "  <li>Current Value is in <span class=\"bold\"
      style=\"background:#80ff00;padding:3px;\">Bold</span></li>\n";
      // style=\"background:#0080c0;padding:3px;\">Bold</span></li>\n";
  // $str .= "  <li>Legal Transition is a <span style=\"padding:1px;background:#80ff00;\"> "
  $str .= "  <li>Legal Transition is a <span style=\"padding:1px;background:#0080c0;\"> "
    . "<span style=\"background:white; padding:1px 0 0 1px; border-right:2px #888 solid; border-bottom:2px #888 solid; border-top:1px #0080c0 solid; border-left:1px #0080c0 solid;\">"
    . "<span style=\"background:#0080c0;\">Button</span></span></span> - Click to Change to That State</li>\n";
  $str .= "  <li>Illegal Transition has a <span class=\"line-through\" style=\"background:#ffc0c0\">Line Through</span> - Click if you're bored - it won't hurt.</li>\n";
  $str .= "  <li>&nbsp;</li>";
  $str .= "  <li><span class=\"bold\">NOTE:</span> Legal changes May Not Actually work if an Automatic change cancels them.</li>\n";
  $str .= "</ul>\n";
  $str .= "<table frame=\"box\" style=\"margin-left:0.5em;\">\n";
  $background_color = '#aaa';
  foreach (StateMgt::$legal_state_names as $state_var) {
    $background_color = $background_color == '#eee' ? '#aaa' : '#eee';
    $str .= "  <tr style=\"text-align:left;background-color:$background_color\"><th>{$state_var}:</th><td>";
    // FIXME: Have to Completely Redesign and Rewrite Manual State Changes
    foreach (StateMgt::$legal_state_values[$state_var] as $val) {
      if (Globals::$dbaccess->$state_var == $val) {
        $str .= "<td class=\"bold\" style=\"background:#80ff00; text-align:center;\">$val</td>";
      } elseif (StateMgt::legal_state_changeP($state_var, $val, Globals::$dbaccess))  {
        $str .= "<td style=\"background:#0080c0\"><form action=\"AdminTools.php\" method=\"get\" accept-charset=\"utf-8\">"
          . "<input type=\"hidden\" name=\"admin_cmd\" value=\"change_state\">"
          . "<input type=\"hidden\" name=\"state_var_name\" value=\"$state_var\">"
          . "<input style=\"background:#0080c0;\" type=\"submit\" name=\"state_var_value\" value=\"$val\">"
          . "</form></td>";
      } else {
        $str .= "<td style=\"background:#ffc0c0; text-align:center;\"><span class=\"line-through\">$val</span></td>";
      }
    }
    $str .= "</tr>\n";
  }
  $str .= "</table>\n";
  $str .= "</form>\n";
  return $str . "</div>\n";
} // end of dbaccess_state()

function site_state()
{
  echo Globals::dump('Current Global Variables');
  echo "<div class=\"dump-output\">\n";

  ob_start();
  echo "\n_SERVER:\n";
  var_dump($_SERVER);
  echo "\n_GET:\n";
  var_dump($_GET);
  echo "\n_POST:\n";
  var_dump($_POST);
  echo "\n_COOKIE:\n";
  var_dump($_COOKIE);
  echo "\n_FILES:\n";
  var_dump($_FILES);
  echo "\n_ENV:\n";
  var_dump($_ENV);
  echo htmlentities(ob_get_clean());
  
  echo "</div>\n";
} // end of site_state()

function rebuild_aclass_hashes()
{
  if (Globals::$dbaccess->on_line != 'F') {
    return FALSE;
  }
  // change_site_state('model_mismatch', 'F');
  // attempt to change state here - this will throw an exception if it can't be done
  StateMgt::handle_event('REFRESH_ACLASS_HASHES');

  try {
    AClass::create_aclass_hashes_table(Globals::$dbaccess, TRUE);
    $object_file_list = array_merge(scandir(Globals::$system_objects), scandir(Globals::$objects_root));
    $object_file_list = array_unique($object_file_list);
    foreach ($object_file_list as $fname) {
      if (!preg_match('/\.php$/', $fname)) {
        continue;
      }
      require_once($fname);
      $class_name = substr($fname, 0, strlen($fname) - 4);
      $ref_obj = new ReflectionClass($class_name);
      if ($ref_obj->isSubclassOf('AnInstance')) {
        $class_instance = AClass::get_class_instance($class_name);
        Globals::$dbaccess->insert_into_table(AnInstance::ACLASS_HASHES_TABLENAME,
            $class_instance->aclass_instance_hashes_array());
      }
    }
  } catch (Exception $e) {
    Globals::add_message("Rebuild Hashes Failed: $e\n");
    StateMgt::rollback();
  }
} // end of rebuild_aclass_hashes()

function confirm_rebuild_aclass_hashes()
{
  if (Globals::$dbaccess->on_line != 'F') {
    Globals::add_message('Cannot Rebuild AClass Hashes - Site is Not Offline');
  } else {
?>
  <form action="AdminTools.php" method="get" accept-charset="utf-8">
    <input type="hidden" name="admin_cmd" value="rebuild_aclass_hashes_2">
    <p>Are you Sure? <input type="submit" value="Yes"></p>
  </form>
<?php
  }
} // end of confirm_rebuild_aclass_hashes()

// end of function definitions

echo "<h1>Administrative Tools</h1>\n";

// dispatch actions
switch (Globals::$rc->safe_get_admin_cmd) {
  case 'change_state':
    $state_var = Globals::$rc->safe_get_state_var_name;
    echo "changing $state_var from " . Globals::$dbaccess->$state_var . " to "
          . Globals::$rc->safe_get_state_var_value . "\n";
    try {
      StateMgt::change_state_value(Globals::$rc->safe_get_state_var_name,
          Globals::$rc->safe_get_state_var_value);
    } catch (Exception $e) {
      Globals::add_message("$e");
    }
    menu();
    break;
  case 'go_online':
    // change_site_state('on_line', 'T');
    StateMgt::handle_event('GO_ONLINE');
    menu();
    break;
  case 'go_offline':
    try {
      StateMgt::handle_event('GO_OFFLINE');
    } catch (Exception $e) {
      Globals::add_message("Exception thrown when going offline - forcing offline\n$e");
      StateMgt::change_state_value('on_line', 'F');
    }
    Globals::add_message('Warning: This Site is Off-Line');
    menu();
    break;
  case 'reload_db':
    IncludeUtilities::redirect_to('ReloadDB.php', basename(__FILE__) . ':' . __LINE__);
    break;
  case 'display_site_state':
    menu();
    site_state();
    break;
  case 'rebuild_aclass_hashes_1':
    confirm_rebuild_aclass_hashes();
    menu();
    break;
  case 'rebuild_aclass_hashes_2':
    rebuild_aclass_hashes();
    menu();
    break;
  case 'phpinfo':
    menu();
    phpinfo();
    break;
  default:
    menu();
    break;
}

?>