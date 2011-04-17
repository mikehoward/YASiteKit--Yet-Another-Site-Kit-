<?php
/*
#doc-start
h1.  Parameters.php - permenent storage for class variables

Created by  on 2010-04-27.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*Parameters* provides persistent storage of per-AClass parameters.

h2. Instantiation

pre. $p_obj = new Parameters($dbaccess, $class_name);

where:

* dbaccess - DBAccess object - required
* class_name - string - required - is the class name the parameters belong to

h2. Attributes

All attributes belong to the class. Create them by writing:

pre. $p_obj->attribute = value;

Access them by using _$p_obj->attribute_.

Check for existence using *isset()* and remove using *unset()*.

The class is very conservative: each time a parameter is defined, modified, or unset,
the result is written to the database. It is essentially a write-through permanent
store.

h2. Class Methods

* php_create_string($dbaccess, $dump_dir) - dumps contents and table creations PHP code
into _$dump_dir_ / Parameters.dump

h2. Instance Methods

* attributes() - array of strings - returns array of attribute names

#end-doc
*/

// global variables
require_once('aclass.php');

// end global variables

class ParametersException extends Exception {}

class Parameters {
  const TABLENAME = '_parameters';
  private $dbaccess = NULL;
  private $cls = NULL;
  private $parameters = array();
  public function __construct($dbaccess, $cls)
  {
    $this->dbaccess = $dbaccess;
    if (!$this->dbaccess->table_exists(Parameters::TABLENAME)) {
      $this->dbaccess->create_table(Parameters::TABLENAME,
        array(
          array('cls', 'varchar(255)', TRUE),
          array('parameters', 'text', FALSE)));
    }
    $this->cls = $cls;
    $tmp = $this->dbaccess->select_from_table(Parameters::TABLENAME, 'parameters', array('cls' => $cls));
    if (count($tmp)) {
      $this->parameters = unserialize($tmp[0]['parameters']);
      if (!is_array($this->parameters)) {
        ob_start();
        echo "Unserialized parameters value: not an array\n";
        var_dump($this->parameters);
        debug_print_backtrace();
        echo ob_get_clean();
      }
    } else {
      $this->dbaccess->insert_into_table(Parameters::TABLENAME, array('cls' => $this->cls,
        'parameters' => serialize(array())));
      $this->parameters = array();
    }
  } // end of __construct()
  
  private function save()
  {
    $this->dbaccess->update_table(Parameters::TABLENAME, array('parameters' => serialize($this->parameters)),
      array('cls' => $this->cls));
  } // end of save()
  
  public function attributes()
  {
    $tmp = array_keys($this->parameters);
    natcasesort($tmp);
    return $tmp;
  } // end of attributes()

  public function __get($name)
  {
    if (array_key_exists($name, $this->parameters)) {
      return $this->parameters[$name];
    } else {
      ob_start() ; debug_print_backtrace();
      throw new ParametersException("Parameters::__get($name): undefined attribute '$name'\n" . ob_get_clean());
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    $this->parameters[$name] = $value;
    $this->save();
  } // end of __set()
  
  public function __isset($name)
  {
    return array_key_exists($name, $this->parameters);
  } // end of __isset()
  
  public function __unset($name)
  {
    unset($this->parameters[$name]);
    $this->save();
  } // end of __unset()
  
  public function dump($msg = NULL)
  {
    $str = "Parameters for {$this->cls}\n" . ($msg ? $msg . "\n" : '');
    $ar = $this->attributes();
    natcasesort($ar);
    foreach ($ar as $attr) {
      $str .= "  $attr: {$this->$attr}\n";
    }
    return $str;
  } // end of dump()
  
  public function php_create_string($dbaccess, $dump_dir)
  {
    if (!is_dir($dump_dir)) {
      if (!mkdir($dump_dir)) {
        echo "Skipping All AnInstance Data - cannot create directory $dump_dir\n";
        return FALSE;
      }
    }
    if (!is_writable($dump_dir)) {
        echo "Skipping All AnInstance Data - directory $dump_dir not writable \n";
      return FALSE;
    }
    
    $f = fopen($dump_dir . DIRECTORY_SEPARATOR . 'Parameters.dump', 'w');
    fwrite($f, "<?php\n");
    $tmp = $dbaccess->select_from_table(Parameters::TABLENAME);
    foreach ($tmp as $row) {
      $tmp = new Parameters($dbaccess, $row['cls']);
      fwrite($f, "\$tmp = new Parameters(\$dbaccess, '{$row['cls']}');\n");
      foreach ($tmp->attributes() as $attr) {
        fwrite($f, " \$tmp->$attr = unserialize(base64_decode('"
          . base64_encode(serialize($tmp->$attr)) . "'));\n");
      }
    }
    fwrite($f, "?>\n");
    fclose($f);
    return TRUE;
  } // end of php_create_string()
}

// end class definitions
?>
