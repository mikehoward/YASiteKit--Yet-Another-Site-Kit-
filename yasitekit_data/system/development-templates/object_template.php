<?php
/*
#doc-start
h1. object_template.php - template to copy for YASiteKit AnInstance objects

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This is a Bare Bones AnInstance object template.

To create a new object, copy this and hack.

Remember to replace:

* Object with your object's name
* object with your object's lower case name

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('Object', 'object_name', 
  array( // field definitions
    array('object_name', 'varchar(40)', 'Object Number'),
    array('char_data', 'char(30)', 'Char Data'),
    array('varchar_data', 'varchar(255)', 'Varchar Data'),
    array('text_data', 'text', 'Text Data'),
    array('email_data', 'email', 'Email Data'),
    array('enum_data', 'enum(val1,A,B,val3)', 'Enum Data'),
    array('set_data', 'set(a,b,c,frogs)', 'Set Data'),
    array('int_data', 'int', 'Int Data'),
    array('float_data', 'float', 'Float Data'),
    array('join_data', 'join(OtherObject.display_field)', 'Join Data'),
    array('file_data', 'file(files/{object_name})', 'File Data'),
    array('date_data', 'date', 'Date Data'),
    array('time_data', 'time', 'Time Data'),
    array('datetime_data', 'datetime', 'Date + Time Data'),
    
    array('invisible_data', 'text', 'Invisible Data - programatic text'),
    array('immutable_data', 'text', 'Only Initializable, can\'t be changed'),
  ),
  array(// attribute definitions
      'email' => 'encrypt',
      'file_data' => array('path' => 'object_files/{object_name}'),
      'invisible_data' => 'invisible',
      'immutable_data' => 'immutable',
      
      // accessible via web services
      'object_name' => 'public',
      'text_data' => 'public',
      ));
// end global variables

// class definitions
class Object extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Object', $dbaccess, $attribute_values);
  } // end of __construct()
}


class ObjectManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Object', 'object_name');
  } // end of __construct()
}
?>
