<?php
/*
#doc-start
h1. StageMgt.php - the State Management Object

bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module defines the site state management object "StateMgt":#state_mgt,
which is used to control the state of the system and coordinate the current
model with the database, safe archiving, etc etc.

It is rarely used, so it is only included in those files and functions where needed.

h2(#state_mgt). State Management

*StateMgt* is an object which cannot be instantiated. This makes it sort of a
cheap singleton - for those into Patterns. It only has static methods

State is maniuplated by responding to program generated 'events'. Events are
defined as a prescribed set of strings which are passed to the _handle_event_
method. This method examines the event transition table and either makes the
required state transition or throws an exception.

h3. Events

* GO_OFFLINE - Take site offline
* GO_ONLINE - take site on line
* SAVE_RECORD - a persistent data object which has changed is written to the database
* REFRESH_ACLASS_HASHES - the aclass hash values have been refreshed. The hashes are
used to detect changes in the model. This event signals the successful refresh
of the hashes after the model and database have been synchronized
* MODEL_MISMATCH_EDIT - an AClass model change has been detected which invalidates
the data definitions in the database. The change can be corrected by a mechanical
rebuild of the database. Typical cause is adding or deleting a field from a model
object.
* ILLEGAL_EDIT - an AClass model change has been detected which invalidates
the data definitions in the database. Database rebuild is impossible without
correction. This must be corrected by editing the model. Typical cause is changing
the definition of the key field(s) of an AClass object
* RESTORATIVE_EDIT - A privious version of the site model has been restored and
so it matches the AClass hashes
* START_REBUILD - The start of a database rebuild
* FINISH_REBUILD - Database rebuild has finihed
* CREATE_ARCHIVE - An archive has been successfully created. Archives can only
be created if the site is off line, the current archive is invalid, and the database
is valid. Further, the model and database must be either in sync or differ only
by a legal edit.

h3. Attributes

None

h3. Class Methods

* StateMgt::events() - returns an array containing all the names of defined
events.
* StateMgt::change_state_value($state_name, $new_value) - changes database
state _$state_name_ to _$new_value_. Rolls back if resulting state is illegal.
Throws exceptions on errors in state name, value, and legality of new state.
* StateMgt::legal_state_changeP($state_name, $next_val) - returns TRUE if changing
state _state_name_ to _next_val_ is a legal given the current state of the system.
NOTE: this does not guarantee that the resulting state is legal.
* StateMgt::state_transitions_for(event) - returns the map from site state
as defined as a 4-tuple (see "State Transitions 3":/doc.d/StateTransitions3.html).
The returned array has state tuples as keys and arrays of state variable changes
as values. [empty arrays indicate a legal state for the given event, but no variable
changes required - see SAVE_RECORD for an example]
* StateMgt::handle_event(event) - changes the state of the system in response to the event.
Throws exception if the event is not defined or the site is not in a state which
can handle the event
* StateMgt::rollback() - restores the previous state of the system. This is useful if a process
failure results in not changing the state of the site. (see REFRESH_ACLASS_HASHES for
an example of the use of _rollback()_).

h3. Instance Methods

None
#doc-end
*/
// state management object - contains all state management transitions, etc
class StateMgt {
  static private $state_stack = array();
  // this array is automatically generated using YASiteKit/utilities/state_transitions_3.php.
  // this array is automatically generated using YASiteKit/utilities/state_transitions_3.php.
  //  Not a good idea to edit it by hand.
  static private $legal_states = array(
     'F_F_F_F',
     'F_F_F_T',
     'F_F_T_F',
     'F_F_T_T',
     'F_F_T_X',
     'F_T_F_F',
     'F_T_T_F',
     'F_T_T_T',
     'F_T_T_X',
     'T_F_T_F',
     'T_T_T_F',
     'R_F_T_T',
     'R_T_T_T');
  static public $legal_state_names = array('on_line', 'archive_stale', 'database_valid', 'model_mismatch');
  static public $legal_state_values = array(
    'on_line' => array('F', 'T', 'R'),
    'archive_stale' => array('F', 'T'),
    'database_valid' => array('F', 'T'),
    'model_mismatch' => array('F', 'T', 'X'),
    );
  static private $state_change_ar = array(
    'GO_OFFLINE' => array(
      'T_F_T_F' => array('on_line' => 'F'),
      'T_T_T_F' => array('on_line' => 'F'),
      'R_F_T_T' => array('on_line' => 'F'),
      'R_T_T_T' => array('on_line' => 'F'),
    ), 
    'GO_ONLINE' => array(
      'F_F_T_F' => array('on_line' => 'T'),
      'F_F_T_T' => array('on_line' => 'R'),
      'F_T_T_F' => array('on_line' => 'T'),
      'F_T_T_T' => array('on_line' => 'R'),
    ), 
    'SAVE_RECORD' => array(
      'T_F_T_F' => array('archive_stale' => 'T'),
      'T_T_T_F' => array(),
    ), 
    'REFRESH_ACLASS_HASHES' => array(
      'F_F_T_F' => array(),
      'F_F_T_T' => array('model_mismatch' => 'F'),
    ), 
    'MODEL_MISMATCH_EDIT' => array(
      'F_F_F_T' => array(),
      'F_F_T_F' => array('model_mismatch' => 'T'),
      'F_F_T_T' => array(),
      'F_F_T_X' => array('model_mismatch' => 'T'),
      'F_T_T_F' => array('model_mismatch' => 'T'),
      'F_T_T_T' => array(),
      'F_T_T_X' => array('model_mismatch' => 'T'),
      'T_F_T_F' => array('on_line' => 'R', 'model_mismatch' => 'T'),
      'T_T_T_F' => array('on_line' => 'R', 'model_mismatch' => 'T'),
      'R_F_T_T' => array(),
      'R_T_T_T' => array(),
    ), 
    'ILLEGAL_EDIT' => array(
      'F_F_T_F' => array('model_mismatch' => 'X'),
      'F_F_T_T' => array('model_mismatch' => 'X'),
      'F_T_T_F' => array('model_mismatch' => 'X'),
      'F_T_T_T' => array('model_mismatch' => 'X'),
      'T_F_T_F' => array('on_line' => 'F', 'model_mismatch' => 'X'),
      'T_T_T_F' => array('on_line' => 'F', 'model_mismatch' => 'X'),
      'R_F_T_T' => array('on_line' => 'F', 'model_mismatch' => 'X'),
      'R_T_T_T' => array('on_line' => 'F', 'model_mismatch' => 'X'),
    ), 
    'RESTORATIVE_EDIT' => array(
      'F_F_T_T' => array('model_mismatch' => 'F'),
      'F_F_T_X' => array('model_mismatch' => 'F'),
      'F_T_T_T' => array('model_mismatch' => 'F'),
      'F_T_T_X' => array('model_mismatch' => 'F'),
      'R_F_T_T' => array('on_line' => 'T', 'model_mismatch' => 'F'),
      'R_T_T_T' => array('on_line' => 'T', 'model_mismatch' => 'F'),
    ), 
    'START_REBUILD' => array(
      'F_F_T_F' => array('database_valid' => 'F'),
      'F_F_T_T' => array('database_valid' => 'F'),
      'F_T_F_F' => array('archive_stale' => 'F'),
      'F_T_T_F' => array('archive_stale' => 'F', 'database_valid' => 'F'),
    ), 
    'FINISH_REBUILD' => array(
      'F_F_F_F' => array('database_valid' => 'T'),
      'F_F_F_T' => array('database_valid' => 'T', 'model_mismatch' => 'F'),
    ), 
    'CREATE_ARCHIVE' => array(
      'F_F_T_F' => array(),
      'F_F_T_T' => array('model_mismatch' => 'F'),
      'F_T_T_F' => array('archive_stale' => 'F'),
      'F_T_T_T' => array('archive_stale' => 'F', 'model_mismatch' => 'F'),
    ), 

    );
  // End of automatically Generated array definitions

  private function __construct() {}  // disable constructor
  
  public static function events()
  {
    return array_keys(StateMgt::$state_change_ar);
  } // end of events()
  
  public static function change_state_value($state_name, $new_value, $dbaccess = NULL)
  {
    if ($dbaccess && $dbaccess != Globals::$dbaccess) {
      throw new Exception("change_state_value(): dbaccess set to value other than Globals::\$dbaccess");
    }
    if (!isset(StateMgt::$legal_state_values[$state_name])) {
      throw new Exception("StateMgt::change_state_value($state_name, $new_value): Illegal State Name: '$state_name'");
    }
    if (!in_array($new_value, StateMgt::$legal_state_values[$state_name])) {
      throw new Exception("StateMgt::change_state_value($state_name, $new_value): Illegal Value for state $state_name");
    }
    StateMgt::push_state();
    Globals::$dbaccess->$state_name = $new_value;
    $cur_state = implode('_', array(Globals::$dbaccess->on_line, Globals::$dbaccess->archive_stale,
          Globals::$dbaccess->database_valid, Globals::$dbaccess->model_mismatch));
    if (!in_array($cur_state, StateMgt::$legal_states)) {
      Globals::add_message("StateMgt::change_state_value($state_name, $new_value): Resulted in Illegal State: $cur_state - Rolled Back");
      StateMgt::rollback();
      throw new Exception("StateMgt::change_state_value($state_name, $new_value): Resulted in Illegal State: $cur_state - Rolled Back");
    }
  } // end of current_state_legalP()
  
  public static function legal_state_changeP($state_name, $next_val)
  {
    static $cur_state = NULL;
    static $cur_transitions = NULL;
    if (!$cur_state) {
      $cur_state = implode('_', array(Globals::$dbaccess->on_line, Globals::$dbaccess->archive_stale,
          Globals::$dbaccess->database_valid, Globals::$dbaccess->model_mismatch));
      foreach (StateMgt::$state_change_ar as $ar) {
        foreach ($ar as $key => $ar2) {
          if ($key == $cur_state) {
            foreach ($ar2 as $s_name => $s_val) {
              if (!isset($cur_transitions[$s_name])) {
                $cur_transitions[$s_name] = array($s_val);
              } elseif (!in_array($s_val, $cur_transitions[$s_name])) {
                $cur_transitions[$s_name] = array($s_val);
              }
            }
          }
        }
      }
    }
    return isset($cur_transitions[$state_name]) && in_array($next_val, $cur_transitions[$state_name]);
  } // end of legal_state_changeP()
  
  public static function state_transitions_for($event)
  {
    if (!isset(StateMgt::$state_change_ar[$event])) {
      throw new Exception("StateMgt::state_transitions_for($event): Undefined Event Name");
    }
    return StateMgt::$state_transitions_for[$event];
  } // end of state_transitions_for()

  private static function push_state()
  {
    array_push(StateMgt::$state_stack, array(Globals::$dbaccess->on_line, Globals::$dbaccess->archive_stale,
        Globals::$dbaccess->database_valid, Globals::$dbaccess->model_mismatch));
  } // end of push_state()
  
  private static function pop_state()
  {
    if (StateMgt::$state_stack) {
      return array_pop(StateMgt::$state_stack);
    } else {
      throw new Exception("StateMgt::pop_state(): Illegal Rollback - stack Empty");
    }
    
  } // end of pop_state()

  public static function handle_event($event)
  {
    if (!isset(StateMgt::$state_change_ar[$event])) {
      throw new Exception("StateMgt::handle_event($event): Illegal Event");
    }
    $current_state_name = Globals::$dbaccess->on_line . '_'
      . Globals::$dbaccess->archive_stale . '_'
      . Globals::$dbaccess->database_valid . '_'
      . Globals::$dbaccess->model_mismatch;
    if (!isset(StateMgt::$state_change_ar[$event][$current_state_name])) {
      throw new Exception("StateMgt::handle_event($event): Event Illegal for Current State: '$current_state_name'");
    }
    StateMgt::push_state();
    foreach (StateMgt::$state_change_ar[$event][$current_state_name] as $attr => $val) {
      Globals::$dbaccess->$attr = $val;
    }
  } // handle_event_change_state()
  
  public static function rollback()
  {
    list($on_line, $archive_stale, $database_valid, $model_mismatch) = StateMgt::pop_state();
    Globals::$dbaccess->on_line = $on_line;
    Globals::$dbaccess->archive_stale = $archive_stale;
    Globals::$dbaccess->database_valid = $database_valid;
    Globals::$dbaccess->model_mismatch = $model_mismatch;
  } // end of rollback()
}
?>