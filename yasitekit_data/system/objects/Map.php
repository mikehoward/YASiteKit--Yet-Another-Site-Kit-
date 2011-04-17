<?php
/*
#doc-start
h1.  Map - The Map object manages mapping between versions of a site

Created by  on 2010-06-18.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved. Licensed under the terms of the GNU Lesser
GNUL License, version 3.  See http://www.gnu.org/licenses/ for details.

bq. THIS SOFTWARE HAS NO WARRANTEE OR REPRESENTATION FOR FITNESS OF PURPOSE.
USE AT YOUR OWN RISK.

Maps are describe in some detail in "Development-Control":/doc.d/ControllingSiteDevelopment.html.
Additonal relavant material is in "Database States":/doc.d/DatabaseState.html and
"Overview of YASiteKit":/doc.d/Overview.html

Maps are really implmented using two classes:

* "MapForObject":#map_for_object is used to analyze differences between two versions of the same AnInstance calss
* "Map":#map is used to analyze and create a mapping function for an entire site.

* "Instantiation":#instantiation
* "Attributes":#attributes
* "Class Methods":#class_methods
* "Instance Methods":#instance_methods

h2(#map). Map Class

The Map class is used to compare two versions of all the AnInstance classes in a YASiteKit
site. It will analyze them and determine if a map is needed, if one is possible and
can create an intereactive form to create one.

h3(#map_instantiation). Instantiation

pre. $map_obj = new Map($source, $target = NULL)

where _$source_ and _$target_ are
associative arrays of arrays as describe in "MapForObject Instantiation":#map_for_object_instantiation.
The array keys are the AnInstance classes of the site. If _$target_ is null (the default),
then AClass::php_defs_ar() is called to get the arrays for the current version of the site.
In other words, Map will be initialized from a backup archive and the current definition of
all the objects. This is generally what you want.

Generally you can forget about the _$target_ argument, unless you come up with a case where
you need to compute a map between two archives. I haven't thought of a real use case for this,
but there may be one - and having capability is essentially free.

h3(#map_attributes). Attributes

First, all attributes are read-only. Only __get() is implemented, so *isset()*, *unset()*
and attribute assignments don't work.

The only attribute which is interesting - outside of the object itself - is _site_state_.

_site_state_ can take on three values: no-map, need-map, and 'illegal'.

The other attributes are:

* _source_ and _target_ (passed to constructor at instantiation),
* _map_for_object_ar_ - which is an associative array keyed by AnInstance class names
into all the MapForObject instances for the site.
* errors - the accumlation of error messages

h3(#map_class_methods). Class Methods

none

h3(#map_instance_methods). Instance Methods

* create_map_form($action) - analyzes the differences between the source and target and
returns one of three strings:
** if no map is needed, then it's simply a message to that effect
** if the site is in an illegal state, then you also get a message
** if a map is needed, then the string is a &lt;form&gt; element which invokes the
_$action_ supplied.
* process_form($rc, $dump_dir) - eats the result of a _create_map_form()_ form submission
and writes out the map function and map function description to the files _map_data_function.php
and _map_data_description.html, respectively.
_$rc_ must be a "RequestCleaner":/doc.d/system-includes/request_cleaner.html object.
_$dump_dir_ is the dump directory into which the map files are written.
* dump(msg = '') - returns a string containing information about the Map instance. This
consists of the contents of the _errors_ and _site_state_ attributes along with the
map instructions for each AnInstance object which requires a mapping.

h2(#map_for_object). MapForObject Class

As the name suggests, a MapForObject instance is used to examine and prepare a map
for all the attributes of a single object. It encapsulates all the relationships
between AClass data types and their compitability.

h3(#map_for_object_instantiation). Instantiation

pre. $foo = new MapForObject(class-name, source-defs, target-defs)

This creates a MapForObject instance for the given class-name. It expects that
both _source-defs_ and _target-defs_ to be associative arrays with three keys:

* defs - a copy of the attribute definitions used to create the class - parameter
3 of the "AClass::define_class()":/doc.d/system-includes/aclass.html call.
* keys - a copy of the keys_list attribute of the class
* props - a copy of attribute_properites attribute of the class.

These arrays can either be obtained from an archive - in the file *_acalss_attribute_defs.php*
- or from a the AClass static method _php_defs_ar()_.

h3(#map_for_object_attributes). Attributes

All attributes are read-only. They aren't very interesting unless you have to fix a bug
in the Map class. Read the code for details.

The only interesting attributes are:

* all_attributes - a list of all attributes used in both source and target
* new_attributes, missing_attributes, retained_attributes - arrays of attribute names
which are new, missing from or retained in the the target.
* map_instructions - an associate array with attributes as keys.
values are array. The first element of a value array determines the format
** - new - - second element is an array of 3 element arrays: array(missing-attribute, safety, comment)
** - delete - second element is 'Safe', third element is comment
** - change - second element is _safety_, third element is comment
** - unchanged -  - no more elements
***   safety and comment are returned values from _data_type_change_analysis()


h3(#map_for_object_class_methods). Class Methods

none

h3(#map_for_object_instance_methods). Instance Methods

* need_mapP() - returns true if a map is needed to convert from _source_ to _target_
* create_map_instructions() - populates the attribute _map_instructions_. Returns nothing.

#end-doc
*/

// global variables

// end global variables

// class definitions

class MapForObjectException extends Exception {}

class MapForObject {
  private $dbaccess;
  private $tablename;
  private $cls_name;
  private $source_defs;
  private $target_defs;
  private $keys;
  private $errors = '';
  private $source_attr_type_map = array();
  private $target_attr_type_map = array();
  
  private $dont_care = FALSE;
  
  private $missing_attributes;
  private $new_attributes;
  private $retained_attributes;
  private $all_attributes;
  
  private $map_instructions;
  
  public function __construct($dbaccess, $cls_name, $source_defs, $target_defs)
  {
    if (!$dbaccess instanceof DBAccess) {
      throw new Exception("FLOOP!!!");
    }
    $this->dbaccess = $dbaccess;
    
    $this->cls_name = $cls_name;
    $this->source_defs = $source_defs;
    $this->target_defs = $target_defs;
    $this->tablename = isset($this->source_defs['tablename']) ? $this->source_defs['tablename'] : strtolower($this->cls_name);
    $this->dont_care = !$dbaccess->rows_in_table($this->tablename);

    $this->missing_attributes = array_diff(array_keys($source_defs['props']),
      array_keys($target_defs['props']));
    $this->new_attributes = array_diff(array_keys($target_defs['props']),
      array_keys($source_defs['props']));
    $this->retained_attributes = array_intersect(array_keys($source_defs['props']),
      array_keys($target_defs['props']));
    $this->all_attributes = array_merge($this->missing_attributes, $this->new_attributes,
        $this->retained_attributes);
    foreach (array('missing_attributes', 'new_attributes', 'retained_attributes', 'all_attributes') as $ar) {
      sort($this->$ar);
    }
    foreach ($source_defs['defs'] as $row) {
      $this->source_attr_type_map[$row[0]] = $row[1];
    }
    foreach ($target_defs['defs'] as $row) {
      $this->target_attr_type_map[$row[0]] = $row[1];
    }
  } // end of __construct()

  public function __toString()
  {
    return "MapForObject($this->cls_name)";
  } // end of __toString()
  
  public function __get($name)
  {
    switch ($name) {
      case 'all_attributes':
      case 'cls_name':
      case 'dont_care':
      case 'errors':
      case 'map_instructions':
      case 'missing_attributes':
      case 'new_attributes':
      case 'retained_attributes':
      case 'source_attr_type_map':
      case 'source_defs':
      case 'tablename':
      case 'target_attr_type_map':
      case 'target_defs':
        return $this->$name;
      default:
        throw new MapForObjectException("MapForObject::__get():illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    switch ($name) {
      case 'all_attributes':
      case 'cls_name':
      case 'errors':
      case 'map_instructions':
      case 'missing_attributes':
      case 'new_attributes':
      case 'retained_attributes':
      case 'source_attr_type_map':
      case 'source_defs':
      case 'tablename':
      case 'target_attr_type_map':
      case 'target_defs':
        throw new MapForObjectException("MapForObject::__set():attempt to set read-only attribute '$name'");
      default:
        throw new MapForObjectException("MapForObject::__set():illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __isset($name)
  {
    switch ($name) {
      case 'all_attributes':
      case 'cls_name':
      case 'dont_care':
      case 'errors':
      // case 'map_instructions':
      case 'missing_attributes':
      case 'new_attributes':
      case 'retained_attributes':
      case 'source_attr_type_map':
      case 'source_defs':
      case 'target_attr_type_map':
      case 'target_defs':
        return TRUE;
      case 'map_instructions':
        return $this->$name !== FALSE;
      default:
        throw new MapForObjectException("MapForObject::__isset():illegal attribute '$name'");
    }
  } // end of __get()
  
  public function __unset($name)
  {
    switch ($name) {
      case 'all_attributes':
      case 'cls_name':
      case 'errors':
      case 'map_instructions':
      case 'missing_attributes':
      case 'new_attributes':
      case 'retained_attributes':
      case 'source_attr_type_map':
      case 'source_defs':
      case 'target_attr_type_map':
      case 'target_defs':
        throw new MapForObjectException("MapForObject::__unset():attempt to unset attribute '$name'");
      default:
        throw new MapForObjectException("MapForObject::__unset():illegal attribute '$name'");
    }
  } // end of __get()

  public function check_keys()
  {
    if ($this->dont_care) {
      return TRUE;
    }
    
// echo "MapForObject->check_keys() for $this->cls_name: ";
    if ($this->source_defs['keys'] != $this->target_defs['keys']) {
      $this->errors = "Illegal Model Key Edit: ";
      if (($missing_keys = array_diff($this->source_defs['keys'], $this->target_defs['keys']))) {
        $this->errors .= " Missing or Renamed Keys: " . implode(', ', $missing_keys);
      }
      if (($new_keys = array_diff($this->target_defs['keys'], $this->source_defs['keys']))) {
        $this->errors .= " New or Renamed Keys: " . implode(', ', $new_keys);
      }
      return FALSE;
    }

    $this->keys = $this->target_defs['keys'];
// echo "keys: " . implode(',', $this->keys);

    foreach ($this->keys as $attr) {
      if ($this->source_attr_type_map[$attr] != $this->target_attr_type_map[$attr]) {
        if (!$this->errors) {
          $this->errors = "Illegal Key Data Type Redefinition:\n";
        }
        $this->errors .= " {$this->cls_name}.{$attr} redefined from {$this->source_attr_type_map[$attr]} -> {$this->target_attr_type_map[$attr]}]n";
      }
    }
// echo "\n";
    return $this->errors ? FALSE : TRUE;
  } // end of check_keys()

  private function data_type_change_analysis($old, $new)
  {
    static $legal_transitions = array(
      'text' => array('text', 'char', 'varchar', 'enum', 'email'),
      'char' => array('char', 'varchar', 'text', 'email', 'enum'),
      'varchar' => array('varchar', 'text', 'char', 'email', 'enum'),
      'enum' => array('enum', 'set', 'text', 'char', 'varchar'),
      'set' => array('set'),
      'email' => array('email', 'text', 'char', 'varchar'),
      'file' => array('file'),
      'join' => array('join'),
      'int' => array('int', 'float', 'char', 'varchar', 'text'),
      'float' => array('float', 'char', 'varchar', 'text'),
      'date' => array('date', 'time', 'datetime'),
      'time' => array('date', 'time', 'datetime'),
      'datetime' => array('date', 'time', 'datetime'),
        );
    if ($this->dont_care) {
      return array('Safe', '<span style=\"background:green;color:black">Don\'t Care - No Data</span>');
    }
    $old_matches = AClass::match_datatype($old);
    $new_matches = AClass::match_datatype($new);
    $old_type = $old_matches[1];
    $new_type = $new_matches[1];
    if (!in_array($new_type, $legal_transitions[$old_type])) {
      return array('Illegal',
        "<span style=\"background:red;color:black\">ERROR: going from $old_type -> $new_type is Illegal</span>");
    }
    
    switch ($old_type) {
      case 'text':
        if ($new_type == 'text')
          return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        else
          return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may Truncate Data</span>");
        break;
      case 'char':
      case 'varchar':
        switch ($new_type) {
          case 'text':
            return array('Safe', "<span style=\"background:green;color:black\">$old -> $new is safe</span>");
          case 'char':
          case 'varchar':
            if (intval($old_matches[2]) <= intval($new_matches[2]))
              return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
            else
              return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may Truncate Data</span>");
          case 'enum':
            return array('Safe',
              "<span style=\"background:red;color:black\">WARNING: going from $old to $new may truncate and invalidate data</span>");
          case 'email':
            if (intval($old_matches[2]) <= 255)
              array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
              array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may Truncate Data</span>");
        }
        break;
      case 'enum':
      case 'set':
        if ($new_type == 'enum' || $new_type == 'set') {
          $old_values = explode(',', $old_matches[2]);
          $new_values = explode(',', $new_matches[2]);
          $lost_values = array_diff($old_values, $new_values);
          if (count($lost_values) == 0)
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
          else
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data values"
              . implode(',', $lost_values) . "</span>");
        } else {
          return array('Unsafe',
            "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data integrity</span>");
        }
        break;
      case 'email':
        return array('Unsafe', 
          "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data integrity</span>");
        break;
      case 'file':
        return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose file path information and/or create orphaned files</span>");
      case 'join':
        throw new Exception("data_type_change_analysis($old, $new): Internal Error - this is not possible");
      case 'int':
        switch ($new_type) {
          case 'char':
          case 'varchar':
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data precision</span>");
          default:
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        }
        break;
      case 'float':
        switch ($new_type) {
          case 'char':
          case 'varchar':
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data precision</span>");
          default:
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        }
        break;
      case 'date':
        switch ($new_type) {
          case 'time':
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data precision</span>");
          default:
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        }
        break;
      case 'time':
        switch ($new_type) {
          case 'date':
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data precision</span>");
          default:
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        }
        break;
      case 'datetime':
        switch ($new_type) {
          case 'date':
          case 'time':
            return array('Unsafe', "<span style=\"background:orange;color:black\">WARNING: going from $old to $new may lose data precision</span>");
          default:
            return array('Safe', "<span style=\"background:green;color:black\">Going from $old to $new is safe</span>");
        }
        break;
      default:
        throw new MapForObjectException("MapForObject::data_type_change_analysis($old, $new): old type error");
    }
    ob_start();
    print_r($old_matches);
    $tmp = preg_replace("/\n/", ',', ob_get_clean());
    echo "old: $old, old_type: $old_type, old_matches: $tmp\n";
    ob_start();
    print_r($new_matches);
    $tmp = preg_replace("/\n/", ',', ob_get_clean());
    echo "new: $new, new_type: $new_type, new_matches: $tmp\n";
    throw new MapForObjectException("MapForObject::data_type_change_analysis($old, $new):internal error");
  } // end of data_type_change_analysis()

  public function need_mapP()
  {
    if ($this->dont_care) {
      return FALSE;
    }
    
    // initialize $need_map variable
    // we always need a map if there are missing attributes AND added attributes
    if ($this->missing_attributes && $this->new_attributes)
      return TRUE;
    
    // if a retained attribute has changed type, we need to check on legality and notify user
    //  so we want a map
    foreach ($this->retained_attributes as $attr) {
      if ($this->source_attr_type_map[$attr] != $this->target_attr_type_map[$attr]) {
        return TRUE;
      }
    }

    $this->map_instructions = NULL;
    return FALSE;
  } // end of need_mapP()
  
  // map instructions is an associate array with attributes as keys.
  //  values are array. The first element of a value array determines the format
  //    -new- - second element is an array of 3 element arrays: array(missing-attribute, safety, comment)
  //    -delete- second element is 'Safe', third element is comment
  //    -change- second element is _safety_, third element is comment
  //    -unchanged- - no more elements
  //   safety and comment are returned values from _data_type_change_analysis()
  public function create_map_instructions()
  {
    if ($this->dont_care) {
      return;
    }
    
    $ar = array();
    foreach ($this->all_attributes as $attr) {
      if (in_array($attr, $this->new_attributes) ) {
        $tmp_ar = array(array('-new-', 'Safe', "<span class=\"safe\">Creating New Attribute $attr is Safe</span>"));
        foreach ($this->missing_attributes as $possible) {
          $tmp = $this->data_type_change_analysis($this->source_attr_type_map[$possible],
                $this->target_attr_type_map[$attr]);
          if ($tmp[0] != 'Illegal') {
            array_unshift($tmp, $possible);
            $tmp_ar[] = $tmp;
          }
        }
        $ar[$attr] = array('-new-', $tmp_ar);
      } elseif (in_array($attr, $this->missing_attributes)) {
        $ar[$attr] = array('-delete-', 'Safe', "<span class=\"warning\">WARNING: $attr will be deleted if not used in rename</span>");
      } elseif ($this->source_attr_type_map[$attr] != $this->target_attr_type_map[$attr]) {
        // retained attribute with changed data types
        $tmp = $this->data_type_change_analysis($this->source_attr_type_map[$attr],
              $this->target_attr_type_map[$attr]);
        array_unshift($tmp, '-changed-');
        $ar[$attr] = $tmp;
      } else {
        $ar[$attr] = array('-unchanged-');
      }
    }
    $this->map_instructions = $ar;
  } // end of create_map_instructions()
}

class MapException extends Exception {}

class Map {
  private $errors = '';     // error messages
  private $dbaccess;
  private $source;
  private $target;
  private $map_for_object_ar= array();
  private $site_state = 'no-map';

  public function __construct($dbaccess, $source, $target = NULL)
  {
    $this->dbaccess = $dbaccess;
    $this->source = $source;
    $this->target = $target ? $target : AClass::php_defs_ar();
    $this->all_objects = array_merge(array_keys($this->source), array_keys($this->target));
    $this->retained_objects = array_intersect(array_keys($this->source), array_keys($this->target));
    $this->missing_objects = array_diff(array_keys($this->source), array_keys($this->target));
    $this->added_objects = array_diff(array_keys($this->target), array_keys($this->source));
    
    $this->create_map_for_object_ar($this->dbaccess);
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'errors':
      case 'source':
      case 'target':
      case 'site_state':
      case 'map_for_object_ar':
        return $this->$name;
      default:
        throw new MapException("Map::__get($name): Illegal attribute");
    }
  } // end of __get()

  // returns:
  //  TRUE if no map needed
  //  FALSE if illegal state
  //  array() of defining map options if map required and possible
  private function create_map_for_object_ar($dbaccess)
  {
    foreach ($this->retained_objects as $obj_name) {
      $this->map_for_object_ar[$obj_name] = new MapForObject($dbaccess, $obj_name, $this->source[$obj_name],
          $this->target[$obj_name]);
      if (!$this->map_for_object_ar[$obj_name]->check_keys()) {
        $this->errors .= "Errors for $obj_name: " . $map_for_object_ar[$obj_name]->errors;
        $this->site_state = 'illegal';
        continue;
      }
      if ($this->map_for_object_ar[$obj_name]->need_mapP()) {
        if ($this->site_state == 'no-map')
          $this->site_state = 'need-map';
        $this->map_for_object_ar[$obj_name]->create_map_instructions();
        $this->errors .= $this->map_for_object_ar[$obj_name]->errors;
      }
    }
  } // end of create_map_for_object_ar()
  
  public function create_map_form($action)
  {
    switch ($this->site_state) {
      case 'no-map';
        return "<div class=\"ok box\"><p>No Mapping Required</p></div>\n";
      case 'illegal':
        return "<div class=\"box error\"><p>Impossible to Create Map - Illegal Edit</p><div style=\"white-space:pre;\">$this->errors</div></div>\n";
      case 'need-map':
        $str = "<div class=\"box advisory\">\n";
        $str .= "<p class=\"bold\">Submit the Form to Create or Recreate the Map Function</p>\n";
        $str .= " <form class=\"display-target\" action=\"$action\" method=\"post\" accept-charset=\"utf-8\">\n";
        $str .= "  <ul>\n";
        foreach ($this->map_for_object_ar as $obj_name => $map_for_obj) {
          if ($map_for_obj->map_instructions) {
            $str .= " <li>Object: $obj_name - NOTE: only changed attributes listed\n";
            $str .= "  <input type=\"hidden\" name=\"map_objects[]\" value=\"$obj_name\">\n";
            $str .= "   <ul>\n";
            foreach ($map_for_obj->all_attributes as $attr) {
              if (($inst = $map_for_obj->map_instructions[$attr])) {
                switch ($inst[0]) {
                  case '-new-':
                    if (count($inst[1]) > 1) {
                      $str .= "    <li class=\"advisory\">$attr: New Attribute OR Name Change\n";
                      $str .= "     <select name=\"{$obj_name}_{$attr}\">\n";
                      foreach ($inst[1] as $row) {
                        list($old_name, $safety, $comment) = $row;
                        switch ($old_name) {
                          case '-new-':
                            $str .= "    <option value=\"-new-\">Create New Field $attr</option>\n";
                            break;
                          default:
                            $str .= "      <option value=\"{$old_name}\">Rename {$old_name} to $attr: 
  {$comment}</option>\n";
                            break;
                        }
                      }
                      $str .= "     </select>";
                      $str .= "    </li>\n";
                    } else {
                      $str .= "   <li class=\"ok\">$attr: is a New Attribute"
                       . "<input type=\"hidden\" name=\"${obj_name}_{$attr}\" value=\"-new-\"></li>\n";
                    }
                    break;
                  case '-delete-':
                    $str .= "    <li class=\"warning\">$attr: will be Deleted unless used in Name Change</li>\n";
                    break;
                  case '-changed-':
                    $str .= "    <li class=\"advisory\">$attr: Type Change is {$inst[1]} - {$inst[2]}</li>\n";
                    break;
                  case '-unchanged-':
                    break;
                  default:
                    $str .= "<li>Illegal Map Instruction for $attr: {$inst[0]}</li>\n";
                    break;
                }
              }
            }
            $str .= "   </ul>\n  </li> <!-- end of $obj_name -->\n";
          }
        }
        $str .= " <li><input type=\"submit\" name=\"submit\" value=\"Create Map\"></li>\n";
        $str .= " </ul>\n";
        return $str . " </form>\n</div>\n";
    }
  } // end of create_map_form()
  
  // returns map function definition as a string
  public function process_form($rc, $dump_dir)
  {
    $doc_str = "<div id=\"display-map-actions\" class=\"box click-display\">\n<p class=\"bold\">Current map_data() Function Description:</p>\n <ul class=\"display-target\">\n";
    $func_str = "function map_data(\$cls, \$in_ar) {\n";
    $func_str .= "  \$out_ar = \$in_ar;\n";
    $func_str .= "  switch (\$cls) {\n";
    
    foreach ($rc->safe_post_map_objects as $obj_name) {
      echo "Building Map for $obj_name\n";
      if (!($map_for_object = $this->map_for_object_ar[$obj_name])) {
        continue;
      }
      $doc_str .= "  <li>$obj_name Attribute Changes:\n   <ul>";
      $func_str .= "    case '$obj_name':\n";
      $not_deleted = array();
      foreach ($map_for_object->all_attributes as $attr) {
        $form_name = "safe_post_{$obj_name}_$attr";
        $inst = $map_for_object->map_instructions[$attr];
        switch ($inst[0]) {
          case '-new-':
            $new_type = $map_for_object->target_attr_type_map[$attr];
            if ($rc->$form_name == '-new-') {
              $doc_str .= "   <li class=\"ok\">'{$attr}' is a new attribute of type '{$new_type}'</li>\n";
              $func_str .= "    // {$attr} is a new attribute of type '$new_type'\n";
              // $func_str .= "   \$out_ar['{$attr}'] = NULL\n";
            } else {
              $source_attr = $rc->$form_name;
              $old_type = $map_for_object->source_attr_type_map[$source_attr];
              $doc_str .= "   <li class=\"advisory\">'{$source_attr}' is reaned to '$attr' and converting {$old_type} -> {$new_type}</li>\n";
              $func_str .= "    \$out_ar['{$attr}'] = \$in_ar['$source_attr'];\n";
              $func_str .= "    unset(\$out_ar['{$source_attr}']);\n";
            }
            break;
          case '-unchanged-':
            $doc_str .= "   <li>'{$attr}' is unchanged</li>\n";
            $func_str .= "   // {$attr} is unchanged\n";
            break;
          case '-changed-':
            $doc_str .= "   <li class=\"advisory\">'{$attr}' type is changed: {$inst[1]}: {$inst[2]}</li>\n";
            $func_str .= "    // $attr type change: {$inst[1]}: {$inst[2]}\n";
            break;
          case '-delete-':
            $doc_str .= "      <li class=\"warning\">'{$attr}' is being Deleted</li>\n";
            $func_str .= "    unset(\$out_ar['$attr']);\n";
            break;
        }
      }

      $doc_str .= "   </ul>\n   </li>\n";
      $func_str .= "      break;\n";
    }
    $doc_str .= "</ul>\n</div>\n";
    $func_str .= "   }\n";
    $func_str .= " return \$out_ar;";
    $func_str .= "}\n";
    
    return array($doc_str, $func_str);
  } // end of process_form()
  
  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\"\n";
    $str .= "Dump of Map Object: $msg\n";
    $str .= "  errors: $this->errors\n";
    $str .= "  site_state: $this->site_state\n";
    $str .= "  map instructions:\n";
    foreach ($this->map_for_object_ar as $map_for_obj) {
      if ($map_for_obj->map_instructions) {
        $str .= "   $map_for_obj->cls_name:\n";
        foreach ($map_for_obj->all_attributes as $attr) {
          $tmp = $map_for_obj->map_instructions[$attr];
          switch ($tmp[0]) {
            case '-new-':
              $str .= "   attribute '$attr': {$tmp[0]}:\n";
              foreach ($tmp[1] as $tmp2) {
                $str .= "      {$tmp2[0]}: {$tmp2[1]}, {$tmp2[2]}\n";
              }
              break;
            case '-delete-':
            case '-changed-':
              $str .= "   attribute '$attr': {$tmp[0]}: {$tmp[1]}, {$tmp[2]}\n";
              break;
            case '-unchanged-':
              $str .= "   attribute '$attr': unchanged\n";
              break;
            default:
              $str .=   "Illegal Instructions for attribute '$attr': ";
              ob_start(); var_dump($tmp) ; $str .= ob_get_clean();
              break;
          }
        }
      } else {
        $str .= "   $map_for_obj->cls_name: none\n";
      }
    }

    // private $source;
    // private $target;
    
    return $str . "</div>\n";
  } // end of dump()
}

// end class definitions

?>
