<?php
/*
#doc-start
h1. category_ajax.php - a Starter file for YASiteKit AJAX scripts

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 

This is a template for creating AJAX code to run using the YASiteKit protocol.

Instructions:

# read about "render_ajax.php":/doc.d/system-includes/render_ajax.html
# copy this script to something like "my_ajax.php" in the _private_data / ajax_scripts_ directory.
# hack away

#doc-end
*/

function ajax_set_required_authority()
{
  // insert any logic you need to check authority - for example, if
  //  Globals::$account_obj->userid is the owner of whatever you are accessing,
  // then you might want to restrict access to that owner - by setting
  // the owner's authority as required.
  // On the other hand, if the user has limited authority: say A,M,W,or C
  //  and the resource is owned by someone else, you can set required_authority to FORBIDDEN

  // Globals::$web_service->required_authority = 'FORBIDDEN';
  // return 403;

  // Globals::$web_service->required_authority = 'ANY';
  // Globals::$web_service->required_authority = 'X';
  // Globals::$web_service->required_authority = 'S';
  // Globals::$web_service->required_authority = 'A';
  // Globals::$web_service->required_authority = 'M';
  // Globals::$web_service->required_authority = 'W';
  // Globals::$web_service->required_authority = 'C';
  // Globals::$web_service->required_authority = 'C,A,M,W,S,X';
  switch (Globals::$web_service->data_format) {
    case 'json':
      Globals::$web_service->required_authority = 'S,X';
      return '200';
    default:
      return '400';
  }
} // end of ajax_set_required_authority()

function move_category()
{
  require_once('Category.php');
  $category_path = preg_replace('/^button_/', '', Globals::$rc->safe_post_button_name);
  $obj = new Category(Globals::$dbaccess, $category_path);
  $parent = $obj->parent;
  $parameters_parent_name = $parent ? $parent : '_root_';
  $name = $obj->name;
  switch (Globals::$rc->safe_post_direction) {
    case 'up':
      $obj->sibling_ordinal -= 1;
      $inc = 1;
      break;
    case 'down':
      $obj->sibling_ordinal += 1;
      $inc = -1;
      break;
    default:
      Globals::$web_service->add_error('Illegal direction: ' . Globals::$rc->safe_post_direction);
      return FALSE;
  }
  if ($obj->sibling_ordinal < 1 || $obj->sibling_ordinal > Category::$parameters->$parameters_parent_name) {
    Globals::$web_service->add_error("Illegal Move - out of range: {$obj}: {$obj->sibling_ordinal}");
    return FALSE;
  }

  // find and adjust other
  $tmp = $obj->get_objects_where(array('parent' => $parent,
      'sibling_ordinal' => $obj->sibling_ordinal));
  $other = $tmp[0];
  $other->sibling_ordinal += $inc;
  
  // $obj->save();
  // $other->save();
  $obj_save = $obj->save() ? 'obj saved' : 'obj not saved';
  $other_save = $other->save() ? 'other saved' : 'other not saved';
  Globals::$web_service->add_content(array('direction' => Globals::$rc->safe_post_direction,
    'path' => $category_path, 'parent' => $parent, 'name' => $name,
    'inc' => $inc, 'sibling_ordinal' => $obj->sibling_ordinal,
    'obj_save' => $obj_save, 'other_save' => $other_save,
    'obj' => $obj->dump('obj'), 'other' => $other->dump('other'),
    ));
  return TRUE;
}

function ajax_content()
{
  // here we assume we have authority

  // check for bad things and call failure() if they've happened
  $something_bad = FALSE;
  if ($something_bad) {
    Globals::$web_service->add_error('something bad happened');
    return FALSE;
  }

  // everything is good, so return something good
  switch (Globals::$web_service->data_format) {
    case 'json':
      switch (Globals::$rc->safe_post_command) {
        case 'move':
          return move_category();
        default:
          Globals::$web_service->add_error("Illegal Command: " . Globals::$rc->safe_post_command);
          return FALSE;
      }
      // Construct an array containing your return data
      break;
    default:
      Globals::$web_service->add_error("Illegal AJAX data format requested: '" . Globals::$web_service->data_format . "'");
      return FALSE;
  }
  
  return TRUE;
} // end of ajax_content()
?>
