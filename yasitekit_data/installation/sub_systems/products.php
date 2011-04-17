<?php
echo "product initialization is disabled\n";
return;

require_once('Product.php');
require_once('Deliverable.php');
require_once('Category.php');

foreach (array('Product', 'Deliverable', 'Category') as $obj_name) {
  $obj = new $obj_name(Globals::$dbaccess);
  if (!Globals::$dbaccess->table_exists($obj->tablename)) {
    $tmp = AClass::get_class_instance($obj_name);
    $tmp->create_table(Globals::$dbaccess);
  }
}

$admin = new Account(Globals::$dbaccess, 'admin');
echo ($admin instanceof AnInstance ? 'admin is AnInstance' : 'admin is not an AnInstance') . "\n";

echo "Initializing Product Categories\n";
foreach (array(
  // array('product', 'Product'),
  array('product_image', 'Image'),
  array('product_potatoes', 'Potatoes'),
  array('deliverable_image_download', 'Downloadable Image'),
  array('deliverable_image_download_free', 'Free Downloadable Image'),
  array('deliverable_image_print', 'Image Print'),
  array('deliverable_image_framed', 'Image Print'),
  array('deliverable_food', 'Food'),
  ) as $row) {
    list($category, $title) = $row;
  $category_obj = new Category(Globals::$dbaccess, $category);
  // $category_obj->title = $title;
  $category_obj->save();
}

echo "Initializing Product\n";
for ($idx=1;$idx<45;$idx++) {
  $obj = new Product(Globals::$dbaccess, array('name' => "prod$idx", 'product_owner' => $admin,
    'product_category' => 'product_image', 'deliverable_category' => 'deliverable_image',
    'title' => "Product $idx", 'description' => "Product $idx Desc", 'available' => 'Y', 'sold_count' => $idx));
  $obj->save();
}
$obj->add_category('product_category', 'product_potatoes');
Category::add_to_category('product_potatoes', $obj);

echo "Initializing Deliverable\n";
$del_data = array(
    array( 'name' => 'img_1024x768', // 'varchar(40)', 'Deliverable Name'),
      'title' => 'Image 1024x768', // 'varchar(255)', 'Title'),
      'unit_price' => '.99', // 'float', 'Unit Price'),
      'deliverable_category' => 'deliverable_image_download',
      'downloadable' => 'Y', // 'enum(Y,N)', 'Downloadable'),
      'available' => 'Y', // 'enum(Y,N)', 'Available'),
      'lead_time' => '0', // 'int', 'Lead Time (days)'),
  
      // 'weight' => '', // 'float(2)', 'Weight'),
      // 'weight_units' => NULL, // 'enum(lb,kg)', 'Weight Units'),
      // 'length' => '', // 'float(2)', 'Length'),
      // 'width' => '', // 'float(2)', 'Width'),
      // 'height' => '', // 'float(2)', 'Height'),
      // 'length_units' => NULL, // 'enum(in,cm)', 'Length Units'),
    ),
    array( 'name' => 'img_800x600', // 'varchar(40)', 'Deliverable Name'),
      'title' => 'Image 800x600', // 'varchar(255)', 'Title'),
      'unit_price' => '.99', // 'float', 'Unit Price'),
      'deliverable_category' => 'deliverable_image_download',
      'downloadable' => 'Y', // 'enum(Y,N)', 'Downloadable'),
      'available' => 'Y', // 'enum(Y,N)', 'Available'),
      'lead_time' => '0', // 'int', 'Lead Time (days)'),

      // 'weight' => '', // 'float(2)', 'Weight'),
      // 'weight_units' => NULL, // 'enum(lb,kg)', 'Weight Units'),
      // 'length' => '', // 'float(2)', 'Length'),
      // 'width' => '', // 'float(2)', 'Width'),
      // 'height' => '', // 'float(2)', 'Height'),
      // 'length_units' => NULL, // 'enum(in,cm)', 'Length Units'),
    ),
    array( 'name' => 'print_85x11', // 'varchar(40)', 'Deliverable Name'),
      'title' => 'Print 8.5 by 11', // 'varchar(255)', 'Title'),
      'unit_price' => '15', // 'float', 'Unit Price'),
      'deliverable_category' => 'deliverable_image_framed,deliverable_image_print',
      'downloadable' => 'N', // 'enum(Y,N)', 'Downloadable'),
      'available' => 'Y', // 'enum(Y,N)', 'Available'),
      'lead_time' => '7', // 'int', 'Lead Time (days)'),

      'weight' => '.5', // 'float(2)', 'Weight'),
      'weight_units' => 'lb', // 'enum(lb,kg)', 'Weight Units'),
      'length' => '12', // 'float(2)', 'Length'),
      'width' => '9', // 'float(2)', 'Width'),
      'height' => '.25', // 'float(2)', 'Height'),
      'length_units' => 'in', // 'enum(in,cm)', 'Length Units'),
    ),
    array( 'name' => 'print_4x6', // 'varchar(40)', 'Deliverable Name'),
      'title' => 'Print 4 by 6', // 'varchar(255)', 'Title'),
      'unit_price' => '8.95', // 'float', 'Unit Price'),
      'deliverable_category' => 'deliverable_image_framed,deliverable_image_print',
      'downloadable' => 'N', // 'enum(Y,N)', 'Downloadable'),
      'available' => 'Y', // 'enum(Y,N)', 'Available'),
      'lead_time' => '7', // 'int', 'Lead Time (days)'),

      'weight' => '.25', // 'float(2)', 'Weight'),
      'weight_units' => 'lb', // 'enum(lb,kg)', 'Weight Units'),
      'length' => '7', // 'float(2)', 'Length'),
      'width' => '5', // 'float(2)', 'Width'),
      'height' => '.25', // 'float(2)', 'Height'),
      'length_units' => 'in', // 'enum(in,cm)', 'Length Units'),
    ),
  );

foreach ($del_data as $tmp) {
  $obj = new Deliverable(Globals::$dbaccess, $tmp['name']);
  foreach ($tmp as $key => $val) {
    if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
      continue; // skip
    }
    $obj->$key = $val;
  }
  $obj->save();
}
$obj->add_category('deliverable_category', 'deliverable_food');
$obj->save();
