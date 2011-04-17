<?php
/*
#doc-start
h1.  ReloadDB.php - Admin Screen for dumping and rebuilding the database

Created by  on 2010-04-09.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*ReloadDB.php* manages archiving and unarchiving the database during
system upgrades or database engine changes, etc.

It is capable of

# creating an archive of the database in a database neutral format which
is suitable to rebuild the database using any engine supported by DBAccess.
# Reloading the infrastructure of the database. This consists of the structures
and values which never - or almost never - change between database modifications:
## session data - so sessions can continue accross database rebuilds
## data table definitions
## encryptor definitions and values used in last incarnation
of the database (necessary to read the data in encrypted fields)
## join table data - this feature is kind of semi-experimental. Not because
it doesn't work, but because it's not clear it is all that useful for web sites.
# Detecting changes to the definition of database objects. Creating a map function
which allows columns to be renamed and the data to be carried over. Detecting
and announcing changes in data types and assessing the risk involved.
# Reloading the data into the modified database.

This is an interactive program which requires Administrative access in order to
run.

WARNING: there are still some 'issues' which haven't been completely worked out.

h2. Access Control and States

The state of the site is managed by of four database global variables - which are implemented
as attributes of the DBAccess object. This is fully discussed in
"Database State":/doc.d/DatabaseState.html and summarized here

* on_line - T or F - T if the database can be accessed by regular applications. It is
momentarily set to F while archiving, rebuilding, and reloading
* database_valid - T or F - Set to F just before starting to rebuild the infrastructure.
Set to T after successfully reloading the data.
* archive_stale - T or F - T if the current archive is stale. This prohibits recreating
the infrastructure or reloading the database
* model_mismatch - F, T, or X - Must be F after rebuilding infrastructure. May be
F or T prior to rebuilding database structure. Database may not be modified if is X.

Only the following combinations of states are legal:

table{border:1px solid black;padding:5px}. _|{text-decoration:underline}. on_line|{text-decoration:underline}. database_valid|{text-decoration:underline}. archive_stale|{text-decoration:underline}. model_mismatch|{text-decoration:underline}. Allowed Actions|
=|T|T|T or F|F|use site;|
=
=|T|-|T|-|<. Create Archive|
=|T|T|F|F|<. Nothing|
=|F|F|F|F|<. Reload Data|
=|F|T|T|T|<. Rebuild Infrastructure, Create Map|
=|F|T|T|F|<. Rebuild Infrastructure|
=|F|T|F|T|<. Create Map|
=|F|T|F|F|<. Reload Data|
=|-|F|-|-|<. Nothing|

In other words:

* if database is NOT on line, then we can't do anything - so we return
* if the archive is _stale_ the only thing we can do is create an archive
* if the database is NOT stale, then
** if the database is valid, we can rebuild the infrastructure
** if the database is invalid
*** if a map is required and we don't have one - we can create one, but we can't
reload data
*** if a map is required and we have one - we can create one AND we can reload
data
*** if a map is NOT required, then we can reload data

h2(#functions). Functions

* render_site_state_form() - outputs the state of the site and presents
options to take the site off line or on line.
* render_archive_form() - outputs a form which presents the state of
the current archive. If it is stale, presents the choice to create a
new archive. Also presents the necessity of a map and legality of
the site. If a map is required, presents the form needed to create one.
If the map function is defined, presents a description of what it does
as well as the map creation form.
* render_rebuild_db_form() - presents the opportunity to rebuild the database,
if possible. Examines the state of the archive, necessity of a map function,
state of the map function, legality of the site, and online/offline mode of site.
If the stars are favorable, then there is a Rebuild Site button. If not, then
there are instructions detailing what must be done to expose the magic button.

#end-doc
*/

// global variables

Globals::$page_obj->page_header = Globals::$site_name . " -Dump and Reload";
Globals::$page_obj->page_title = Globals::$site_name . " - Dump and Reload Database";
Globals::$page_obj->required_authority = 'X';

// add semi-suckerfish display magic to map display and form
$page_head = Globals::$page_obj->get_by_name('head');
$my_style = new PageSegText('map_form_style');
$my_style->append("<style>#display-map-actions .display-target {display:none;} #display-map-actions:hover ul {display:block;}</style>");
$my_style->append("<style>#create-map-form .display-target {display:none;} #create-map-form:hover form {display:block;}</style>");
$page_head->append($my_style);

// these flags are initialized below at the start of processing or within functions
global $drop_first; // flag controls whether tables are dropped prior to reload or not
global $dump_dir; // Dump directory for this request
global $form_action;
$form_action = "ReloadDB.php";

require_once('aclass.php');
require_once('archive_functions.php');
require_once('VersionObj.php');
require_once('StateMgt.php');

// end global variables

// function definitions

// Form Rendering Functions
function render_site_state_form()
{
  global $form_action;
  
  switch (Globals::$dbaccess->on_line) {
    case 'F':
      $site_state = '<span class="advisory">Off Line</span>';
      if (StateMgt::legal_state_changeP('on_line', 'T')) {
        $change_form = "<form action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\">"
          . "Change Site State to Online: <input class=\"ok\" type=\"submit\" name=\"submit\" value=\"Go Online\">"
          . "</form>";
      } else {
        $change_form = "<p>No State change possible at this time:</p>"
          . "<ul>"
          . "  <li>Archive Stale:  " . Globals::$dbaccess->archive_stale . "</li>"
          . "  <li>Database Valid: " . Globals::$dbaccess->database_valid . "</li>"
          . "  <li>Model Mismatch: " . Globals::$dbaccess->model_mismatch . "</li>"
          . "</ul>";
      }
      break;
    case 'T':
      $site_state = '<span class="ok">On Line</span>';
      $change_form = "<form action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\">"
        . "Change Site State: <input class=\"warning\" type=\"submit\" name=\"submit\" value=\"Go Offline\">"
        . "</form>";
      break;
    case 'R':
      $site_state = '<span class="warning">Read Only</span>';
      $change_form = "<form action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\">"
        . "Change Site State: <input class=\"warning\" type=\"submit\" name=\"submit\" value=\"Go Offline\">"
        . "</form>";
      break;
    default:
      $site_state = 'Illegal Site State: [' . Globals::$dbaccess->on_line . ']';
      $change_form =  "<p>No State change possible at this time:</p>"
        . "<ul>"
        . "  <li>Archive Stale:  " . Globals::$dbaccess->archive_stale . "</li>"
        . "  <li>Database Valid: " . Globals::$dbaccess->database_valid . "</li>"
        . "  <li>Model Mismatch: " . Globals::$dbaccess->model_mismatch . "</li>"
        . "</ul>";
      break;
  }
?>
  <div id="site_state" class="box">
    <h2>Site Name: <?php echo Globals::$site_name; ?></h2>
    <p>Site is <span class="bold"><?php echo $site_state; ?></span></p>
    <?php echo $change_form; ?>
  </div>
<?php
}  // end of render_site_state_form()

function render_archive_form()
{
  global $form_action;
  global $dump_dir;
  
  // examine Archive Directory and Version Information
  $dump_dir_writable_str = dump_dir_writable($dump_dir);
  $dump_dir_ok_str = dump_dir_ok($dump_dir);
  if ($dump_dir_ok_str === TRUE) {
    $archive_state_str = Globals::$dbaccess->archive_stale == 'F'
      ? "<span class=\"ok\">Archive is Up to Date</span>" : "<span class=\"warning\">Archive Known to be Stale</span>";
    try {
      $archive_version_obj = VersionObj::get_from_file(VersionObj::versioning_path($dump_dir));
      $archive_version_obj_str = "$archive_version_obj";
    } catch (VersionObjException $e) {
      echo "Error reading versioning file for archive: $e\n";
      $archive_version_obj = FALSE;
      $archive_version_obj_str = $e->getMessage();
    }
  } else {
    $archive_version_obj = FALSE;
    $archive_version_obj_str = "No Archive";
    StateMgt::change_state_value('archive_stale', 'T');
    $archive_state_str = "<span class=\"error\">Archive more than Stale - Does Not Exist</span>";
  }
  
  try {
    $db_version_obj = VersionObj::get_from_db(Globals::$dbaccess);
    $db_version_obj_str = "$db_version_obj";
  } catch (VersionObjException $e) {
    $db_version_obj = FALSE;
    $db_version_obj_str = $e -> getMessage();
  }

  // set color of dump directory display
  $dump_dir_class = $dump_dir == Globals::$dump_dir ? '' : 'advisory';
?>
  <div id="archive_section" class="box">
    <h2>Dump Directory and Archive Section:</h2>
    <p class="<?php echo $dump_dir_class; ?>">Dump Directory: <?php echo $dump_dir; ?></p>
    <ul>
      <li><?php echo $dump_dir_writable_str === TRUE ? 'Is Writable' : $dump_dir_writable_str; ?></li>
<?php
  if ($dump_dir_ok_str === TRUE) {
    echo "<li><span class=\"ok\">Contains a Backup Archive</span></li>\n";
  } else {
    echo "<li><span class=\"warning\">No Backup Archive</span></li>";
  }

  echo "    <li>$archive_state_str</li>\n";

  if ($db_version_obj) {
    echo "      <li>Database Version: $db_version_obj_str</li>\n";
  } else {
    echo "<li>No Database Versioning Record: Please Create one\n";
    echo VersionObj::new_version_obj_form($form_action, 'Create DB Versioning', 'db');
    echo "</li>\n";
  }

  if ($archive_version_obj) {
      echo "      <li>Archive Version: $archive_version_obj_str; </li>\n";
  } elseif ($archive_state_str === TRUE) {
      echo "<li>No Archive Versioning File: Please Create one\n";
      echo VersionObj::new_version_obj_form($form_action, 'Create Archive Versioning', 'file');
      echo "</li>\n";
}
?>
      <li>
        <span class="<?php echo $dump_dir_class; ?>">Change Dump Directory Path?</span>
        <form action="<?php echo $form_action; ?>" method="post" accept-charset="utf-8">
          <ul class="<?php echo $dump_dir_class; ?>">
            <li>
              <input class="<?php echo $dump_dir_class; ?>" type="test" name="dump_dir" value="<?php echo $dump_dir; ?>" size="60" maxlength="255">
            </li>
            <li><input class="advisory" type="submit" name="submit" value="Change Dump Directory"></li>
            <li><input class="ok" type="submit" name="submit" value="Reset Dump Directory to Default"></li>
          </ul>
        </form>
      </li>
      <li>
        <form action="<?php echo $form_action; ?>" method="post" accept-charset="utf-8">
          Click to Refresh or <input class="ok" type="submit" name="submit" value="Create Archive">
        </form>
      </li>
    </ul>
<?php
  // Map section
  if (file_exists($tmp = $dump_dir . DIRECTORY_SEPARATOR . '_map_data_description.html')) {
    echo "<div>\n<p class=\"float-right smaller\">(mouse over or click to view)</p>" . file_get_contents($tmp) . "</div>\n";
  } else {
    echo "<p>No Map Function Currently Exists</p>\n";
  }
  if (($map_obj = get_map_obj(Globals::$dbaccess, $dump_dir))) {
    switch ($map_obj->site_state) {
      case 'need-map':
        echo "<div id=\"create-map-form\" class=\"box click-display\">\n<p class=\"float-right smaller\">(mouse over or click to view)</p>" . $map_obj->create_map_form($form_action) . "</div>\n";
        break;
      default:
        echo $map_obj->create_map_form($form_action);
        break;
    }
  } else {
    echo "<div class=\"box\"><p>Unable to Create Map Object</p></div>\n";
  }

  echo "  </div>\n";
} // end of render_archive_form()

function render_rebuild_db_form()
{
  global $dump_dir;
  global $form_action;
  
  if (Globals::$dbaccess->on_line != 'F') {
    $headline = "<p class=\"advisory\">Site MUST be Offline to Rebuild Database</p>\n";
    $action = "no-rebuild";
  } else {
    $map_obj = get_map_obj(Globals::$dbaccess, $dump_dir);
    switch ($map_obj->site_state) {
      case 'illegal':
      $headline = "<p class=\"advisory\">Site is in Illegal State - Please Edit indicated Objects to correct this</p>\n"
        . "<p class=\"error\">$map_obj->errors</p>\n";
        $action = "no-rebuild";
        break;
      case 'need-map':
        if (!file_exists($dump_dir . DIRECTORY_SEPARATOR . '_map_data_function.php')) {
          $headline = "<p class=\"advisory\">Map Required to Rebuild Database - Please Create</p>\n";
          $action = "no-rebuild";
        } else {
          $action = "ok-rebuild";
          $headline = '';
        }
        break;
      case 'no-map':
        $action = "ok-rebuild";
        $headline = '';
        break;
      default:
        $headline = "<p class=\"advisory\">Illegal Site State: '$map_obj->site_state' - This is a System Error, please report it</p>\n";
        $action = "no-rebuild";
        break;
    }
  }
  
  // check to see if it's OK to rebuild the site and display message if it isn't
  if ($action == "ok-rebuild" && Globals::$dbaccess->archive_stale == 'T') {
    $headline .= "<p class=\"advisory\">Archive is not Up To Date - If you Rebuild using this Archive, You Will Lose Data</p>\n";
    $action = "force-rebuild";
  }
  // if it is present the option to rebuild, dropping tables as we go
  
  switch ($action) {
    case 'ok-rebuild':
      $form = "<form class=\"ok\" action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\">\n"
        . " Click to <input type=\"submit\" name=\"submit\" value=\"Rebuild From Archive\">\n"
        . "</form>\n";
      $classes = "ok";
      break;
    case 'no-rebuild':
      $form = '';
      $classes = "advisory";
      break;
    case 'force-rebuild':
      $form = "<form class=\"advisory\" action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\">\n"
        . " Click to <input type=\"submit\" name=\"submit\" value=\"Rebuild From Stale Archive\">\n"
        . "</form>\n";
      $classes = "warning";
      break;
    default:
      break;
  }

  echo "<div class=\"box $classes\">\n";
  echo "<h2>Rebuild Site Section</h2>";
  echo $headline;
  echo $form;
  echo "</div>\n";
} // end of render_rebuild_db_form()

function request_rebuild_confirmation()
{
?>
  <div class="box warning">
    <form action="<?php echo $action; ?>" method="post" accept-charset="utf-8">
      <h2>Rebuild Site Section</h2>
        <p class="warning">You have Requested to Rebuild the Database from a Stale Archive. Please
            confirm by clicking
            <input class="advisory" type="submit" name="submit" value="Confirm Rebuild">
        </p>
    </form>
  </div>
<?php
} // end of request_rebuild_confirmation()

// End Form Rendering Functions

function get_map_obj($dbaccess, $dump_dir)
{
  // we cache the map object so we can cheaply call this guy to get it.
  static $map_obj = NULL;
  
  if ($map_obj) {
    return $map_obj;
  }
  
  // Check to see if it is possible to create a map function, return false if not
  $path_tmp = $dump_dir . DIRECTORY_SEPARATOR . '_aclass_attribute_defs.php';
  if (!file_exists($path_tmp)) {
    return FALSE;
  }
  
  $str = file_get_contents($path_tmp);
  if (!$str) {
    throw new Exception("Unable to read _aclass_attribute_defs.php from $dump_dir");
  }
  eval($str);
  if (!isset($aclass_defs)) {
    throw new Exception("including _aclass_attribute_defs.php did NOT define \$aclass_defs");
  }
  $source = $aclass_defs;
  
  require_once('Map.php');
  return ($map_obj = new Map($dbaccess, $source));
} // end of get_map_obj()

function process_map_function_form($rc, $dump_dir)
{
  // Check to see if it is possible to create a map function, return false if not
  $path_tmp = $dump_dir . DIRECTORY_SEPARATOR . '_aclass_attribute_defs.php';
  if (!file_exists($path_tmp)) {
    return FALSE;
  }
  
  $str = file_get_contents($path_tmp);
  if (!$str) {
    throw new Exception("Unable to read _aclass_attribute_defs.php from $dump_dir");
  }
  eval($str);
  if (!isset($aclass_defs)) {
    throw new Exception("including _aclass_attribute_defs.php did NOT define \$aclass_defs");
  }
  $source = $aclass_defs;
  
  require_once('Map.php');
  $map_obj = new Map($source);

  list($map_doc_str, $map_func_str) = $map_obj->process_form($rc, $dump_dir);
  // echo "<div class=\"dump-output\">\n$map_doc_str\n\n$map_func_str\n</div>\n";
  file_put_contents($dump_dir . DIRECTORY_SEPARATOR . '_map_data_function.php', $map_func_str);
  file_put_contents($dump_dir . DIRECTORY_SEPARATOR . '_map_data_description.html', $map_doc_str);

} // end of process_map_function_form()

function connect_to_db($db_params = NULL)
{
  if (!$db_params) {
    $db_params = array();
    foreach (array('dbname', 'db_engine', 'host', 'port', 'user', 'password') as $name) {
      $rc_name = "safe_post_{$name}";
      $db_params[$name]= Globals::$rc->$rc_name;
    }
  }
  if (Globals::$rc->safe_post_recreate_database  == 'Y') {
    $db_params['recreate_database'] = TRUE;
    global $drop_first;
    $drop_first = FALSE;
  } else {
    $db_params['create_database'] = TRUE;
    global $drop_first;
    $drop_first = Globals::$rc->safe_post_drop_first == 'Y';
  }
  return new DBAccess($db_params);
} // end of connect_to_db()
// end function definitions

// initial processing of POST data
// set dump directory
if (isset(Globals::$rc->safe_post_dump_dir)) {
  $dump_dir = Globals::$rc->safe_post_dump_dir;
} elseif (isset(Globals::$session_obj->dump_dir)) {
  $dump_dir = Globals::$session_obj->dump_dir;
} else {
  $dump_dir = Globals::$dump_dir;
}

switch (Globals::$rc->safe_post_submit) {
  case 'Go Offline':
    StateMgt::handle_event('GO_OFFLINE');
    // change_site_state('on_line', 'F');
    break;
  case 'Go Online':
    StateMgt::handle_event('GO_ONLINE');
    // change_site_state('on_line', 'T');
    break;
  case 'Create Archive Versioning':
  case 'Create DB Versioning':
    VersionObj::process_ver_obj_form(Globals::$rc, Globals::$dbaccess);
    break;
  case 'Change Dump Directory':
    Globals::$session_obj->dump_dir = Globals::$rc->safe_post_dump_dir;
    break;
  case 'Reset Dump Directory to Default':
    Globals::$session_obj->dump_dir = Globals::$dump_dir;
    break;
  case 'Create Archive':
    make_database_archive(Globals::$dbaccess, $dump_dir, Globals::$private_data_root);
    break;
  case 'Create Map':
    process_map_function_form(Globals::$rc, $dump_dir);
    break;
  case 'Rebuild From Stale Archive':
    request_rebuild_confirmation();
    $request_confirmation_flag = TRUE;
    break;
  case 'Rebuild From Archive':
  case 'Confirm Rebuild':
    $dba = connect_to_db(Globals::$db_params);
    $force_flag = Globals::$rc->safe_post_submit == 'Confirm Rebuild';
    echo rebuild_infrastructure($dba, $dump_dir, TRUE, $force_flag);
    echo reload_database($dba, $dump_dir, Globals::$private_data_root, TRUE, $force_flag);
    $map_obj = get_map_obj($dba, $dump_dir);
    // NOTE: we make the archive and create the new archive in the site dump directory,
    //  not the local dump_dir because that may have changed.
    if ($map_obj->site_state == 'need-map') {
      backup_database_archive(Globals::$dump_dir);
      $vers_obj = VersionObj::get_from_db($dba);
      $vers_obj->inc();
      $make_database_archive($db, Globals::$dump_dir, Globals::$private_data_root);
    }
    break;
  default:
    if (Globals::$rc->safe_post_submit)
      throw new Exception("ReloadDB.php: Internal Error: submit value '"
        . Globals::$rc->safe_post_submit . "' Not Handled");
    break;
}
?>
<h1>Database Reload Utility</h1>

<p class="box ok">Return to <a class="underline" href="/AdminTools.php" title="Administration Tools">Admin Tools</a></p>

<?php
// Render forms
render_site_state_form();

render_archive_form();

if (isset($request_confirmation_flag)) {
  request_rebuild_confirmation();
} else {
  render_rebuild_db_form();
}
return;
?>
