<?php
/*
#doc-start
h1. Downloadabe.php - template to copy for YASiteKit AnInstance downloadables

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

A Downloadable defines the geometry and price of a downloadable image.

This is a stub. It probably doesn't do what you want.

Fields:

* name - string - key - name of downloadable
* title - string - display string
* width - int - width in pixels
* height - int - height in pixels
* price - float - price in USD

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('Downloadable', 'name', 
  array( // field definitions
    array('name', 'varchar(40)', 'Name'),
    array('title', 'varchar(255)', 'Title'),
    array('width', 'int', 'Width (pixels)'),
    array('height', 'int', 'Height (pixels)'),
    array('file_format', 'enum(jpg,gif,png,tiff,pdf,zip,gz)', 'File Format'),
    array('price', 'float', 'Price'),
  ),
  array(// attribute definitions
    'name' => 'public',
    'title'=> 'public',
    'width' => 'public',
    'height' => 'public',
    'file_format' => 'public',
    'price' => 'public',
      ));
// end global variables

// class definitions
class Downloadable extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Downloadable', $dbaccess, $attribute_values);
  } // end of __construct()
}


class DownloadableManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Downloadable', 'title');
  } // end of __construct()
}
?>