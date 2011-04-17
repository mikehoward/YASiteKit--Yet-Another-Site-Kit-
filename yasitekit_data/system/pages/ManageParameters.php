<?php
/*
#doc-start
h1. ManageParameters.php - Management for Persistent parameter store for Classes

bq.(c) Copyright 2010 Mike Howard. All Rights Reserved. 

ManageParameters.php is the management interface to the Parameters object and persistent
store.

The "Parameters":/doc.d/system-objects/Parameters.html object provides a persistent, centralized store
for Object parameters. 

Parameter names use the same rules as object attribute names: [a-z][_a-z0-9]*

At present, the following parameters are defined, but the facility is general and flexible.

* required_authority - all persistent objects - defines the authority required for
data access via web services. See "render_web_service.php":/doc.d/system-includes/render_web_service.html
for details.
* max_days - DownloadAuthorization - sets the maximum number of days a download authorization
is valid for.
* max_uses - DownloadAuthorization - sets the maximum number of times a download authorization
may be used.

#doc-end
*/

// Global Variables
require_once('Parameters.php');
Globals::$page_obj->page_title = Globals::$site_name . " - Parameter Management";
Globals::$page_obj->page_header = Globals::$site_name . " - Parameter Management";
Globals::$page_obj->required_authority = 'X';
// end Global Variables

// Class Definitions

// end Class Definitions

// function Defintions
function display_form()
{
  $color = 
    $light_color = "#eeeeee";
  $dark_color = "#cccccc";
  $shade = $light_shade = "#dddddd";
  $dark_shade = "#bbbbbb";
  $str = "<form action=\"ManageParameters.php\" method=\"post\" accept-charset=\"utf-8\">\n";

  // scan objects directory for classes
  $fname_ar = array();
  foreach (array(Globals::$objects_root, Globals::$system_objects) as $ar) {
    foreach (scandir($ar) as $fname) {
      if ($fname[0] == '.' || substr($fname, strlen($fname) - 4) != '.php' || in_array($fname, $fname_ar)) {
        continue;
      }
      $fname_ar[] = $fname;
      $class_name = basename($fname, '.php');
      if (!class_exists($class_name)) require_once($fname);
      
      if (!AClass::existsP($class_name)) {
        continue;
      }

      // get parameters for current object
      $params_obj = new Parameters(Globals::$dbaccess, $class_name);
      $color = $color == $light_color ? $dark_color : $light_color;
      $str .= "<li class=\"lower-line clear\" style=\"background-color:$color;\">{$class_name}:\n <ul style=\"margin:0\">\n";
      $keys = $params_obj->attributes();
      $shade = $dark_shade;
      foreach ($keys as $key) {
        $shade = $shade == $light_shade ? $dark_shade : $light_shade;
        $str .= "  <input type=\"hidden\" name=\"field_names[]\" value=\"{$class_name}_{$key}\" id=\"field_names[]\">\n";
        $str .= "  <li class=\"clear\" style=\"background-color:$shade\"><label class=\"float-left width-25\" for=\"{$class_name}_{$key}\">$key </label>"
          . "<input class=\"width-25\" type=\"text\" name=\"{$class_name}_{$key}\" value=\"{$params_obj->$key}\" id=\"{$class_name}_{$key}\">";
        $str .= " Delete <input type=\"radio\" name=\"{$class_name}_{$key}_DELETE\" value=\"Y\" id=\"{$class_name}_{$key}_DELETE\"></li>\n";
      }
      $str .= "  <input type=\"hidden\" name=\"field_names[]\" value=\"{$class_name}___new_\" id=\"field_names[]\">\n";
      $str .= "  <input type=\"hidden\" name=\"field_names[]\" value=\"{$class_name}___value_\" id=\"field_names[]\">\n";
      $shade = $shade == $light_shade ? $dark_shade : $light_shade;
      $str .= "  <li style=\"background-color:$shade\"><span class=\"clear float-left width-20\">New Param</span>"
        . "<label class=\"float-left width-5\" for=\"{$class_name}___new_\">Name:</label>"
        . "<input class=\"float-left width-25\" type=\"text\" name=\"{$class_name}___new_\" value=\"\" id=\"{$class_name}___new_\">"
        . "  <label class=\"float-left width-1-\"> Value </label>"
        . "<input class=\"width-25\" type=\"text\" name=\"{$class_name}___value_\" value=\"\" id=\"{$class_name}___value_\">"
        . "</li>\n";
      $str .= " </ul>\n</li> <!-- $class_name -->\n";
    }
  }
  $str .= " <input type=\"submit\" name=\"submit\" value=\"Commit\" id=\"submit\">\n";
  $str .= " <input type=\"reset\" name=\"reset\" value=\"Reset\" id=\"reset\">\n";
  $str .= "</form>\n";
  
  return $str;
} // end of display_form()

function process_form()
{
  $field_names = Globals::$rc->safe_post_field_names;
  $class_to_fields = array();
  foreach ($field_names as $field_name) {
    if (preg_match('/^([A-Z][a-z0-9]*)_(\w+)/i', $field_name, $match_obj) != 1) {
      echo "Illegal Field Name: $field_name\n";
      continue;
    }
    $class_name = $match_obj[1];
    if (!isset($class_to_fields[$class_name])) $class_to_fields[$class_name] = array();
    $class_to_fields[$class_name][] = $field_name;
  }
  
  foreach ($class_to_fields as $class_name => $field_names) {
    $params_obj = new Parameters(Globals::$dbaccess, $class_name);
    $regx = "/^{$class_name}_(\\w+)$/";
    foreach ($field_names as $field_name) {
      $form_name = "safe_post_$field_name";
      preg_match($regx, $field_name, $match_obj);
      $real_field_name = $match_obj[1];

      switch ($real_field_name) {
        case '__new_':
          if (Globals::$rc->$form_name) {
            $param_name = Globals::$rc->$form_name;
            $value_form_name = "safe_post_{$class_name}___value_";
            $value = Globals::$rc->$value_form_name;
            // echo "Setting params->{$param_name} to '$value'\n";
            $params_obj->$param_name = $value;
          }
          break;
        case '__value_':
          break;
        default:
          $delete_form_name = "${form_name}_DELETE";
          if (isset(Globals::$rc->$delete_form_name)) {
            unset($params_obj->$real_field_name);
          } elseif ($params_obj->$real_field_name != Globals::$rc->$form_name) {
            $params_obj->$real_field_name = Globals::$rc->$form_name;
          }
          break;
      }
    }
  }
} // end of process_form()

// end function Defintions

// dispatch actions
// echo Globals::$rc->dump();

switch (Globals::$rc->safe_post_submit) {
  case 'Commit':
    process_form();
    echo display_form();
    break;
  default:
    echo display_form();
    break;
}
?>