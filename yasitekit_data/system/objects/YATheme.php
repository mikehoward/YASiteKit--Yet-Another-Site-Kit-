<?php
/*
#begin-doc
h2. YATheme.php - the YASiteKit Theme object

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 
Licensed under terms of LGPL Version 3. For details
go to http://www.gnu.org/licenses/lgpl.html

This documents the *YATheme* object. For details of writing theme files
see "YAThemeSpec":/doc.d/YAThemeSpec.html

h2(#yatheme). YATheme - the main YATheme interface

YATheme is the primary interface. None of the other classes
should be used directly.

YATheme is an AnInstance object, so it inherits all of the capabilities
of an object defineds as an AClass. See "aclass.php":/doc.d/system/includes/aclass.html
for details.

h3. Instantiation

pre. $foo = new YATheme($dbaccess, $file_name) -

where,

* $dbaccess - a DBAccess instance
* $file_name - is the name of a file which is on the include path.
_file_name_ should be an HTML, PHP, or YATheme template file.

h3. Attributes

All attributes are read-only

* dbaccess - DBAccess - access to the database
* file_name - string - the file name of the renderable file - which
must be located someplace on the PHP include path
* file_path - string - absolute path to file. Only valid if _file_exists_
is 'Y'. This is set when the file is parsed.
* rendered_content - string - the value of _path_ after rendering
it and all _included_ files. This is a mix of HTML with embedded
PHP as produced by YAThemeFile::render().
* file_exists - string - either 'Y' or 'N'. [A third value of
'Unknown' is used internally and should never be seen]
* refresh_timestamp - DateTime - timestamp of last refresh
* included_files - join - list of files included in this template. Used
for refreshing and invalidating the yatheme cache
* access_flag - string - one of YATheme::LOGIN_REQUIRED, YATheme::NOT_AUTHORIZED,
or YATheme::ACCESS_OK - which are string constants. Valid after instantiation.
* caching - string - One of _on, off_, and _compress_.
* required_authority - set - array of authority tokens or ANY or FALSE.

h3. Class Methods

* flush_entire_cache($dbaccess) - deletes all cache entries.
* invalidate_stale_cache_entries($dbaccess) - wanders through the cache table and discards
any cached entries which are out of date. Should be used as
an off-line process run as a cron job or on demand.

h3. Instance Methods

* flush_from_cache() - deletes _this_ from cache
* render() - returns the current cached value of _$this->file_name_. 

#end-doc
*/

require_once('aclass.php');
require_once('YAThemeFiles.php');

class YAThemeException extends Exception {}

AClass::define_class('YATheme', 'file_name',
  array(
    array('file_name', 'varchar(255)', 'File Name'),
    array('file_path', 'varchar(255)', 'Absolute Path to File'),
    array('file_exists', 'enum(Unknown,Y,N)', 'File Exists'),
    array('refresh_timestamp', 'datetime', 'Refresh Timestamp'),
    array('rendered_content', 'text', 'Rendered Content'),
    array('included_files', 'join(YAThemeFiles.file_name,multiple)', 'Required Files'),
    array('caching', 'enum(on,off,compress)', 'Caching Mode'),
    array('required_authority', 'set(ANY,C,A,M,V,S,X)', 'Required Authority'),
  ),
  array(
    'file_exists' => array('readonly', 'default' => 'Unknown'),
    'refresh_timestamp' => 'readonly',
    'rendered_content' => 'readonly',
    'included_files' => 'readonly',
    'caching' => array('default' => 'on'),
  ));

class YATheme extends AnInstance {
  const PUBLIC_OK = 'Public OK';
  const AUTHORITY_OK = 'Authority OK';
  const LOGIN_REQUIRED = 'Login Required';
  const NOT_AUTHORIZED = 'Insufficient Authorization';
  private $php_path = 'php';
  private $access_flag = NULL;

  public function __construct($dbaccess, $attr_values = NULL) {
    parent::__construct('YATheme', $dbaccess, $attr_values);

    // return if no file is specified
    if (!$this->file_name) {
      return;
    }
    
    // set the file exists flag to the actual state of the template
    // This implicitly invokes 'parse()' which verifies that the file is on the
    //  include path and also sets authority.
    if ($this->caching == 'off' || $this->file_exists != 'Y') {
      $this->rendered_content = $this->re_render();
    }

    // this makes sure everything is set properly
    // $this->render();
    // determine required authority for access. We compute the most restrictive
    // requirement from Globals::$router_obj and the page authority requrement
// echo $this->dump();
// echo Globals::$router_obj->dump();
    if (Globals::$router_obj instanceof RequestRouter) {
      if (Globals::$router_obj->required_authority) {
        $required_authority = Globals::$router_obj->required_authority;
      }
    } else {
      $required_authority = FALSE;
    }
    if ($this->required_authority) {
      $required_authority = $required_authority ? array_intersect($this->required_authority, $required_authority)
        : $this->required_authority;
    }

    if ($required_authority) {
      if (in_array('ANY', $required_authority)) {
        $this->access_flag = YATheme::PUBLIC_OK;
      } elseif (!Globals::$account_obj instanceof Account) {
        $this->access_flag = YATheme::LOGIN_REQUIRED; //'Login Required';
      } elseif (!Globals::$account_obj->has_authority($required_authority)) {
        $this->access_flag = YATheme::NOT_AUTHORIZED; //"Not Authorized";
      } elseif (!Globals::$account_obj->logged_in()) {
        $this->access_flag = YATheme::LOGIN_REQUIRED; //"Login Required";
      } else {
        $this->access_flag = YATheme::AUTHORITY_OK; //"Access OK";
      }
    } else {
      $this->access_flag = YATheme::PUBLIC_OK; //"Access OK";
    }
  } // end of __construct()
  

  public static function flush_entire_cache($dbaccess) {
    $class_instance = AClass::get_class_instance('YATheme');
    $dbaccess->delete_from_table($class_instance->tablename, NULL);
  } // end of flush_entire_cache()

  public static function invalidate_stale_cache_entries($dbaccess) {
    $obj = new YATheme($dbaccess);
    $list = $obj->get_objects_where(NULL);
    foreach ($list as $yatheme_tmp) {
      $refresh_timestamp = intval($yatheme_tmp->format('U'));
      foreach ($yatheme_tmp->included_files as $yafiles_obj) {
        if ($refresh_timestamp < $yafiles_obj->modify_timestamp->format('U')) {
          // invalidate by deleting cache entry
          $yatheme_tmp->delete();
          // jump to bottom of yatheme loop
          continue 2;
        }
      }
    }
  } // end of invalidate_stale_cache_entries()
  
  // Magic Methods
  public function __toString() {
    return "YATheme($this->file_name)";
  } // end of __toString()
  
  public function __get($name) {
    switch ($name) {
      case 'access_flag':
      case 'php_path':
        return $this->$name;
      default:
        return parent::__get($name);
    }
  } // end of __get()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'php_path':
        $this->$name = $value;
        break;
      case 'access_flag':
        throw new YAThemeException("YATheme::__set($name, value):attempt to set read-only attribute '$name'");
      case 'caching':
        if ($value == 'off') {
          $this->rendered_content = NULL;
          $this->re_render();
        }
        parent::__set($name, $value);
        break;
      default:
        parent::__set($name, $value);
        break;
    }
  } // end of __set()
  
  public function __isset($name) {
    switch ($name) {
      case 'access_flag':
      case 'php_path':
        return isset($this->control);
      default:
        return parent::__isset($name);
    }
  } // end of __isset()
  
  public function __unset($name) {
    switch ($name) {
      case 'access_flag':
      case 'php_path':
        throw new YAThemeException("YATheme::__unset($name): attempt to unset attribute '$name'");
      default:
        parent::__unset($name);
        break;
    }
  } // end of __unset()

  // Instance Methods
  // Private Methods
  private function re_render() {
    if (!$this->file_name) {
      $this->rendered_content = "<div class=\"yatheme-error\">No Content File Specified</div>\n";
    }

    require_once('athemer.php');
    require_once('yastream.php');

    $themer = new YAThemeParser($this->file_name);
    
    // check to see if we need to select the default template file or not. If we
    //   do, then overwrite the current value of $themer->template_file
    if (Globals::$default_template) {
      if (!$themer->template_file || $themer->template_file == 'default') {
        $themer->template_file = Globals::$default_template;
      } elseif (stream_resolve_include_path($this->template_file) === FALSE) {
        Globals::add_message("Unable to use template file: $this->template_file");
        $themer->template_file = Globals::$default_template;
      }
    } elseif ($themer->template_file
        && $themer->template_file != 'none'
        && stream_resolve_include_path($this->template_file) === FALSE) {
      Globals::add_message("Unable to resolve $this->template_file and no default template defined");
    }
    
    $this->rendered_content = $themer->render();
    if ($themer->parse_result) {
      $this->file_exists = 'Y';
      $this->file_path = $themer->file_path;
    } else {
      $this->file_exists = 'N';
    }

    switch ($this->caching) {
      case 'off':
        $this->mark_saved();
        if ($this->record_in_db) {
          $this->delete();
        }
        return $this->rendered_content;
      case 'on':
        break;
      case 'compress':
        $var_fname = "var://{$this->file_name}";
        file_put_contents($var_fname, $this->rendered_content);
        $this->rendered_content = php_strip_whitespace($var_fname);
        break;
    }

    $this->refresh_timestamp = new DateTime('now');

    // build file dependencies
    $ar = array();
    foreach ($themer->all_file_names as $tmp) {
      $ar[] = new YAThemeFiles($this->dbaccess, $tmp);
    }
    $this->included_files = $ar;
    
    // set required authority
    if ($themer->required_authority) {
      $this->required_authority = preg_split('/\s*,\s*/', $themer->required_authority);
    } else {
      $this->required_authority = '';
    }

    $this->save();

    return $this->rendered_content;
  } // end of re_render()

  // Public Instance Methods
  public function flush_from_cache() {
    $this->delete();
  } // end of flush_from_cache()

  public function render() {
    // if caching is 'off', then rendered_content is set to NULL on instantiation or changing
    //  caching value
    return $this->rendered_content ? $this->rendered_content : $this->re_render();
  } // end of render()
  
  public function save() {
    if ($this->caching == 'off') {
      $this->rendered_content = NULL;
    }
    parent::save();
  } // end of save()
  
  public function dump($msg = '') {
    $str = parent::dump($msg);
    $str .= "  access_flag: {$this->access_flag}\n";
    $str .= "  php_path: {$this->php_path}\n";
    return $str;
  } // end of dump()
}
