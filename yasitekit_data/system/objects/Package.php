<?php
/*
#doc-start
h1. Package - the Package Management Class

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. Instantiation

pre. $foo = new Package(Globals::$dbaccess, package_name);

where _package_name_ is the name of a package.

h2. Attributes

* package_name - string - Packaage Name in a subset of camel case. The name consists
of one or more words concatenated together. Each word must begin with a capital letter
and be followed by at least one lower case letter or digit. The regular expression
is '^[A-Z][a-zA-Z]+$'. [Note: this identical to ObjectInfo::OBJECT_NAME_REGX]
* package_dir - string - the Package Directory derived from the package name by converting
all of the upper case letters to lower case and prefixing the interior ones by an underscore.
Use IncludeUtilities::camel_to_words($package_name) to do this programatically.
* package_abs_path - string - absolute path to package_dir
* enabled - enum(N, Y) - defaults to N
* readme - text - the content of the packages README file. It may contain HTML elements and
will be sanitized to remove _script_ tags
* required_packages - string - comma separated list of package names which are required to be installed
and enabled for this packaage to function properly.
* required_objects - string - comma separated list of required objects which are not
in some package.

h2. Class Methods

* name_to_dir($package_name) - converts camel case to underscore separated by
replacing all (except the leading) upper case character by and underscore and
the lower case version. The leading upper case character is lower cased.
* dir_to_name($dir) - inverse of name_to_dir() - only works for package names and directories
* install($package) - locates _$package_ and installs it if it exists. Creates all tables
required for objects, creates a Package instance, and parses and _install.txt_.
Returns the Package instance. Otherwise, returns FALSE.

h2. Instance Methods

* load_package() - simply (uniquely) adds the package directory to the include path.

#end-doc
*/

// global variables
require_once('aclass.php');
require_once('ObjectInfo.php');

AClass::define_class('Package', 'package_name', 
  array( // field definitions
    array('package_name', 'varchar(255)', 'Package Number'),
    array('package_dir', 'varchar(255)', 'Package Directory'),
    array('system_or_site', 'enum(unknown,system_package,site_package)', 'System or Site Package'),
    array('package_abs_path', 'varchar(255)', 'Package Absolute Path'),
    array('enabled', 'enum(N,Y)', 'Enabled?'),
    array('readme', 'text', 'ReadMe File'),
    array('required_packages', 'text', 'Required Packages'),
    array('required_objects', 'text', 'Required Non-Package Objects'),
    array('objects', 'text', 'Package Objects'),
  ),
  array(// attribute definitions
    'package_name' => array('required', 'filter' => '([A-Z][a-z0-9]+)+'),
    'package_dir' => array('readonly', 'filter' => '[a-z][_a-z0-9]*'),
    'required_packages' => array('filter' => '\s*\w+(\s*,\s*\w+)*\s*'),
    'required_objects' => array('filter' => '\s*\w+(\s*,\s*\w+)*\s*'),
    'readme' => 'readonly',
  ));
// end global variables

// class definitions
class Package extends AnInstance {
  // static private $object_regx = '/^[A-Z][a-zA-Z]+$/';
  const OBJECT_FILE_REGX = '/^([A-Z][a-zA-Z]+)\.php$/';
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Package', $dbaccess, $attribute_values);
    if ($this->package_name && !$this->package_dir) {
      $this->package_dir = IncludeUtilities::camel_to_words($this->package_name);
      $this->package_abs_path = stream_resolve_include_path($this->package_dir);
      if (!$this->package_abs_path) {
        Globals::add_message("Package $this->package_name is not installed");
        $this->package_dir = '';
      } else {
        $ar = array();
        $this->system_or_site = preg_match('|^' . Globals::$system_packages . '|', $this->package_abs_path)
          ? 'system_package' : 'site_package';
        foreach (scandir($this->package_abs_path) as $fname) {
          if (preg_match(Package::OBJECT_FILE_REGX, $fname, $m)) {
            $ar[] = $m[1];
          }
        }
        $this->objects = implode(',', $ar);
      }
      $this->save();
    }
  } // end of __construct()
  
  // Static Fuctions
  public static function name_to_dir($name) {
    $ar = str_split($name);
    $dir = strtolower(array_shift($ar));
    foreach ($ar as $char) {
      $dir .= ctype_upper($char) ? '_' . strtolower($char) : $char;
    }
    return $dir;
  } // end of name_to_dir()
  
  public static function dir_to_name($dir) {
    $ar = array_map(create_function('$a', 'return ucfirst($a);'), explode('_', $dir));
    return implode('', $ar);
  } // end of dir_to_name()

  private function install_install_helper($install_data) {
    $tmp = new RequestRouter($this->dbaccess, $install_data);
    $tmp->save();
  } // end of install_install_helper()

  private function install_manage_helper($manage_data) {
    $object_list = preg_split('/\s*,\s*/', $manage_data['object_names']);
    if (!array_key_exists('resource_name', $manage_data)) {
      $manage_data['resource_name'] = "Manage {$manage_data['object_names']}";
    }
    unset($manage_data['object_names']);
    $router = new RequestRouter($this->dbaccess, $manage_data);
    $router->save();
    foreach ($object_list as $object_name) {
      $obj_info = new ObjectInfo($this->dbaccess, $object_name);
      $obj_info->manageable = 'Y';
      $obj_info->management_url = "/{$router->routing_key}" . ($router->path_map ? "/{$router->path_map}" : '');
      $obj_info->save();
    }
  } // end of install_manage_helper()

  public static function install($dbaccess, $package_name) {
    if (($path = stream_resolve_include_path(IncludeUtilities::camel_to_words($package_name) . DIRECTORY_SEPARATOR . 'install.php'))) {
      $tmp = include($path);
      if ($tmp === FALSE) {
        return FALSE;
      }
      
      // creating the Package object will set attributes: package_dir and objects.
      $package_obj = new Package($dbaccess, $package_name);
      $package_obj->required_packages = $required_packages;
      $package_obj->required_objects = $required_objects;

      require_once('RequestRouter.php');
      require_once('ObjectInfo.php');

      if (($path = stream_resolve_include_path($package_obj->package_dir . DIRECTORY_SEPARATOR . 'README.txt'))) {
        $package_obj->readme = file_get_contents($path);
      } else {
        $package_obj->readme = "No README.txt File - contact maintainer and scold.";
      }
      $package_obj->save();
      
      foreach (explode(',', $package_obj->objects) as $object_name) {
        $filename = "{$object_name}.php";
        $file_path = stream_resolve_include_path($package_obj->package_dir . DIRECTORY_SEPARATOR . $filename);
        require_once($file_path);

        // create table - if required
        $class_instance = AClass::get_class_instance($object_name);
        if (!$dbaccess->table_exists($class_instance->tablename)) {
          $class_instance->create_table($dbaccess);
        }
        
        // create ObjectInfo record
        $obj_info = new ObjectInfo($dbaccess, array('object_name' => $object_name, 'valid' => 'Y',
          'path' => $file_path, 'source' => $package_obj->system_or_site, 'source_name' => $package_name));
      }
      
      // process install_data
      if (is_array($install_data)) {
        if (array_key_exists(0, $install_data)) {
          foreach ($install_data as $tmp) {
            $package_obj->install_install_helper($tmp);
          }
        } else {
          $package_obj->install_install_helper($install_data);
        }
      }

      // process $management array, if present
      if (is_array($management)) {
        if (array_key_exists(0, $management)) {
          foreach ($management as $mng_tmp) {
            $package_obj->install_manage_helper($mng_tmp);
          }
        } else {
          $package_obj->install_manage_helper($management);
        }
      } else {
        $management = FALSE;  // just to be sure nobody messed up.
      }
      
      return $package_obj;
    } else {
      return FALSE;
    }
  } // end of install()
  
  // instance methods
  public function load_package() {
    if (!preg_match('/' . DIRECTORY_SEPARATOR . $this->package_dir . PATH_SEPARATOR . '/', ($path = get_include_path()))
        && !preg_match('/' . DIRECTORY_SEPARATOR . $this->package_dir . '$/', $path)) {
      set_include_path(Globals::$packages_root . DIRECTORY_SEPARATOR . $this->package_dir
          . PATH_SEPARATOR . Globals::$system_packages . DIRECTORY_SEPARATOR . $this->package_dir
          . PATH_SEPARATOR . get_include_path());
      }
  } // end of load_package()
}


class PackageManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Package', 'package_name');
  } // end of __construct()
}
?>
