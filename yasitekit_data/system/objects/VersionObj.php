<?php
/*
#doc-start
h1.  VersionObj.php - VersionObj supports version management for YASiteKit sites - with support for concurrent access

Created by  on 2010-06-17.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved. Licensed under the terms of the GNU Lesser
GNUL License, version 3.  See http://www.gnu.org/licenses/ for details.

bq. THIS SOFTWARE HAS NO WARRANTEE OR REPRESENTATION FOR FITNESS OF PURPOSE.
USE AT YOUR OWN RISK.

The VersionObj class encapsulates a versioning method used to track changes
in YASiteKit models. The method attempts to avoid version errors by restricting
the actions which can be applied to a version instance.

First of all, versions are of two types: master and dev (for development).
All versions carry two version numbers: _master_version_ and _dev_version_.
Increment rules are: _master_ instances only increment _master_version_,
wherease _development_ versions increment _dev_version_. The idea here is that
when a copy of the _master_ is checked out, the _dev_version_ will indicate the
changes to Persistent objects in the Development version and the _master_version_
stays constant to show where it started from. Occational 'hg updates' will bring
in new _master_version_ numbers, so that the versioning in the development version
can be made to track along the master repository.

Further, version instances are stored in the site database table - in the
table named '_version' and in the '_version' file of each archive which is
created.

The Version numbers - _master_version_ and _dev_version_ - are read-only
attributes. They must be incremented using the _inc()_ method. The _inc()_
method will also automatically save the version object if it is was obtained
from the database. If it was obtained from a file, then _inc()_ will neither
update the version number nor save the instance.

Version numbers are simple, monotonically increasing integers - beginning
with 1 (except that _dev_version_ is 0 in a _master_ instance).

* "Instantiation":#instantiation
* "Attributes":#attributes
* "Class Methods":#class_methods
* "Instance Methods":#instance_methods


h2(#instantiation). Instantiation

Cannot be created using the *new* operator. Use one of the "class methods":#class_methods
below.

h2(#attributes). Attributes

Attribute access is controlled via PHP Magic methods. In short, all attributes
are read-only and can only be initialized (which happens automatcally).
_master_version_ and _dev_version_ may only be incremented using the
_inc()_ method. Nothing can be unset and only these attributes can be tested with isset().

* master - boolean - TRUE if this is the one and only master version. Else FALSE
* owner - string - a userid satisfying [a-z][_a-z0-9]* which is normally the userid
of some human responsible for this versioning instance. If _master_ is TRUE, then
it may make sense for _owner_ to be the _site_id_ ("see config.php":/doc.d/config.html#site_info
for details).
* master_version - int - master version number - montonically increasing beginning at 1.
This is the number incremented if _master_ is TRUE.
* dev_version - int - this is a development version number. In a _master_ instance, it will
always be zero (0). In a _development_ version it will also a monotonically
increasing integer beginning with 1. This is the number incremented if _master_ is FALSE.
* save_param - one of FALSE, a file path (string), or a DBAccess object


h2(#class_methods). Class Methods

Instantiation Methods:

* initialize_versioning($master, $owner, $master_version = 1, $dev_version = 1) - returns a shiney new
initialized from thin air VersionObj object OR throws an exception. The _dev_version_
attribute is set to 0 if _$master_ is TRUE, else 1. This method is designed to use
to initialize a the versioning information for a master or development site.
* get_from_db($dbaccess) - returns a VersionObj instance using data in the
database table '_versioning'. This method saves _$dbaccess_ in the object so that
_inc()_ knows to both increment something and write it to the database.
* get_from_file($path) - reads and parses the versioning file at _$path_ and
returns a VersionObj instance or throws a VersionObjException exception.
This method saves the version path in the VersionObj instance so that _inc()_
knows to ignore attempts to change the version number or overwrite the file.
See "File Format":#file_format for to understand the contents and format of VersionObj files.

Other Class Methods:

* versioning_path($dir_path) - returns the name of a VersionObj file in or to create in
_$dir_path_ OR FALSE if _$dir_path_ is not a directory. Does NOT check for writability
or existence of versioning file.
* new_version_obj_form($action, $submit_val, $save_to) - returns a string containing a form which can be used
to create a new VersionObj and save it in either a database or a file. _$save_to_ must be
either 'file' or 'db'. _$submit_val_ is the string which will be submitted with the form
under the name 'submit'.
* process_ver_obj_form($rc, $dbaccess) - processes the results of submitting a versioning_obj_form.
_$rc_ is a "RequestCleaner":/doc.d/system-includes/request_cleaner.html. _$dbaccess_
is a "DBAccess instance":/doc.d/system-includes/dbaccess.html. The object will be saved
according to the instructions in the form.

h2(#instance_methods). Instance Methods

* inc() - if _save_param_ is a DBAccess instance, then this method
adds 1 to either the _master_version_ number or _dev_version_, depending on
the value _master_ AND writes the updated object to the database. Otherwise, it silently
returns.
* write(save_param, comment = FALSE) - writes the VersionObj object to a storage medium.
If the _save_param_ attribute of the instance is NULL, then it is set to the _save_param_
argument. This value then determines how _inc()_ works from then on. See Correct Way . . . below.
_save_param_ can be either a path to a file or a DBAccess object. _comment_ is a comment
string which will be written at the head of the file IF _save_param_ is a file path. _comment_
is ignored for DBAccess storage. Throws lots of VersionObjException's.

h2(#correct_usage). The Correct Way to Do Things

The correct way to initialize versioning for a site - either development or database -
is to create a fresh VersionObj using _initialize_versioning()_ and then _immediately_
write it to the database.

The correct way to increment a version after finding a model mismatch is:

# update the archive - this will update the data without touching the old _AClass_
definitions. This will write the current VersionObj in the database to the archive
# inc() the current VersionObj - this will increment the version number and write through
to the database
# backup the archive and flush the archive directory
# Create a new archive - which will write the updated version number to the new archive

h2(#data_storage). VersionObj Data Storage

VersionObj information can be saved and retrieved from a database or file.

h3(#database_format). Database Format

Data is saved in a database in the table '_versioning', which has four fields:

* master - char(1) - either T or F
* owner - char(40) - note the hard limit on the length of owner userid's
* master_version - int
* dev_version - int

The _owner_ field is the key field, hence version's are uniquely specified by owner.

h3(#file_format). VersionObj File Format

A VersionObj file is a text file containing several lines of data.
Each line is a variable definition, a comment, or blank.

* comments begin with the hash mark (#) (optionally preceeded by white space)
and extend to the end of the line
* blank lines consist entirely of white space characters
* variable definitions consist of a variable name followed by an equal sign (=)
followed by the definition. Both variable names and definitions are single tokens
with no embedded blanks or punctuation. Only the following variable names are recognized
(possible values are given as regular expressions):
** master - value satisfies [TF]
** owner - value satisfies [a-z][_a-z0-9]*
** master_version - value satisfies \d+
** dev_version - value satisfies \d+

Anything else is illegal.

#end-doc
*/

// global variables

// end global variables

// class definitions

class VersionObjException extends Exception {}

class VersionObj {
  const TABLENAME = '_versioning';
  private $master = FALSE;
  private $owner = NULL;
  private $master_version = 0;
  private $dev_version = 0;
  private $save_param = NULL;
  
  private function __construct($save_param = NULL, $master = FALSE, $owner = FALSE,
      $master_version = 0, $dev_version = 0)
  {
    $this->save_param = $save_param;
    $this->master = $master;
    $this->owner = $owner;
    $this->master_version = $master_version;
    $this->dev_version = $dev_version;
  } // end of __construct()
  
  // magic methods
  
  public function __toString()
  {
    return ($this->master ? 'master' : 'dev') . "-{$this->owner}-{$this->master_version}-{$this->dev_version}";
  } // end of __toString()
  
  public function __get($name)
  {
    switch ($name) {
      case 'master':
      case 'owner':
      case 'master_version':
      case 'dev_version':
      case 'save_param':
        return $this->$name;
      default:
        throw new VersionObjException("VersionObj::__get($name):illegal attribute name '$name'");
    }
  } // end of __get()
  
  public function __set($name, $val)
  {
    switch ($name) {
      case 'master':
      case 'owner':
      case 'master_version':
      case 'dev_version':
      case 'save_param':
        throw new VersionObjException("VersionObj::__set($name): attempt to set controlled access attribute: use instance metods");
      default:
        throw new VersionObjException("VersionObj::__set($name):illegal attribute name '$name'");
    }
  } // end of __set()
  
  public function __isset($name)
  {
    switch ($name) {
      case 'master':
      case 'owner':
      case 'master_version':
      case 'dev_version':
        return TRUE;
      case 'save_param':
        return $this->save_param !== FALSE;
      default:
        throw new VersionObjException("VersionObj::__isset($name):illegal attribute name '$name'");
    }
  } // end of __isset()
  
  public function __unset($name)
  {
    throw new VersionObjException("VersionObj::__unset($name):illegal attempt to unset attribute '$name'");
  } // end of __unset()

  // static methods
  
  public static function initialize_versioning($master, $owner, $master_version = 1, $dev_version = FALSE)
  {
    if (!is_bool($master)) {
      throw new VersionObjException("VersionObj::initialize_versioning(master, owner): 'master' is not a Boolean");
    }
    if (!preg_match('/^[a-z][_a-z0-9]{1,39}$/', $owner)) {
      throw new VersionObjException("VersionObj::initialize_versioning(master, owner): Illegal owner '$owner': does not satisfy [a-z][_a-z0-9]{1,39}");
    }
    if (!is_int($master_version) || $master_version <= 0) {
      throw new VersionObjException("VersionObj::initialize_versioning(master, owner, master_version): Illegal master_version: must be >= 1: '$master_version'");
    }
    if ($dev_version === FALSE) {
      $dev_version = $master ? 0 : 1;
    } elseif (!is_int($dev_version) || $dev_version < 0) {
      throw new VersionObjException("VersionObj::initialize_versioning(master, owner, master_version, dev_version): Illegal dev_version: must be >= 0: '$dev_version'");
    }
    return new VersionObj(NULL, $master, $owner, $master_version, $master ? 0 : $dev_version);
  } // end of initialize_versioning()
  
  public static function get_from_db($dbaccess)
  {
    require_once('dbaccess.php');
    if (!($dbaccess instanceof DBAccess)) {
      throw new VersionObjException("VersionObj::get_from_db(dbaccess): dbaccess is not an instance of DBAccess");
    }
    $tmp = $dbaccess->select_from_table(VersionObj::TABLENAME);
    if ($tmp) {
      $tmp = $tmp[0];
      return new VersionObj($dbaccess, $tmp['master'] == 'T' ? TRUE : FALSE, $tmp['owner'],
        $tmp['master_version'], $tmp['dev_version']);
    } else {
      throw new VersionObjException("VersionObj::get_from_db($dbaccess): version object not defined");
    }
  } // end of get_from_db()
  
  public static function get_from_file($path)
  {
    if (!file_exists($path)) {
      throw new VersionObjException("VersionObj::get_from_file($path): file does not exist");
    } elseif (is_file($path)) {
      if ($path != VersionObj::versioning_path(dirname($path))) {
        throw new VersionObjException("VersionObj::get_from_file($path):Bad file name");
      } 
    } elseif (is_dir($path)) {
      $path = VersionObj::versioning_path($path);
    } else {
      throw new VersionObjException("VersionObj::get_from_file($path): path is not a file");
    }
    if (!is_readable($path)) {
      throw new VersionObjException("VersionObj::get_from_file($path): path not readable");
    }
    $str = file_get_contents($path);
    $lines = preg_split('/[\r\n]+/', $str);
    $line_no = 1;
    foreach ($lines as $line) {
      // discard blanks and comments
      if (preg_match('/^\s*$/', $line) || preg_match('/^\s*#/', $line)) {
        continue;
      } elseif (preg_match('/\s*master\s*=\s*([TF])\s*$/', $line, $match_obj)) {
        $master = $match_obj[1] == 'T';
      } elseif (preg_match('/\s*owner\s*=\s*([a-z][_a-z0-9]{1,39})\s*$/', $line, $match_obj)) {
        $owner = $match_obj[1];
      } elseif (preg_match('/\s*master_version\s*=\s*(\d+)\s*$/', $line, $match_obj)) {
        $master_version = intval($match_obj[1]);
      } elseif (preg_match('/\s*dev_version\s*=\s*(\d+)\s*$/', $line, $match_obj)) {
        $dev_version = intval($match_obj[1]);
      } else {
        throw new VersionObjException("VersionObj::get_from_file($path): Illegal content at line $line_no: '$line'");
      }
      $line_no += 1;
    }
    foreach (array('master', 'owner', 'master_version', 'dev_version') as $vname) {
      if (!isset($$vname)) {
        throw new VersionObjException("VersionObj::get_from_file($path): Definition for $vname Missing");
      }
    }
    return new VersionObj($path, $master, $owner, $master_version, $dev_version);
  } // end of get_from_file()
  
  public static function versioning_path($dir)
  {
    return is_dir($dir) ? $dir . DIRECTORY_SEPARATOR . VersionObj::TABLENAME : FALSE;
    
  } // end of versioning_path()
  
  public static function new_version_obj_form($action, $submit_val, $save_to)
  {
    $str = "<form action=\"$action\" method=\"post\" accept-charset=\"utf-8\">\n";
    $str .= " <ul>\n";

    $str .= "<li>\n";
    $str .= "Master <input type=\"radio\" name=\"vo_master\" value=\"T\" checked>\n";
    $str .= "<span style=\"text-decoration:line-through\">Master</span> <input type=\"radio\" name=\"vo_master\" value=\"F\">\n";
    $str .= "</li>\n";
    $str .= "<li>Owner: <input type=\"text\" name=\"vo_owner\" value=\"" . Globals::$site_id . "\"></li>\n";
    $str .= "<li>Master Version Number: <input type=\"text\" name=\"vo_master_vers\" value=\"1\"></li>\n";
    $str .= "<li>Dev Version Number: <input type=\"text\" name=\"vo_dev_vers\" value=\"1\"></li>\n";
    switch ($save_to) {
      case 'file':
        $str .= "<input type=\"hidden\" name=\"vo_save_to\" value=\"file\">\n";
        $str .= "<li id=\"vo_path\">if Safe To File: File Path: <input type=\"text\" name=\"vo_path\" value=\""
            . VersionObj::versioning_path(Globals::$dump_dir)
            . "\"></li>\n";
        break;
      case 'db':
        $str .= "<li><input type=\"hidden\" name=\"vo_save_to\" value=\"db\"></li>\n";
        break;
      default:
        throw new VersionException("VersionObj::new_version_obj_form($action, $save_to): illegal save_to - must be file or db");
    }
    $str .= "<li><input type=\"submit\" name=\"submit\" value=\"$submit_val\"></li>\n";

    $str .= " </ul>\n";
    $str .= "</form>\n";
    return $str;
  } // end of new_version_obj_form()
  
  public static function process_ver_obj_form($rc, $dbaccess)
  {
    $versioning_obj = VersionObj::initialize_versioning($rc->safe_post_vo_master == 'T' ? TRUE : FALSE,
      $rc->safe_post_vo_owner, intval($rc->safe_post_vo_master_vers), intval($rc->safe_post_vo_dev_vers));
    // echo $versioning_obj->dump('process_ver_obj_form()');
    switch ($rc->safe_post_vo_save_to) {
      case 'file':
        $versioning_obj->write($rc->safe_post_vo_path);
        break;
      case 'db':
        $versioning_obj->write($dbaccess);
        break;
      default:
        throw new VersionObjException("VersionObj::process_ver_obj_form(): Illegal vo_save_to: $rc->safe_post_vo_save_to");
    }
  } // end of process_ver_obj_form()

  // instance methods

  public function inc()
  {
    // refuse to work unless this is associated with the database
    if (!($this->save_param instanceof DBAccess)) {
      return;
    }
    if ($this->master) {
      $this->master_version += 1;
    } else {
      $this->dev_version += 1;
    }
    
    $now = new DateTime('now');
    $this->write($this->save_param, "New Version Created at " . $now->format('c'));
  } // end of inc()
  
  public function write($save_param = FALSE, $comment = FALSE)
  {
    if ($save_param === FALSE) {
      if ($this->save_param) {
        $save_param = $this->save_param;
      } else {
        throw new VersionObjException("VersionObj::write(): no save_param specified in call or object instance");
      }
    } 
    if (is_string($save_param)) {
      $path = $save_param;
      if (is_file($path)) {
        if ($path != VersionObj::versioning_path(dirname($path))) {
          throw new VersionObjException("VersionObj::write($save_param): Illegal Save Path - bad file name");
        }
        // this is OK, so it falls out of the nested if's
      } elseif (is_dir($path) || is_dir($path . DIRECTORY_SEPARATOR)) {  // is a directory
        $path = VersionObj::versioning_path($path);
      } elseif (file_exists($path)) {
        throw new VersionObjException("VersionObj::write($save_param): Illegal Save Path - neither file nor directory");
      }
      $dir = dirname($path);
      if (!is_writable($dir)) {
        throw new VersionObjException("VersionObj::write($path): directory $dir is not writable");
      }
      if (file_exists($path) && !is_writable($path)) {
        throw new VersionObjException("VersionObj::write($path): file {basename($path)} exists and is not writable");
      }
      $str = $comment ? "# $comment\n" : '';
      $str .= "master = " . ($this->master ? 'T' : 'N') . "\n";
      $str .= "owner = {$this->owner}\n";
      $str .= "master_version = {$this->master_version}\n";
      $str .= "dev_version = {$this->dev_version}\n";
      if (file_put_contents($path, $str) === FALSE) {
        throw new VersionObjException("VersionObj::write($path): write to file failed");
      }
    } elseif ($save_param instanceof DBAccess) {
      $dbaccess = $save_param;
      if (!$dbaccess->table_exists(VersionObj::TABLENAME)) {
        $dbaccess->create_table(VersionObj::TABLENAME, array(array('master','char(1)'),
          array('owner', 'char(40)', TRUE), array('master_version', 'int'), array('dev_version', 'int')));
      }
      $dbaccess->update_table(VersionObj::TABLENAME, array('master' => $this->master ? 'T' : 'F',
            'master_version' => $this->master_version,
            'dev_version' => $this->dev_version),
          array('owner' => $this->owner));
      if ($dbaccess->changes() == 0) {
        $dbaccess->insert_into_table(VersionObj::TABLENAME, array('master' => $this->master ? 'T' : 'F',
          'owner' => $this->owner,
          'master_version' => $this->master_version,
          'dev_version' => $this->dev_version));
      }
    } else {
      throw new VersionObjException("VersionObj::write(save_param, comment): save_param neither file path nor DBAccess");
    }
    if (!$this->save_param) {
      $this->save_param = $save_param;
    }
  } // end of write()

  public function dump($msg = '')
  {
    $str = "VersionObj($this):\n" . ($msg ? $msg . "\n" : '');
    $str .= "  save_param: $this->save_param\n";
    return $str;
  } // end of dump()
}

// end class definitions
?>
