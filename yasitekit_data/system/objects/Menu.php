<?php
/*
#doc-start
h1. Menu.php - template to copy for YASiteKit AnInstance menus

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a Bare Bones AnInstance menu template.

To create a new menu, copy this and hack.

Remember to replace:

* Menu with your menu's name
* menu with your menu's lower case name

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('Menu', 'menu_name', 
  array( // field definitions
    array('menu_name', 'varchar(255)', 'Menu Name'),
    array('title', 'varchar(255)', 'Block Title (optional)'),
    array('menu_items', 'join(MenuItem.menu_item_id)', 'Menu Items'),
    array('item_ordering', 'pile', 'Menu Item Ordering'),
  ),
  array(// attribute definitions
    'menu_name' => array('filter' => '[a-z][_a-z0-9]*'),
    'item_ordering' => 'readonly',
      ));
// end global variables

// class definitions
class Menu extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Menu', $dbaccess, $attribute_values);
  } // end of __construct()
  
  public function form($form_action = NULL, $top_half = '', $bottom_half = '', $actions = NULL) {
    $ar = $this->pile_keys('item_ordering');
    sort($ar);
    $menu_items = $this->menu_items;
var_dump(array_keys($menu_items));
    foreach ($ar as $key => $menu_item_id) {
      
    }
    return parent::form($form_action, $top_part, $bottom_part, $submit_actions);
  } // end of form()
  
  public function process_form($rc) {
    return parent::process_form($rc);
  } // end of process_form()
  
  public function render($top = NULL, $bottom = NULL) {
    return parent::render($top, $bottom);
  } // end of render()
}


class MenuManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Menu', 'menu_name');
  } // end of __construct()
}
?>
