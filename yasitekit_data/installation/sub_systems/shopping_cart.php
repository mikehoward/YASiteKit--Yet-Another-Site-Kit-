<?php
echo "shopping cart is disabled\n";
return;

require_once('ShoppingCart.php');
require_once('ShoppingCartItem.php');
require_once('Address.php');

foreach (array('ShoppingCart', 'ShoppingCartItem', 'Address') as $obj_name) {
  $obj = new $obj_name(Globals::$dbaccess);
  if (!Globals::$dbaccess->table_exists($obj->tablename)) {
    $tmp = AClass::get_class_instance($obj_name);
    $tmp->create_table(Globals::$dbaccess);
  }
}

// do your initialization
$item_data = array(
  array(
    ),
  );
$address_data = array(
  array(
    'addressee' => 'Joe Smedly',
    'address1' => '1234 Top Ln',
    'address2' => 'Unit 12',
    'city' => 'Seatle',
    'state_province' => 'WA',
    'postal' => '12345',
    // 'country_code' => 'US',
    // 'tax_authorities' => '',
    ),
  array(
    'addressee' => 'Fred Flintstone',
    'address1' => 'Bedrock Road',
    'address2' => 'Cave # 12',
    'city' => 'Bed Rock',
    'state_province' => 'Palialit',
    'postal' => 'Z3brA',
    // 'country_code' => '',
    // 'tax_authorities' => '',
    ),
  );
$cart_data = array(
  array('email' => 'joe@foo.bar',),
  array('email' => 'fred@foo.bar',),
  );

foreach (array(array('Address', $address_data),
      array('ShoppingCart', $cart_data),
      array('ShoppingCartItem', $item_data)) as $row) {
  list($object_name, $object_data) = $row;
  echo "Initializing $object_name Data\n";
  $ar = array();

  foreach ($object_data as $tmp) {
    $obj = new $object_name(Globals::$dbaccess);
    foreach ($tmp as $key => $val) {
      if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
        continue; // skip
      }
      echo "setting $key to $val\n";
      $obj->$key = $val;
    }
    $obj->save();
    $ar[] = $obj;
    // $obj_ar[$tmp['key-name']] = $obj;
  }
  $ar_name = strtolower($object_name);
  $$ar_name = $ar;
}

for ($idx=0;$idx<2;$idx++) {
  $shoppingcart[$idx]->address_id = $address[$idx]->address_id;
  $shoppingcart[$idx]->save();
}
echo $shoppingcart[0]->dump('after assigning address_id');
