<?php
/*
#doc-start
h1. ObjectInfo.php - template to copy for YASiteKit AnInstance object_infos

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

The primary use of ObjectInfo is to map object names into absolute paths
so that we can cut down on the length of the PHP include path. It also
has some other stuff - which will be useful for packages and generalizing
object data management.

h2. Attributes

All attributes are public

h3. Instance Attributes.

* object_name - string - Object Name
* valid - enum(N,Y) - Object Valid - an invalid object is one which does not
have an include path.Epson America, Inc.
Attn: Recycling Center
18300 Central Avenue
Carson, CA 90746
* path  - string - Absolute Path to Obj File
* source - enum(unknown,system,site,package) - Source Type
* source_name - string - Source Name - only valid if _source_ is _package_
* manageable - enum(Y,N) - Manageable? - must be Y if the object has persistent
data. Otherwise, not.
* management_url - string - Management URL

h3. Class Attribute

* ObjectInfo::$object_map - array - associative array which maps object names
to ObjectInfo instances. Gets constructed upon first instantiation of an ObjectInfo
instance OR first call to _load_object_map()_

h2. Class Methods

* do_require_once(object_name) - use in place of _require_once()_. Uses
the ObjectInfo database to require using the absolute path. Throw excption if
the ObjectInfo entry for _object_name_ is not valid. Returns FALSE if class _object_name_
already exists.
* do_require(object_name) - just like _do_require_once()_, except that
it throws an exception if _object_name_ already exists.
* load_object_map(dbaccess) - loads the object map and saves in ObjectInfo::$object_map.
Also returns the map in case you want to do something with it

h2. Instance Methods

* management_link($title = '') - returns an HTML Anchor element pointing to the _management_url_
or FALSE, if this object is not manageable.

#end-doc
*/

// global variables
require_once('aclass.php');
require_once('Package.php');

AClass::define_class('ObjectInfo', 'object_name', 
  array( // field definitions
    array('object_name', 'varchar(40)', 'Object Name'),
    array('valid', 'enum(N,Y)', 'Object Valid'),
    array('path', 'varchar(255)', 'Absolute Path to Obj File'),
    array('source', 'enum(unknown,system,site,site_package,system_package)', 'Source Type'),
    array('source_name', 'varchar(255)', 'Source Name'),
    array('manageable', 'enum(Y,N)', 'Manageable?'),
    array('management_url', 'varchar(255)', 'Management URL'),
  ),
  array(// attribute definitions
    'object_name' => array('filter' => ObjectInfo::OBJECT_NAME_REGX),
    'path' => 'required',
    'valid' => array('default' => 'N'),
    'source' => array('default' => 'unknown'),
  ));
// end global variables

// class definitions
class ObjectInfoException extends Exception {}

class ObjectInfo extends AnInstance {
  const OBJECT_NAME_REGX = '[A-Z][a-zA-Z]+';
  public static $object_map = FALSE;
  
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('ObjectInfo', $dbaccess, $attribute_values);

    // is this an empty object? or a filled in one which does NOT require initializtion
    if (!$this->object_name) {
      return;
    }

    // If path is filled in, then this is a fully described instance - either
    //  from the database or instantiation. We save it in case it's a new instance.
    if ($this->path) {
      $this->save();
      return;
    }

    // load the map if it isn't loaded
    // This has to come after the empty object code to avoid an infinite loop
    if (!ObjectInfo::$object_map) {
      ObjectInfo::load_object_map($dbaccess);
    }
    
    // at this point we know: 1. object_name is given; 2. path and source inffo are not.
    $fname = $this->object_name . ".php";
    if ($path = stream_resolve_include_path($fname)) {
      $this->path = $path;
      if (strpos($path, Globals::$objects_root) !== FALSE) {
        $this->source = 'site';
        $this->valid = 'Y';
      } elseif (strpos($path, Globals::$system_objects) !== FALSE) {
        $this->source = 'system';
        $this->valid = 'Y';
      } else {
        $this->source = 'unknowwn';
        $this->valid = 'N';
      }
    } else {
      require_once('archive_functions.php');
      $tmp_ar = array(array(Globals::$packages_root, 'site_package'),
                      array(Globals::$system_packages, 'system_package'));
      for ($idx = 0;$idx < 2;$idx++ ) {
        list($root, $source_type) = $tmp_ar[$idx];
        foreach (package_objects($root) as $row) {
          if ($row[0] == $this->object_name) {
            $this->source = $source_type;
            list($package_dir, $tmp) = explode(DIRECTORY_SEPARATOR, $row[1]);
            $this->source_name = IncludeUtilities::words_to_camel($package_dir);
            $this->path = $root . DIRECTORY_SEPARATOR . $row[1];
            $this->valid = 'Y';
            break 2;
          }
        }
      }
    }

    // set default managment info and save
    $this->manageable = 'N';
    $this->management_url = '';
    $this->save();
    if (!array_key_exists($this->object_name, ObjectInfo::$object_map)) {
      ObjectInfo::$object_map[$this->object_name] = $this;
    }
  } // end of __construct()
  
  public function management_link($title = '') {
    if (!$title) {
      $title = "Manage {$this->object_name}";
    }
    return $this->manageable == 'Y' ? "<a href=\"{$this->management_url}\" title=\"$title\">$title</a>" : FALSE;
  } // end of management_link()

  public static function do_require_once($file_name) {
    if (preg_match('/\.php$/', $file_name)) {
      $object_name = substr($file_name, 0, strlen($file_name) - 4);
    } else {
      $object_name = $file_name;
      $file_name = "{$object_name}.php";
    }

    // NOP if class exists
    if (class_exists($object_name)) {
      return TRUE;
    }
    
    // if in map, use it
    if (ObjectInfo::$object_map && array_key_exists($object_name, ObjectInfo::$object_map)) {
      require_once(ObjectInfo::$object_map[$object_name]->path);
    } else {
      $obj = new ObjectInfo(Globals::$dbaccess, $object_name);
      if ($obj->valid == 'Y') {
        require_once($obj->path);
      } else {
        // maybe it's really there someplace out on the include path
        require_once($file_name);
      }
    }
    return TRUE;
  } // end of require()


  public static function do_require($file_name) {
    if (preg_match('/\.php$/', $file_name)) {
      $object_name = substr($file_name, 0, strlen($file_name) - 4);
    } else {
      $object_name = $file_name;
      $file_name = "{$object_name}.php";
    }

    // NOP if class exists
    if (class_exists($object_name)) {
      return TRUE;
    }

    // if in map, use it
    if (ObjectInfo::$object_map && array_key_exists($object_name, ObjectInfo::$object_map)) {
      require(ObjectInfo::$object_map[$object_name]->path);
    } else {
      $obj = new ObjectInfo(Globals::$dbaccess, $object_name);
      if ($obj->valid == 'Y') {
        require($obj->path);
      } else {
        // maybe it's really there someplace out on the include path
        require($file_name);
      }
    }
    return TRUE;
  } // end of require()

  public static function &load_object_map($dbaccess) {
    $obj = new ObjectInfo($dbaccess);
    $ar = $obj->get_objects_where(NULL);
    ObjectInfo::$object_map = array();
    foreach ($ar as $object_name => $obj) {
      ObjectInfo::$object_map[$object_name] = $obj;
    }
    return ObjectInfo::$object_map;
  } // end of load_object_map()
}


class ObjectInfoManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'ObjectInfo', 'object_name');
  } // end of __construct()
}
?>
