<?php
/*
#doc-start
h1. installerator.php - The YASiteKit Installation Script Utility

bq. (c) Copyright 2010 Mike Howard. All Rights Reserved. 
  Licensed for use under the GNU Lesser GNU Public License, version 3
  See http://www.gnu.org/licenses/lpgl.html for details

The *Installerator* is the companion script to the
"*Configurator*":/doc.d/system-installation/configurator.html.
It must be run _after_ the *configurator*, because it reads and uses the
_config.php_ files the *configurator* produces.

NOTE: this means it _must_ be re-run each time a configuration file is modified -
either by the *configuration* or by hand.

The *installerator* creates several files in the _config-dir_ directory for
use in installing and re-installing versions of a site. All of these files
take their names from the template files in the system/installation directory.
[template files all have the _-template_ suffix, so they are easy to spot].
All created files have the _-template_ suffix replaced by _-development_,
_-alpha_, or _-production_.

The files created are:

* htaccess-... - the *.htaccess* file which must be used in the installation.
* index.php-... - the _index.php_ file which goes in _document_root_
* make_tarfiles.sh-development - this is a Unix/Linux/Mac OSX compatable shell script
which will create the distribution _tar_ files. It expects to use the _maketar_
python script found in 'Useful utilities' on the YASiteKit site 
"download":http://www.yasitekit.org/downloads/msh-utilities-1.0.0.zip page.
* remote_install.sh-... - a UNIX/LINUX/Mac OSX shell script which runs on a remote
host and installs the site.
* upload_script.sh-... - a UNIX/LINUX/Mac OSX shell script which runs locally. It
uses _scp_ and _ssh_ to copy the tar archive files to the remote site and then
remotely execute the _remote_install.sh_ script.
* vhost-... - the Apache Virtual Hosts entry you will need for the site. This
needs to go into the configuration file.

#doc-end
*/

// global definitions

// end global definitions

// class definitions
class ConfigValues {
  private $variables = array();
  public $name;
  public $valid;
  private $path;
  private $var_name_ar = array(
    'site_id',
    'document_root',
    'private_data_root',
    'system_root',
    'site_installation',
    'webmaster',
    );
  
  
  public function __construct($name)
  {
    static $regx = '/(public\s+static\s+|static\s+public\s+)\$(\w+)\s*=\s*(.*);\s*(\/\/.*)?/';
    $this->name = $name;
    $this->path = "config-dir" . DIRECTORY_SEPARATOR . 'config.php-' . $name;
    $this->valid = FALSE;
    if (!file_exists($this->path)) {
      return;
    }
    $str = file_get_contents($this->path);
    if ($str === FALSE) {
      return;
    }
    $lines = preg_split('/\s*[\r\n]+\s*/', $str);
    $line_no = 0;
    foreach ($lines as $line) {
      $line_no += 1;
      if (preg_match($regx, $line, $match_obj)) {
        $var_name = $match_obj[2];
        if (in_array($var_name, $this->var_name_ar)) {
          if (preg_match('/(([\'"])([^\'"]*)\2)|(\d+)|(TRUE|FALSE|NULL)/', $match_obj[3], $m)) {
            if (count($m) >= 5 && $m[4]) {
              $var_value = intval($m[4]);
              $var_type = 'int';
            } elseif (count($m) >= 6 && $m[5]) {
              switch ($m[5]) {
                case 'TRUE': $var_type = 'bool'; $var_value = TRUE; break;
                case 'FALSE': $var_type = 'bool'; $var_value = FALSE; break;
                case 'NULL': $var_type = 'string'; $var_value = NULL; break;
                default: throw new Exception("get_config_values($this->path):Internal Error");
              }
            } elseif ($m[3]) {
                $var_value = $m[3];
                $var_type = 'string';
            } else {
              throw new Exception("get_config_values($this->path):Parse Error $line_no");
            }
          } else {
            var_dump("Second preg_match failed: $line");
            continue;
          }
          // put it away
          $this->variables[$var_name] = array($var_type, $var_value);
        }
      }
    }
    $this->valid = TRUE;
  } // end of __construct()
  
  public function __toString()
  {
    return $this->name;
  } // end of __toString()
  
  public function __get($name)
  {
    if (in_array($name, array_keys($this->variables))) {
      return $this->variables[$name][1];
    } elseif (in_array($name, $this->variable_names)) {
      return NULL;
    } else {
      throw new Exception("ConfigValues::__get($name): Undefined variable: $name"); 
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    if (in_array($name, $this->variables) && $this->variables[$name][0] != 'param')
      throw new Exception("ConfigValues::__set($name, $value): Illegal attempt to set read only variable");
    else
      $this->variables[$name] = array('param', $value);
  } // end of __set()
  
  public function __isset($name)
  {
    return isset($this->variables[$name]);
  } // end of __isset()
  
  public function variable_names()
  {
    return $this->var_name_ar;
  } // end of variable_names()
  
  public function emptyP()
  {
    return !$this->valid || count($this->variables) == 0;
  } // end of emptyP()
  
  public function __unset($name)
  {
    if (in_array($name, $this->variables))
      throw new Exception("ConfigValues::__unset($name): Illegal attempt to unset variable $name");
  } // end of __unset()
  
  public function as_table_rows()
  {
    foreach ($this->variables as $key => $ar) {
      switch ($ar[0]) {
        case 'string':
        case 'int':
          $val = $ar[1];
          break;
        case 'bool':
          $val = $ar[1] ? 'TRUE' : 'FALSE';
          break;
      }
      $str .= "<tr>\n" . "<th>$key ({$ar[0]})</th><td>{$val}</td>". "</tr>\n";
    }
    return $str;
  } // end of as_table_rows()
  
  public function parse()
  {
    # code...
  } // end of parse()

  public function dump($msg = '')
  {
    $str = $msg ? "$msg\n" : '';
    $str .= "ConfigValues($this->name)\n";
    foreach ($this->variables as $var_name => $ar) {
      $str .= "  $var_name ({$ar[0]}): {$ar[1]}\n";
    }
    return $str;
  } // end of dump()
}

class HostValues {
  private $variables = array();
  private $variable_names = NULL;
  private $variable_type = array(
    'host' => 'string',
    'userid' => 'string',
    'password' => 'string',
    'upload_req' => 'bool',
    'active' => 'bool',
    'server_doc_root' => 'string',
    'server_log_root' => 'string',
    'htaccess_text' => 'textarea',
    );
  private $read_only_var_names;
  public $name;
  public $valid;
  public $path;
  public $config_vars;
  private $needs_save = FALSE;
  private $machine = array( /* array(reg-expr, cur-state, next-state) */
      array('/^(\w+)="((\\\"[^"]*\\\"|[^"]*)*)"$/', 'assign-stmt', 'assign-stmt'),
      array('/^(\w+)="((\\\"[^"]*\\\"|[^"]*)*)$/', 'assign-stmt', 'assign-cont'),
      array('/^((\\\"[^"]*\\\"|[^"]*)*)"$/', 'assign-cont', 'assign-stmt'),
      array('/^(.*)$/', 'assign-cont', 'assign-cont'),
    );
  
  public function __construct($name)
  {
    $this->name = $name;
    $this->config_vars = new ConfigValues($name);
    $this->variable_names = array_keys($this->variable_type);
    $this->path = 'config-dir' . DIRECTORY_SEPARATOR . 'install-dict-' . $name;
    
    // check validity
    if (!($this->valid = $this->config_vars->valid)) {
      return;
    }
    
    if ($this->path) {
      $str = file_exists($this->path) ? file_get_contents($this->path) : FALSE;
      if ($str !== FALSE) {
        $lines = preg_split('/[\r\n]+/', $str);
        $state = 'assign-stmt';
        foreach ($lines as $line) {
          foreach ($this->machine as $ar) {
            list($regx, $this_state, $next_state) = $ar;
            if ($this_state == $state && preg_match($regx, $line, $match_obj)) {
              switch ($state) {
                case 'assign-stmt':
                  $var_name = $match_obj[1];
                  $var_value = $match_obj[2];
                  $this->variables[$var_name] = $var_value;
                  $state = $next_state;
                  continue 3;
                case 'assign-cont':
                  $this->variables[$var_name] .= "\n" . $match_obj[1];
                  $state = $next_state;
                  continue 3;
              }
            }
          }
          // if (preg_match('/^\s*(\w+)="(.*?)"$/', $line, $match_obj)) {
          //   $var_name = $match_obj[1];
          //   $var_value = $match_obj[2];
          //   $this->variables[$var_name] = $var_value;
          // }
        }
      }
    }
    foreach (array('host', 'userid', 'password', 'htaccess_text', 'upload_req', 'active', 'server_doc_root', 'server_log_root') as $var_name) {
      if (!isset($this->variables[$var_name])) {
        $this->variables[$var_name] = NULL;
      }
    }
    $this->variable_names = array_merge($this->variable_names, $this->config_vars->variable_names());
    foreach ($this->config_vars->variable_names() as $var_name) {
      $this->$var_name = $this->config_vars->$var_name;
    }
  } // end of __construct()
  
  public function __toString()
  {
    return $this->name;
  } // end of __toString()
  
  public function __get($name)
  {
    if (isset($this->variables[$name])) {
      return $this->variables[$name];
    } else {
      throw new Exception("HostValues::__get($name): Undefined Variable $name\n" . $this->dump());
    }
  } // end of __get()
  
  public function __set($name, $value)
  {
    if (in_array($name, $this->variable_names)) {
      $this->variables[$name] = $value;
    } else {
      throw new Exception("HostValues::__set($name, $value): Attempt to set illegal variable '$name'");
    }
  } // end of __set()
  
  public function __isset($name)
  {
    return isset($this->variables[$name]);
  } // end of __isset()
  
  public function __unset($name)
  {
    throw new Exception("HostValues::__unset($name): Illegal attempt to unset a variable");
  } // end of __unset()
  
  public function emptyP()
  {
    return $this->config_vars->emptyP();
  } // end of emptyP()

  public function save()
  {
    $str = '';
    foreach ($this->variables as $key => $value) {
      $str .= "$key=\"$value\"\n";
    }
    if (file_exists($this->path)) {
      $path_bak = $this->path . ".BAK";
      $path_bak_dash = $path_bak . '-';
      if (file_exists($path_bak)) {
        if (file_exists($path_bak_dash)) {
          unlink($path_bak_dash);
        }
        rename($path_bak, $path_bak_dash);
      }
      if (rename($this->path, $path_bak) === FALSE) {
        rename($path_bak_dash, $path_bak);
        return FALSE;
      }
    }
    return file_put_contents($this->path, $str) !== FALSE ? TRUE : FALSE;
  } // end of save()

  public function file_from_template_content($template)
  {
    $str = file_get_contents($template);
    if (!$str) {
      return FALSE;
    }
    $tokens = array('/\\"/');
    $repls = array('"');
    foreach ($this->variables as $key => $value) {
      $tokens[] = "/{{$key}}/";
      $repls[] = "$value";
    }
    // we do this twice so that we get one level of recursive substitution. If you want more,
    //  either duplicate the next line or do something less cheesy
    $str = preg_replace($tokens, $repls, $str);
    return preg_replace($tokens, $repls, $str);
  } // end of file_from_template_content()

  private function write_file_from_template_helper($template, $fname_prefix)
  {
    $str = $this->file_from_template_content($template);
    if ($str === FALSE) {
      return FALSE;
    }
    $path = 'config-dir' . DIRECTORY_SEPARATOR . $fname_prefix . "-{$this->site_installation}";
    if (file_exists($path)) {
      $path_bak = $path . ".BAK";
      $path_bak_dash = $path_bak . '-';
      if (file_exists($path_bak)) {
        if (file_exists($path_bak_dash)) {
          unlink($path_bak_dash);
        }
        rename($path_bak, $path_bak_dash);
      }
      if (rename($path, $path_bak) === FALSE) {
        rename($path_bak_dash, $path_bak);
        echo "<p class=\"error\">Update of $path FAILED</p>\n";
        return FALSE;
      }
    }
    if (file_put_contents($path, $str) !== FALSE) {
      echo "<p class=\"ok\">Update of $path Succeeded</p>\n";
      return TRUE;
    } else {
      echo "<p class=\"error\">Update of $path FAILED</p>\n";
      return FALSE;
    }
  } // end of write_file_from_template_helper()

  public function write_file_from_template(/* file list */)
  {
    $file_list = func_get_args();
    $ret = TRUE;
    foreach ($file_list as $fname) {
      // latch $ret to FALSE if either write fails
      $tmp = $this->write_file_from_template_helper("{$fname}-template", $fname);
      if ($ret && $tmp === FALSE)
        $ret = FALSE;
    }
    return $ret;
  } // end of write_file_from_template()
  
  public function form_elements()
  {
    $str = '';
    foreach ($this->variables as $key => $value) {
      $str .= "<tr><th>$key</th><td>";
      if (in_array($key, $this->config_vars->variable_names())) {
        $str .= $value;
      } else {
        switch ($this->variable_type[$key]) {
          case 'int':
            $str .= "<input type=\"text\" name=\"$key\" value=\"$value\" maxlength=\"10\" size=\"10\">";
            break;
          case 'textarea':
            $str .= "<textarea name=\"$key\" rows=\"10\" cols=\"60\">$value</textarea>";
            break;
          case 'bool':
            $y_checked = $value == 'Y' ? 'checked' : '';
            $n_checked = $value == 'Y' ? '' : 'checked';
            $str .= "<input type=\"radio\" name=\"$key\" value=\"Y\" $y_checked> Y | "
                .  "<input type=\"radio\" name=\"$key\" value=\"N\" $n_checked> N ";
            break;
          case 'string':
          default:
            $str .= "<input type=\"text\" name=\"$key\" value=\"$value\" maxlength=\"255\" size=\"60\">";
            break;
        }
      }
      $str .= "</td></tr>\n";
    }
    return $str;
  } // end of form_elements()
  
  public function display_as_text()
  {
    $str='';
    foreach ($this->variables as $key => $value) {
      $str .= "$key=\"$value\"\n";
    }
    return $str;
  } // end of display_as_text()
  
  public function parse()
  {
    $clean = array();
    foreach ($_POST as $key => $value) {
      $clean[$key] = preg_replace(array('/</', '/>/', '/"/'), array('&lt;', '&gt;', '\\"'), $value);
    }
    foreach ($this->variables as $key => $value) {
      if (in_array($key, $this->config_vars->variable_names())) {
        continue;
      }
      if ($clean[$key] != $value) {
        // $this->variables[$key] = $clean[$key];
        $this->variables[$key] = $_POST[$key];
        $this->needs_save = TRUE;
      }
    }
  } // end of parse()
  
  public function dump($msg = '')
  {
    $str = $msg ? "$msg\n" : '';
    $str .= "HostValues($this->name):\n";
    foreach ($this->variables as $key => $value) {
      $str .= "  $key => $value\n";
    }
    $str .= $this->config_vars->dump();
    return $str;
  } // end of dump()
}
// end class definitions

// function definitions

function even_odd()
{
  static $class = 'even';
  $class = $class == 'even' ? 'odd' : 'even';
  return $class;
} // end of even_odd()

// end function definitions

// doing stuff
foreach (array('development', 'alpha', 'production') as $inst_tmp) {
  $installations[$inst_tmp] = new HostValues($inst_tmp);
}

if (isset($_POST['submit'])) {
  switch ($_POST['submit']) {
    case 'Save development':
    case 'Save alpha':
    case 'Save production':
      list($tmp, $saved_installation) = preg_split('/\s+/', $_POST['submit']);
      $saved_host_vars = $installations[$saved_installation];
      if ($saved_host_vars->valid) {
        $saved_host_vars->parse();
        $save_result = $saved_host_vars->save();
      } else {
        $save_result = FALSE;
      }
      if ($save_result) {
        $save_text = "<div class=\"box ok vspace width-80p\">\n";
        $save_text .= "<p class=\"bold larger\">Save of $saved_installation Installation Info Succeeded</p>\n";
        if ($_POST['submit'] == 'Save development') {
          $save_text .= "<p class=\"bold larger box\">Remember to Run 'run_local_install.sh' script!!!!</p>\n";
        }
        ob_start();
        if ($saved_host_vars->site_installation == 'development') {
          $saved_host_vars->write_file_from_template('make_tarfiles.sh');
        }
        $saved_host_vars->write_file_from_template('index.php', 'htaccess', 'vhost');
        if ($saved_host_vars->upload_req == 'Y') {
          $saved_host_vars->write_file_from_template('remote_install.sh', 'upload_script.sh');
        } else {
          $saved_host_vars->write_file_from_template('local_install.sh');
        }
        $save_text .=  ob_get_clean() . "</div>\n";
      } else {
        $save_text = "<div class=\"box error vspace width-80p\">\n";
        $save_text .= "<p class=\"bold larger\">Save of $saved_installation Installation Info Failed</p>\n";
        $save_text .= "</div>\n";
      }
      break;
      // this section is problematic until we can find a convenient way to set up passwordless
      //  ssh login to the remote site.
    // case 'Upload development':
    // case 'Upload alpha':
    // case 'Upload production':
    //   list($tmp, $installation_to_upload) = preg_split('/\s+/', $_POST['submit']);
    //   // run config-dir/upload_script.sh-$installation_to_upload in config-dir
    //   ob_start();
    //   $result = system("cd config-dir; /bin/sh upload_script.sh-{$installation_to_upload}");
    //   echo $result ? "Upload Worked\n" : "Upload Failed\n";
    //   $upload_result = ob_get_clean();
    //   break;
    default:
      throw new Exception("installerator.php: Illegal submit value: " . $_POST['submit']);
  }
}
// end doing stuff

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <title>The Fabulous Installerator</title>
    <style>
      body { background:#ffffe0; padding:5px; margin-left: 2em;}
      li.boxed {
        padding:2px;
        padding-bottom:
        10px;border:1px solid #444444;
        margin-left:-2em;
        list-style: none inside;
      }
      th, td { text-align:left;}
      .width-80p { width:80%;}
      .box { padding:5px;border:1px black solid;}
      .vspace { margin-top:1em; margin-bottom:1em;}
      .center { text-alignt: center;}
      .float-left { float:left;}
      .float-right {float:right;}
      .clear {clear:both;}
      .even { background:#f0f0f0 !important;}
      .odd {background:#f8f8f8 !important;}
      .bold {font-weight:bold;}
      .italic { font-style:italic;}
      .smaller {font-size:small;}
      .larger {font-size:larger;}
      
      .info { background: #e0e0e0;}
      .advisory { background: #ffc080;}
      .ok { background: #a0ff80;}
      .error { background: #ff6000;}
      
      .development { background: #60ff80;}
      .alpha { background: #ffa000;}
      .production { background: #e0ffe0;}

      .pre { white-space:pre; background:#eeeeee;}
      
      #installerator-info { margin-left: 4em; margin-right: 4em;}
      #hidden-info { display:none;}
      #installerator-info:hover #hidden-info { display: block;}
    </style>
  </head>
  <body>
    <h1>YASiteKit Installerator</h1>

    <div class="vspace bold">
      Leave the Installerator and <a class="box" href="configurator.php">Go to the Configurator</a>
    </div>

    <div id="installerator-info" class="box vspace info">
      <span class="bold">???? What's an Installerator?</span> <span class="italic">(mouse over to see)</span>
      <div id="hidden-info">
        <p>
          The <span class="bold">Installerator</span> is <span class="italic">not</span> an installer. It's a PHP script which helps manage
          install scripts.
        </p>
        <p><span class="bold">Why?</span>
          YASiteKit is designed to enable staged development - where a site exists in three
          different installations. We call them <span class="italic">development, alpha,</span>
          and <span class="italic">production</span>. The idea is that you develop and break the
          site in the <span class="italic">installation</span>. When you like what you see,
          you install it as an <span class="italic">alpha</span> site and make sure it works.
          When you like the <span class="italic">alpha</span> version, then you can back up
          the <span class="italic">production</span> version, install the vetted
          <span class="italic">alpha</span> code as the <span class="italic">production</span>
          installation, and then rebuild the db - if necessary.
        </p>
        <p><span class="bold">How To Use This Thing:</span>
          The <span class="bold">Installerator</span> maintains files named
          <span class="italic">install-dict-&lt;installation&gt;</span> which contain
          Shell Variable definitions for:
        </p>
        <ul>
          <li><span class="bold">active</span> - Y for active. Anything else for not active</li>
          <li><span class="bold">host</span> - the host the installation lives on</li>
          <li><span class="bold">upload_req</span> - Y for yes, N for no. If Y, then we have to install by uploading. N means
            we don't have to - which usually means we are running on <span class="italic">this</span>
            host</li>
          <li><span class="bold">userid</span> - the admin/programmer userid needed to access the host and modify files</li>
          <li><span class="bold">password</span> - the password for the admin/programmer</li>
          <li>
            These items <span class="italic">only</span> apply to systems you are administering.
            They are used in creating the Virtual Hosts entry for the Apache Web Server
            <ul>
              <li><span class="bold">server_doc_root</span> - This is the File System path to the DocumentRoot directory
                used by the Apache web server. Typical values are: /usr/local/apache2/htdocs, /www/htdocs,
                /Library/WebServer/Documents, /opt/local/apache2/htdocs, . . .</li>
              <li><span class="bold">server_log_root</span> - This is the file system path to the directory where Apache writes
                it's log files. Typical values are: /usr/local/apache2/logs, /private/var/log/apache2,
                /opt/local/apache2/logs, . . .</li>
            </ul>
          </li>
          <li class="advisory">
            These items are copied from the config.php file for each installation. You must
            change them using the Configurator. After making any changes, come back to the
            Installerator and re-save the effected installation to incorporate the changes
            into the installation files.
            <ul>
              <li><span class="bold">document_root</span> - absolute file system path to site public data</li>
              <li><span class="bold">private_data_root</span> - absolute file system path to site private data</li>
              <li><span class="bold">system_root</span> - absolute file system path to YASiteKit system scripts</li>
              <li><span class="bold">site_id</span> used to name various files and things</li>
              <li><span class="bold">site_installation</span> One of
                  <span class="italic">development, alpha,</span> or
                  <span class="italic">production</span>.</li>
            </ul>
          </li>
        </ul>
        <p>The stuff you can edit has input elements. The stuff from <span class="italic">config.php</span>
          doesn't. There are no value checks - so be warned.</p>
        <p>Each of the <span class="bold">Save</span> buttons attempts to save the specified
          initialization dictionary and a couple of more files in <span class="italic">config-dir</span>.
          It does <span class="italic">not</span> save files for other site installation types.
          Also, it may not work.
          </p>
        <p><span class="bold">The Save May Not Work!</span> The save buttons will attempt to save,
          but this won't work if your local web server does not have write permission for both
          the local <span class="bold">config-dir</span> directory and the file it's trying to
          save. This can easily happen if you edit one of the files by hand or don't set things
          up correctly.</p>
        <p>When you click a save button, you'll see a boxed message just below here. If it's Green,
          then the save worked. If it's Orange/Red, it failed. (there are also some verbal hints, in
          case you can't see the colors).</p>
        <p><span class="bold">But no matter what happens </span> the <span class="bold">Installerator</span>
          displays a copy of each file it attempted to create at the bottom of the page. Just look down
          in the gray area.
        </p>
        <p><span class="bold">So all is Not Lost!</span> You can still
          recover using copy-and-paste. Just:</p>
        <ul>
          <li>view the page as text</li>
          <li>copy the file you want to save</li>
          <li>paste it into the file you want to save to using your text editor</li>
          <li>(optional) fix the permissions on the file so the Installerator works</li>
        </ul>
      </div>  <!-- end of hidden-info -->
    </div>

<?php if (isset($save_text)) {
  echo $save_text;
}
?>

    <div>
<?php
foreach (array('development', 'alpha', 'production') as $inst_tmp) {
  $host_vars_tmp = $installations[$inst_tmp];
// echo $host_vars_tmp->dump();
  if ($host_vars_tmp->emptyP()) {
    echo "<p class=\"box {$host_vars_tmp->name} width-80p\">$host_vars_tmp->name is Empty</p>\n";
  } else {
if (isset($installation_to_upload) && $installation_to_upload == $inst_tmp) {
  echo "<div class=\"box pre\">\n";
  echo "Result of Uploading $installation_to_upload\n";
  echo $upload_result;
  echo "</div>\n";
}
?>
  <form class="<?php echo $host_vars_tmp->name; ?> width-80p" action="installerator.php" method="post" accept-charset="utf-8">
    <input type="hidden" name="installation" value="<?php echo $host_vars_tmp->name; ?>">
    <table frame="box" rules="rows" width="100%">
    <tr><th colspan="2"><span class="larger">Installation Parameters for Installation <?php echo $host_vars_tmp->name; ?></span></th></tr>
<?php
      echo $host_vars_tmp->form_elements();
?>
    </table>
    <p>
      <input type="submit" name="submit" value="Save <?php echo $host_vars_tmp->name; ?>">
<?php // if ($host_vars_tmp->active == 'Y' && $host_vars_tmp->upload_req == 'Y'): ?>
      <!-- <input type="submit" name="submit" value="Upload <?php echo $host_vars_tmp->name; ?>"> -->
<?php // endif; ?>
    </p>
    </form>
  <br>
<?php
    }
  }
?>
    </div>
    
<?php
if (isset($saved_host_vars)) {
  echo "<div class=\"pre box " . even_odd() . "\">\n";
  echo "BEGINNING of install-dict-{$saved_host_vars->name}\n";
  echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "install-dict-{$saved_host_vars->name}");
  // echo $saved_host_vars->display_as_text();
  echo "END of install-dict-{$saved_host_vars->name}\n\n";
  echo "</div>\n";
  
  if ($saved_host_vars->site_installation == 'development') {
    echo "<div class=\"pre box " . even_odd() . "\">\n";
    echo "BEGINNING of make_tarfiles.sh-{$saved_host_vars->name}\n";
    echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "make_tarfiles.sh-{$saved_host_vars->name}");
    // echo $saved_host_vars->file_from_template_content('make_tarfiles.sh-template');
    echo "END of make_tarfiles.sh-{$saved_host_vars->name}\n\n";
    echo "</div>\n";
  }

  echo "<div class=\"pre box " . even_odd() . "\">\n";
  echo "BEGINNING of htaccess-{$saved_host_vars->name}\n";
  echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "htaccess-{$saved_host_vars->name}");
  // echo $saved_host_vars->file_from_template_content('htaccess-template');
  echo "END of htaccess-{$saved_host_vars->name}\n\n";
  echo "</div>\n";

  echo "<div class=\"pre box " . even_odd() . "\">\n";
  echo "BEGINNING of index.php-{$saved_host_vars->name}\n";
  echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "index.php-{$saved_host_vars->name}");
  // echo $saved_host_vars->file_from_template_content('index.php-template');
  echo "END of index.php-{$saved_host_vars->name}\n\n";
  echo "</div>\n";

  echo "<div class=\"pre box " . even_odd() . "\">\n";
  echo "BEGINNING of vhost-{$saved_host_vars->name}\n";
  echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "vhost-{$saved_host_vars->name}");
  // echo $saved_host_vars->file_from_template_content('vhost-template');
  echo "END of vhost-{$saved_host_vars->name}\n\n";
  echo "</div>\n";

  if ($saved_host_vars->upload_req == 'Y') {
    echo "<div class=\"pre box " . even_odd() . "\">\n";
    echo "BEGINNING of upload_script.sh-{$saved_host_vars->name}\n";
    echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "upload_script.sh-{$saved_host_vars->name}");
    // echo $saved_host_vars->file_from_template_content('upload_script.sh-template');
    echo "END of upload_script.sh-{$saved_host_vars->name}\n\n";
    echo "</div>\n";
    
    echo "<div class=\"pre box " . even_odd() . "\">\n";
    echo "BEGINNING of remote_install.sh-{$saved_host_vars->name}\n";
    echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "remote_install.sh-{$saved_host_vars->name}");
    // echo $saved_host_vars->file_from_template_content('remote_install.sh-template');
    echo "END of remote_install.sh-{$saved_host_vars->name}\n\n";
    echo "</div>\n";
  } else {
    echo "<div class=\"pre box " . even_odd() . "\">\n";
    echo "BEGINNING of local_install.sh-{$saved_host_vars->name}\n";
    echo file_get_contents("config-dir" . DIRECTORY_SEPARATOR . "local_install.sh-{$saved_host_vars->name}");
    // echo $saved_host_vars->file_from_template_content('local_install.sh-template');
    echo "END of local_install.sh-{$saved_host_vars->name}\n\n";
    echo "</div>\n";
  }
}
  ?>
    </div>
  </body>
</html>