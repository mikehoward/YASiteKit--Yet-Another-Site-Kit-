<?php 
/*
#begin-doc
h1(#yafiles). YAThemeFiles - YATheme file cross reference

YAThemeFiles objects are used to maintain references between YATheme head files -
that is, the file which is at the top of a YATheme rendering tree - and all
included files.

It is primarily used for maintaining the YATheme cache.

This object is not instantiated directly. It is designed to be used
in the implementatino of YATheme. Other than being an AnInstance
object, it is not documented.

#end-doc
*/

require_once('aclass.php');
// require_once('YATheme.php');


AClass::define_class('YAThemeFiles', 'file_name',
  array(
    array('file_name', 'varchar(255)', 'File Name'),
    array('modify_timestamp', 'datetime', 'Modified Timestamp'),
    array('yatheme_heads', 'join(YATheme.file_name,multiple)', 'YATheme Head Files')),
  array(
    'modify_timestamp' => 'readonly',
    'yatheme_heads' => 'readonly',
  ));
class YAThemeFiles extends AnInstance {
  public function __construct($dbaccess, $attr_values = NULL) {
    parent::__construct('YAThemeFiles', $dbaccess, $attr_values);
    if (isset($this->file_name) && !isset($this->modify_timestamp)) {
      $this->modify_timestamp = new DateTime('now');
    }
  } // end of __construct()
}
