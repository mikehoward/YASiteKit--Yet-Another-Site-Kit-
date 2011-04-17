<?php
/*
#doc-start
h1. RequestRouter.php - Maps url's into a dispatcher + parameters

Created by  on 2011-02-11
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

The RequestRouter maps URLs of the form http:host.dom/routing_key/parm1/parm2/.../parmN
to display and managements routines with security information.

h2. Instantiation

The normal instantiation is simply:

pre. $foo = new RequestRouter($dbaccess, $routing_key)

where,

* $dbaccess is a DBAccess instance
* routing_key is an identifier - normaly the lead component of a URL.

The _routing_key_ is normally a word derived from the package name. It
may include diferentiating words separated by underscores (_). For
example, say the 'foo' package provides for the display of both single 'foo'
objects and lists of 'Foo' objects. Then there may be two routing_keys:

* foo_single - which will map URL's like '/foo_single/foo_key' to the
Foo displayer for single Foo objects
* foo_list - which will map URL's of the form '/foo_list/start_date/end_date'
to the Foo list displayer which is displays all Foo's with dates in a range.

h2. Attributes

The normal AnInstance, permanent attributes are:

* routing_key - string - single word taken from the first segment of the URL.
This is the key which is used to chose the display and management templates
* resource_name - string - Display string for management and report
* script_name - string - the YATheme template which will display the
requested URL
* path_map - string - slash (/) separated path name. Essentially, a map into the pathinfo
part of the URL. This gives names for each component and specifies how many components
this RequestRouter instance handles.
* required_authority - set - the authority required to view URLs of this type. Defaults to '',
meaning anyone may access.
* authority_field_name - string - name of the 'authority' field in objects referenced
in these URL's. Only applies to URLs which point to objects and is only used to
override the _manage_authority_ values. Defaults to '' - which is empty and means that
the field does not exist in these records.

Additional _transcient_ attributes are:

* component_map - the results of calling _map_pathinfo($pathinfo)_ are
saved in this variable. If _map_pathinfo()_ has not yet been called, it is array().
* path_map_array - array - contains the components of the path from _path_map_
after exploding the string based on the slash (/) character.
* uri - this is the host relative URI for a specific path. It is created by
_map_pathinfo($pathinfo)_ and is composed of the _routing_key_ followed
by _$pathinfo_. If _map_pathinfo()_ has not yet been called, it is FALSE.

h2. Class Methods

None

h2. Instance Methods

The usual AnInstance methods plus . . .


* map_pathinfo($pathinfo) - returns an associative array where the keys are taken from
the _path_map_ attribute and values are taken from the $pathinfo string, split on slash
marks (/)
* link($title = '') - returns an HTML anchor pointing to URL last processed by _map_pathinfo()_

#end-doc
*/

// global variables
require_once('aclass.php');

AClass::define_class('RequestRouter', 'routing_key', 
  array( // field definitions
    array('routing_key', 'varchar(40)', 'Routing Key'),
    array('resource_name', 'varchar(255)', 'Resource Name'),
    array('script_name', 'varchar(255)', 'Script'),
    array('path_map', 'varchar(255)', 'Path to Parm Map'),
    array('required_authority', 'set(ANY,C,A,W,M,S,X)', 'Required Authority to Access'),
    array('authority_field_name', 'varchar(255)', 'Authority Field Name'),
  ),
  array(// attribute definitions
      'routing_key' => array('required', 'filter' => '\w+'),
      'script_name' => array('required', 'filter' => '[A-Z][A-Za-z0-9]*\.(php|tpl)'),
      'path_map' => array('required', 'filter' => '[a-z][_a-z0-9]*(\/[a-z][_a-z0-9]*)*'),
      'required_authority' => array('default' => ''),
      'authority_field_name' => array('required', 'default' => 'req_authority'),
      ));
// end global variables

// class definitions
class RequestRouterException extends Exception {}

class RequestRouter extends AnInstance {
  private $cache = array(
    'uri' => FALSE,
    'path_map_array' => array(),
    'component_map' => array(),
  );
  
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('RequestRouter', $dbaccess, $attribute_values);
    if (!$this->path_map_array) {
      $this->path_map_array = explode('/', $this->path_map);
    }
  } // end of __construct()
  
  public function __get($name) {
    if (array_key_exists($name, $this->cache['component_map'])) {
      return $this->cache['component_map'][$name];
    }
    switch ($name) {
      case 'component_map':
      case 'path_map_array':
      case 'uri':
        return array_key_exists($name, $this->cache) ? $this->cache[$name] : FALSE;
        break;
      default:
        // return in_array($name, $this->attribute_names) ? parent::__get($name) : FALSE;
        return parent::__get($name);
    }
  } // end of __get()
  
  public function __set($name, $value) {
    if (array_key_exists($name, $this->cache['component_map'])) {
      throw new RequestRouterException("RequestRouter::__set(): attempt to set read only variable $name");
    }
    switch ($name) {
      case 'component_map':
      case 'path_map_array':
      case 'uri':
        $this->cache[$name] = $value;
        break;
      case 'path_map':
        $ar = explode('/', $value);
        foreach ($ar as $tmp) {
          if (in_array($tmp, $this->attribute_names) || in_array($tmp, array('component_map', 'path_map_array', 'uri'))) {
            throw new RequestRouterException("RequestRouter::__set(): path_map variable contains a name which conflicts with the RequestRouter Field Name '$tmp'");
          }
        }
        // Intentional Fall Through
      default:
        parent::__set($name, $value);
        break;
    }
  } // end of __set()
  
  public function __isset($name) {
    if (array_key_exists($name, $this->cache)) {
      return isset($this->cache[$name]);
    } elseif(array_key_exists($name, $this->cache['component_map'])) {
      return isset($this->cache['component_map'][$name]);
    } else {
      return parent::__isset($name);
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'path_map_array':
      case 'uri':
        throw new RequestRouterException("RequestRouter::__unset($name): illegal attempt to unset read only, transcient variable: '$name'");
      default:
        return parent::__unset($name);
    }
  } // end of __unset()
  
  public function map_pathinfo($pathinfo) {
    static $len = FALSE;
    $ar = explode('/', $pathinfo);
    if (count($ar) > count($this->path_map_array)) {
      throw new RequestRouterException("RequestRouter::map_pathinfo($pathinfo): parameter count mismatch: "
        . " expect " . count($this->path_map) . " or less; received " . count($ar));
    }
    if ($len === FALSE) {
      $len = count($this->path_map_array);
    }
    $this->uri = "/{$this->routing_key}/{$pathinfo}";
    $tmp = array();
    for ($idx=0;$idx<$len;$idx++) {
      $tmp[$this->path_map_array[$idx]] = isset($ar[$idx]) ? $ar[$idx] : FALSE;
    }
    return ($this->component_map = $tmp);
  } // end of map_pathinfo()
  
  // public function link($title = '') {
  //   if ($this->uri) {
  //     return "<a href=\"{$this->uri}\" title=\"{$this->resource_name}\">" . ($title ? $title : $this->resource_name) . "</a>";
  //   } else {
  //     return '';
  //   }
  // } // end of link()
  
  public function dump($msg = '') {
    $str = parent::dump($msg);

    $str .= "component_map:\n";
    foreach ($this->component_map as $key => $val) {
      $str .= "   $key => $val\n";
    }
    
    $str .= "path_map_array: " . implode(', ', $this->path_map_array) . "\n";
    $str .= "uri: $this->uri\n";
    return $str;
  } // end of dump()
}


class RequestRouterManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'RequestRouter', 'resource_name');
  } // end of __construct()
}
?>
