<?php
require_once('Object.php');
require_once('Object2.php');

foreach (array('Object', 'Object2') as $obj_name) {
  $obj = new $obj_name(Globals::$dbaccess);
  if (!Globals::$dbaccess->table_exists($obj->tablename)) {
    $tmp = AClass::get_class_instance($obj_name);
    $tmp->create_table(Globals::$dbaccess);
  }
}

// do your initializations
$sample_object_data = array(
  array(
    ),
  );

// foreach (array(array('ShoppingCart', $cart_data), array('ShoppingCartItem', $item_data)) as $row) {
foreach (array(array('SampleObjectName', $sample_object_data)) as $row) {
  list($object_name, $object_data) = $row;
  
  foreach ($object_data as $tmp) {
    $obj = new $object_name(Globals::$dbaccess, $tmp);
    foreach ($tmp as $key => $val) {
      if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
        continue; // skip
      }
      $obj->$key = $val;
    }
    $obj->save();
    // $obj_ar[$tmp['key-name']] = $obj;
  }
}
