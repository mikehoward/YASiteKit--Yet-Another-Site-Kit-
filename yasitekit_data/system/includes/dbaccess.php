<?php
/*
#doc-start
h1.  dbaccess.php - the DBAccess Database Adaptor

Created by  on 2010-02-11.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

A bunch of database routines bundled up into an Object.

IMHO most database interface layers are too complicated. That's because they
try to do too much stuff. This interface layer aims at simplicity, which is
achieved by only supporting a bare bones subset of SQL.

The module currently supports 5 PHP database drivers:

* sqlite
* sqlite3
* mysql
* mysqli
* postgresql

Everything is accessed by creating a DBAccess object - which creates the
connection or throws an exception.

Persistent Attributes are automatically created and maintained by the object.
This provides a means of managing the state of the database. For example,
the application may define a DBAccess attribute called _error_ and save
a value of 'T' or 'F' in it. This can be used to take the application off
line for maintenance and to signal asynchonously running processes that the
system is not available. "see":#attributes below.

The Database State has evolved and now supports fairly complex safeguards
of data for data modeling evolution. See "State Transitions":/doc.d/state_transitions.html
for details.

h2. Error Handling

Error handling is non-invasive, but the programmer's responsibility. This
contrasts sharply with the rest of the system which almost always throws
an exception. This was a conscous choice - although not necessarily the
right one - because it seemed more likely to want to deal with errors
programatically than by defaulting to aborting. It also makes it easy
to ignore errors.

Generally, methods which have side effects - creating tables, dropping, etc -
return FALSE on failure and something non-FALSE on success. Check using
the triple equals ($dbaccess->operation(...) !== FALSE) { ... }.

After an error is detected, the error information can be retrieved using the
_error()_ method. This always contains the last error message emitted by the
underlying database handler.

If you don't check it, then the bad things go unnoticed.

h2. Instantiation

To use, create a DBAccess object:

pre. $dbaccess = new DBAccess($db_params, $verbose = FALSE);

where _db_params_ is an associative array containing the following keys:

* db_params - is an array containing all the information needed to connect
to a database using the PHP database adapter specified. It has the following
keys:
** db_engine - one of none, sqlite, sqlite3, mysql, mysqli, postgresql
** dbname - name of database to connect to
** host - name of host - mysql, mysqli, postgresql
** port - port number - mysql, mysqli, postgresql
** user - user id for connection - mysql, mysqli, postgresql
** password - password for user in connection - mysql, mysqli, postgresqlf
** create_database - boolean - if present and TRUE, then the database will
be created if it does not yet exist.
** recreate_database - boolean - if present and TRUE, then the database will
be dropped if it exists, and then recreated

h2. Conventions

Conventions used in this module:

* sql - is a string which is passed to the underlieing query function. It needs to be
properly escaped _prior_ to passing it on.
* $dict - is a dictionary of key/value pairs where the _keys_ are column names and the
_values_ are text strings. All data is treated as text and quoted.
* $where - is a dictionary identical in format to _$dict_. It is used to construct
_where_ clauses in select, delete, and update statements.

h2(#attributes). Attributes / aka Database State

All DBAccess attributes are used to maintain Database State.

Very briefly each DBAccess object operates in three main states:

* Off-Line - on_line == 'F' - this is used to do maintenance on the database - typically
rebulding the struture needed for an application.
* On-Line - on_line == 'T' - full functioning of the database is available EXCEPT for
dropping or modifying table definitions.
* Read-Only - on_line == 'R' - data may be read, but not modified EXCEPT for specified
metadata tables. See _append_metadata_table()_ and _del_metadata_table()_ "below":#class_methods

See "DatabaseState":/doc.d/DatabaseState.html for details.

Each Database and Database Engine has a unique set of state variables. More
than one database/database engine many be active at any time. Their states
are maintained separately under the private key _dbaccess_id_ - which is the
string returned by __toString().

h3(#attribute_defaults). Default Attributes

The following attributes are always defined for all databases. They are all initialized
to 'F' (false) when the database is created and first accessed, which places the
database in the offline state and marks it as invalid, but consistent.
See "State Transitions":/doc.d/state_transitions.html for (exhaustive) detail.

* on_line
* database_valid
* archive_stale
* model_mismatch

h3(#attribute_implementation). Attribute Implementation

Attributes are stored in the distinguished table *_dbaccess*. All values are read
at instantiation time - which forms a sort of caching - so read access is inexpensive.
Assignment both creates attributes and writes them to the data store - so we
have - in effect - a write-through cache.

This is all implemented via the PHP magic methods:

* __get(attribute) - returns the value of the named attribute or throws an AttributeError
exception if it is not defined. This is a _feature_ to help detect spelling errors.
* __set(attribute, value) - creates or redefines the named attribute. _value_ must
be a non-empty string. If it is anything else throws an AttributeError exception.
Assigning a NEW value or CREATING a new attribute results in the name/value pair
written to the database, but not otherwise.
Does not return a meaningful value.
* __isset(attribute) - returns TRUE or FALSE if _attribute_ is defined in the database.
* __unset(attribute) - removes _attribute_ from both the instance and the database.

The table *_dbaccess* contains two fields:

* tag - varchar(255) - the key field
* value - text - the value field

WARNING: the cache is implemented as a static class variable. This means that
separate instances of a DBAccess object which point to the identically same database
AND are running the the same process context will share the cache. Instances
started in different processes will NOT share the cache, after the initial load.

This sets up a sort of _race_ condition. The likelyhood of instabilities are small
as long as competing processes have short run times. However, it is possible for
a process to start, followed immediately by a second one, and the first process to
set a value in the cache. In this case the second process will not see the new
value. This can be a problem if the first process is taking the application off line.

h2(#class_methods). Class Methods

* available_db_engines() - returns array of available database engines
* append_metadata_table($tablename) - appends _$tablename_ to the list of
tables which may be modified in Read-Only mode. The list is initially:
** _dbaccess - the database state table
** sessions - the web site sessions table
* del_metadata_table($tablename) - deletes _$tablename_ from the list of
tables which may be modified in Read-Only mode

h2(#instance_methods). Instance Methods

Instance methods are described in functional groups. Pay attention to
the warnings.

h3(#admin_methods). Administrative methods

* close() - closes the connection and makes the object useless
* register_close_function() - registers a function with args to be called just
prior to closing the database
* unregister_close_function() - removes function from list to call on closing
database.
* attribute_names() - returns array of currently defined attributes.
* verbose(value = TRUE) - sets and resets the _verbose_ flag.

h3(#info_methods). Information Methods

* connectedP() - returns TRUE if connection to database exists, else FALSE
* error() - returns the string value of the last database error.
* errorP() - returns TRUE if the last operation resulted in an error, else FALSE
* changes() - returns number of rows changed by last data manipulation operation
* table_exists(table_name) - returns TRUE if 'select count(*) from table_name' works, else FALSE.
* rows_in_table(table_name) - returns integer number of rows in table OR FALSE if table
does not exist (or select statement fails)

h3(#data_def_methods). Data Definition Methods:

p(warning). %WARNING: These two functions are very dangerous - so don't use them.%
%(I haven't figured out how to make them safe yet.%
%Will probably open a connection and make sure that the database to be created does not exist%
%OR% %that it has a fresh archive or something like that)%

* create_database($dbname) - creates the specified database
in the database engine specified in _$db_params_. NOTE that this is NOT a class function
and must be called on a DBAccess instance.
* drop_database($dbname) - drops the specified database. Same issues as _create_database()_.

p(advisory). %These functions ONLY work when the database is in the _offline_ state.%

* create_table(table_name, field_definitions, drop_first = FALSE) - attempts to
define table _table_name_ using the array _field_definitions_.  If the flag _drop_first_
is TRUE, then the table is dropped before being created. If FALSE and the table exists,
then returns FALSE. The _field_definitions_ array is a simple array of arrays
holding two or three values:
** field name - string - required - gives name of field. Must satisfy [a-z]\w*
** field definition - string - required - full SQL style data definition.
** key_flag - boolean - optional - Must be TRUE if field_name is key field
* drop_table(table_name) - drops named table

h3(#data_manipulation_methods). Data Maniuplation Methods

These methods provide a database portable interface. Use them
rather than writing SQL because they will continue to work when this
thing supports Couchdb, Mongodb and other non-SQL engines, whereas the SQL won't.
[NOTE: the system is in process of migration to strictly using these functions]

p(advisory). %NOTE: insert, update, and delete operations ONLY work if the database is NOT in _readonly_ mode.%

* insert_into_table($table, $dict) - uses the key/value pairs in _$dict_
to construct an _insert_ statement for _$table_ and executes it. Returns TRUE or FALSE.
* update_table($table, $dict, $where) - uses the key/value pairs in _$dict_
to construct an update statement for _$table_. _$where_ is passed through _db_escape_where()_.
* delete_from_table($table, $where) - similar to the other two functions,
except it deletes the specified records from _$table_.
* select_from_table($table, $select_list = NULL, $where = NULL, $orderby = NULL) -
provides an abstract interface to _select_as_array()_. _$select_list_ can be
NULL, a string containing a comma separated list of items, or an array of selections.
Each selection is treated as an opaque string, so phrases such as 'foo as bar'
will work. Each selection item is passed through the database specific string
escape method to deal with SQL injection.

h3(#utility_methods). Utilties

p. The following are utility functions which abstract various database specific escape
methods. Generally you won't need to use them because they are automatically
used in the data manipulation functions immediately above. If you do opt
to use the raw database query functions below, then you should examine the
code to see how these escapes are used.

* escape_string($str) - returns _$str_ as modified by the database driver's
db_escape_string() method.
* escape_array_values($ar) - returns an array where each _key_ in _$ar_
is replaced by one which every character which is not in the range: [a-zA-Z0-9_]
is deleted and each _value_ is quoted using _db_escape_string()_.
* escape_where($where) - returns a where clause as follows:
** if $where is empty or False, returns an empty string ('')
** if $where is an array, constructs a where clause which tests for simultaneous equality
on all key-value conditions
** otherwise, $where is assumed to be a non-empty string. The returned string is guarenteed
to begin with the word 'where'.

h3(#low_level_methods). Low Level Methods

These methods are include for completeness. 

p(warning). %WARNING: the following functions do not check the state of the database, so%
%you have no protection when using them.%

p(advisory). %Lower Level functions which should be avoided when writing portable code:%

* select_as_array($sql)- returns an array of results.  If the $sql is not a _select_, then
the query is diverted to _query()_ and returns an empty array if _query()_ succeeded.
Returns FALSE on failure.
* query($sql) -  attempts to execute _$sql_. If _$sql_ contains the word
_select_, then _query()_ returns the result of calling _db_select_as_array()_. Otherwise,
returns TRUE if the _$sql_ succeeds, else FALSE.
#end-doc
*/


// Specific database drivers
// postgresql
if (function_exists('pg_connect')) {
  DBAccess::add_to_available_dbs('postgresql');
  $pg_last_result = NULL;
  
  function db_postgresql_database_exists($dbname)
  {
    # code...
  } // end of db_postgresql_database_exists()
  
  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
  function db_postgresql_create_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'template1';
    $conn = db_postgresql_createConnection($db_params);
    db_postgresql_query($conn, "create database $dbname");
    db_postgresql_closeConnection($conn);
  } // end of db_postgresql_create_database()
  
  function db_postgresql_drop_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'template1';
    $conn = db_postgresql_createConnection($db_params);
    db_postgresql_query($conn, "drop database $dbname");
    db_postgresql_closeConnection($conn);
  } // end of db_postgresql_drop_database()
  
  function db_postgresql_createConnection($db_params = NULL) {
    $ar = array();
    if (!$db_params) {
      throw new DBAccessException("DBAccess::createConnection(db_params): No Datatabase Parameters Specified");
    }
    foreach ($db_params as $key => $value) {
      // check for supported parameters
      if (in_array($key, array('host', 'hostaddr', 'port', 'dbname', 'user', 'password', 'connect_timeout',
          'options', 'tty', 'sslmode', 'requiressl', 'service'))) {
        $ar[] = "$key='$value'";
      }
    }
    $connection_string = implode(' ', $ar);
    return pg_connect($connection_string);
  }

  function db_postgresql_closeConnection($db_conn) {
    return $db_conn ? pg_close($db_conn) : TRUE;
  }

  function db_postgresql_select_as_array($db_conn, $sql) {
    global $pg_last_result;
    $pg_last_result = FALSE;
    if (!$db_conn) { return array();}
    if (preg_match('/\bselect\b/', $sql) == 0) {
      return db_postgresql_query($db_conn, $sql);
    }
    if (($pg_last_result = pg_query($db_conn, $sql)) !== FALSE) {
      $ar = array();
      while (($row = pg_fetch_assoc($pg_last_result))) {
        $ar[] = $row;
      }
      return $ar;
    } else {
      return FALSE;
    }
  }

  function db_postgresql_query($db_conn, $sql) {
    global $pg_last_result;
    $pg_last_result = FALSE;
    if (!$db_conn) { return FALSE;}
    if (preg_match('/\bselect\b/', $sql) == 1) {
      return db_postgresql_select_as_array($db_conn, $sql);
    }
    return ($pg_last_result = pg_query($db_conn, $sql)) !== FALSE;
  }
  
  function db_postgresql_escape_string($db_conn, $str) {
    return pg_escape_string($db_conn, $str);
  }
    
  function db_postgresql_error($db_conn) {
    global $pg_last_result;
    ob_start();
    echo pg_last_error($db_conn);
    if ($pg_last_result) pg_result_error($pg_last_result);
    debug_print_backtrace();
    return ob_get_clean();  
  }
  
  function db_postgresql_errorP($db_conn)
  {
    return pg_last_error($db_conn) ? TRUE:FALSE;
  } // end of db_postgresql_errorP()
  
  function db_postgresql_changes($db_conn)
  {
    global $pg_last_result;
    return pg_affected_rows($pg_last_result);
  } // end of db_postgresql_changes()
  
  function db_postgresql_table_exists($db_conn, $tablename)
  {
    $tablename = preg_replace('/[^\w]/', '', $tablename);
    $result = pg_query($db_conn, "select * from pg_tables where tablename = '$tablename'");
    return $result !== FALSE && pg_num_rows($result) > 0;
  } // end of db_postgresql_table_exists()
  
  function db_postgresql_rows_in_table($db_conn, $tablename)
  {
    $sql = pg_escape_string($db_conn, "select count(*) as cnt from $tablename");
    $result = pg_query($db_conn, $sql);
    if ($result === FALSE)
      return FALSE;
    $row = pg_fetch_assoc($result);
    return intval($row['cnt']);
  } // end of db_postgresql_rows_in_table()
}

// mysqli - the improved mysql driver
if (class_exists('MySQLi')) {
  DBAccess::add_to_available_dbs('mysqli');

  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
  function db_mysqli_create_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'mysql';
    $conn = db_mysqli_createConnection($db_params);
    db_mysqli_query($conn, "create database $dbname");
    db_mysqli_closeConnection($conn);
  } // end of db_mysqli_create_database()
  
  function db_mysqli_drop_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'mysql';
    $conn = db_mysqli_createConnection($db_params);
    db_mysqli_query($conn, "drop database $dbname");
    db_mysqli_closeConnection($conn);
  } // end of db_mysqli_drop_database()

  function db_mysqli_createConnection($db_params = NULL) {
    if (!$db_params) {
      throw new DBAccessException("DBAccess::createConnection(db_params): No Datatabase Parameters Specified");
    }
    $db_conn = new MySQLi($db_params['host'], $db_params['user'],
      $db_params['password'], $db_params['dbname'], $db_params['port'], $db_params['unix_socket']);
    if (!$db_conn->connect_error) {
      $db_conn->autocommit(TRUE);
      return $db_conn;
    } else {
      var_dump($db_conn->connect_error);
      return FALSE;
    }
  }

  function db_mysqli_closeConnection($db_conn) {
    return $db_conn ? $db_conn->close() : TRUE;
  }

  function db_mysqli_select_as_array($db_conn, $sql) {
    if (!$db_conn) { return array(); }
    if (preg_match('/\bselect\b/', $sql) == 0) {
      return db_mysqli_query($db_conn, $sql);
    }
    if (($result = $db_conn->query($sql)) !== FALSE) {
      $ar = array();
      while (($row = $result->fetch_assoc())) {
        $ar[] = $row;
      }
      $result->close();
      return $ar;
    } else {
      return FALSE;
    }
  }

  function db_mysqli_query($db_conn, $sql) {
    if (!$db_conn) { return array(); }
    if (preg_match('/\bselect\b/', $sql) == 1) {
      return db_mysqli_select_as_array($db_conn, $sql);
    }
    return $db_conn->query($sql) !== FALSE;

  }
  
  function db_mysqli_escape_string($db_conn, $str) {
    return $db_conn ? $db_conn->real_escape_string($str) : $str;
  }
  
  function db_mysqli_error($db_conn) {
    return $db_conn->error;
  }
  
  function db_mysqli_errorP($db_conn)
  {
    return $db_conn->errno ? TRUE:FALSE;
  } // end of db_postgresql_errorP()
  
  function db_mysqli_changes($db_conn)
  {
    return $db_conn->affected_rows;
  } // end of db_mysqli_changes()
  
  function db_mysqli_table_exists($db_conn, $tablename)
  {
    $tablename = preg_replace('/[^\w]/', '', $tablename);
    $result = $db_conn->query("show table status like '$tablename'");
    return $result !== FALSE && $result->num_rows > 0;
  } // end of db_mysqil_table_exists()
  
  function db_mysqli_rows_in_table($db_conn, $tablename)
  {
    if (!$db_conn)
      return FALSE;
    $sql = $db_conn->real_escape_string("select count(*) as cnt from $tablename");
    $result = $db_conn->query($sql);
    if (!$result)
      return FALSE;
    $row = $result->fetch_assoc();
    return intval($row['cnt']);
  } // end of db_mysqli_rows_in_table()
}

// mysql - the old, non-improved one
if (function_exists('mysql_connect')) {
  DBAccess::add_to_available_dbs('mysql');

  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
  function db_mysql_create_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'mysql';
    $conn = db_mysql_createConnection($db_params);
    db_mysql_query($conn, "create database $dbname");
    db_mysql_closeConnection($conn);
  } // end of db_mysql_create_database()
  
  function db_mysql_drop_database($db_params, $dbname)
  {
    $db_params['dbname'] = 'mysql';
    $conn = db_mysql_createConnection($db_params);
    db_mysql_query($conn, "drop database $dbname");
    db_mysql_closeConnection($conn);
  } // end of db_mysql_drop_database()

  function db_mysql_createConnection($db_params = NULL) {
    if (!$db_params) {
      throw new DBAccessException("DBAccess::createConnection(db_params): No Datatabase Parameters Specified");
    }
    $host = $db_params['host'] ? $db_params['host'] : 'localhost';
    if ($host == 'localhost' && $db_params['unix_socket']) {
      $host = ":" . $db_params['unix_socket'];
    } else {
      $host = isset($db_params['port']) && $db_params['port'] ?
        $db_params['host'] . ':' . $db_params['port'] : $db_params['host'];
    }
    if (!($db_conn = mysql_connect($host, $db_params['user'], $db_params['password']))) {
      return FALSE;
    }
    return mysql_select_db($db_params['dbname'], $db_conn) ? $db_conn : FALSE;
  }

  function db_mysql_closeConnection($db_conn) {
    return $db_conn ? mysql_close($db_conn) : TRUE;
  }

  function db_mysql_select_as_array($db_conn, $sql) {
    if (!$db_conn) { return array(); }
    if (preg_match('/\bselect\b/', $sql) == 0) {
      return db_mysql_query($db_conn, $sql);
    }
    if (($result = mysql_query($sql, $db_conn)) !== FALSE) {
      $ar = array();
      while (($row = mysql_fetch_assoc($result))) {
        $ar[] = $row;
      }
      return $ar;
    } else {
      return FALSE;
    }
  }

  function db_mysql_query($db_conn, $sql) {
    if (!$db_conn) { return array(); }
    if (preg_match('/\bselect\b/', $sql) == 1) {
      return db_mysql_select_as_array($db_conn, $sql);
    }
    return mysql_query($sql, $db_conn) !== FALSE;
  }
  
  function db_mysql_escape_string($db_conn, $str) {
    return $db_conn ? mysql_real_escape_string($str, $db_conn) : mysql_escape_string($str);
  }
    
  function db_mysql_error($db_conn) {
    return mysql_error($db_conn);
  }
  
  
  function db_mysql_errorP($db_conn)
  {
    return mysql_errno($db_conn) ? TRUE:FALSE;
  } // end of db_postgresql_errorP()

  function db_mysql_changes($db_conn)
  {
    return mysql_affected_rows($db_conn);
  } // end of db_mysql_changes()

  function db_mysql_table_exists($db_conn, $tablename)
  {
    $tablename = preg_replace('/[^\w]/', '', $tablename);
    $result = mysql_query("show table status like '$tablename'", $db_conn);
    return $result !== FALSE && mysql_num_rows($result) > 0;
  } // end of db_mysql_table_exists()
  
  function db_mysql_rows_in_table($db_conn, $tablename)
  {
    if (!$db_conn)
      return FALSE;
    $sql = mysql_real_escape_string("select count(*) as cnt from $tablename");
    $result = mysql_query($sql, $db_conn);
    if ($result === FALSE)
      return FALSE;
    $row = mysql_fetch_assoc($result);
    return intval($row['cnt']);
  } // end of db_mysql_rows_in_table()
}

// sqlite3 - the newer sqlite database
if (class_exists('SQLite3')) {
  DBAccess::add_to_available_dbs('sqlite3');
  
  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
  function db_sqlite3_create_database($db_params, $dbname)
  {
    return;
  } // end of db_sqlite3_create_database()
  
  function db_sqlite3_drop_database($db_params, $dbname)
  {
    unlink($dbname);
  } // end of db_sqlite3_drop_database()

  function db_sqlite3_createConnection($db_params = NULL) {
    if (!$db_params) {
      throw new DBAccessException("DBAccess::createConnection(db_params): No Datatabase Parameters Specified");
    }
    try {
      $db_conn = new SQLite3($db_params['dbname'], SQLITE3_OPEN_READWRITE);
    } catch (Exception $e) {
      $db_conn = new SQLite3($db_params['dbname'], SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    }
    return $db_conn;
  }

  function db_sqlite3_closeConnection($db_conn) {
    return $db_conn ? $db_conn->close() : TRUE;
  }

  function db_sqlite3_select_as_array($db_conn, $sql, $recursive_call = FALSE) {
    if (!$db_conn) { return array(); }
    if (preg_match('/\bselect\b/', $sql) == 0) {
      return db_sqlite3_query($db_conn, $sql, TRUE) ? array() : FALSE;
    }
    $ar = array();
    $result = $db_conn->query($sql);
    if ($result instanceof SQLIte3Result) {
      while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $ar[] = $row;
      }
      return $ar;
    } else {
      return $result;
    }
  }

  function db_sqlite3_query($db_conn, $sql, $recursive_call = FALSE) {
    if (!$db_conn) { return FALSE; }
    if (preg_match('/\bselect\b/', $sql) == 1) {
      return db_sqlite3_select_as_array($db_conn, $sql, TRUE);
    }
    return $db_conn->exec($sql) !== FALSE;
  }
  
  function db_sqlite3_escape_string($db_conn, $str) {
    return $db_conn->escapeString($str);
  }
  
  function db_sqlite3_error($db_conn) {
    return $db_conn->lastErrorMsg();
  }
  
  function db_sqlite3_errorP($db_conn)
  {
    return $db_conn->lastErrorCode() ? TRUE:FALSE;
  } // end of db_postgresql_errorP()

  function db_sqlite3_changes($db_conn)
  {
    return $db_conn->changes();
  } // end of db_sqlite_changes()

  function db_sqlite3_table_exists($db_conn, $tablename)
  {
    $tablename = preg_replace('/[^\w]/', '', $tablename);
    $result = $db_conn->query("pragma table_info($tablename)");
    return $result && $result->numColumns() > 0;
  } // end of db_sqlite3_table_exists()
  
  
  function db_sqlite3_rows_in_table($db_conn, $tablename)
  {
    if (!$db_conn)
      return FALSE;
    $sql = $db_conn->escapeString("select count(*) as cnt from $tablename");
    $result = $db_conn->query($sql);
    if (!($result instanceof SQLIte3Result))
      return FALSE;
    $row = $result->fetchArray(SQLITE_ASSOC);
    return intval($row['cnt']);
  } // end of db_sqlite3_rows_in_table()
}

// old sqlite2 database
if (function_exists('sqlite_open')) {
  DBAccess::add_to_available_dbs('sqlite');
  
  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
  function db_sqlite_create_database($db_params, $dbname)
  {
    return TRUE;
  } // end of db_sqlite_create_database()
  
  function db_sqlite_drop_database($db_params, $dbname)
  {
    return file_exists($dbname) ? unlink($dbname) : TRUE;

  } // end of db_sqlite_drop_database()

  function db_sqlite_createConnection($db_params = NULL) {
    if (!$db_params) {
      throw new DBAccessException("DBAccess::createConnection(db_params): No Datatabase Parameters Specified");
    }
    $db_conn = sqlite_open($db_params['dbname'], 0700, $error_message);
    if (!$db_conn) {
      print("<p>Unable to open sqlite3 database: $error_message</p>");
    }
    return $db_conn;
  }

  function db_sqlite_closeConnection($db_conn) {
    return $db_conn ? sqlite_close($db_conn) : TRUE;
  }

  function db_sqlite_select_as_array($db_conn, $sql) {
    if (!$db_conn) { return FALSE; }
    if (preg_match('/\bselect\b/', $sql) == 0) {
      return db_sqlite_query($db_conn, $sql);
    }
    try{
      $ar = array();
      if (($result = sqlite_array_query($db_conn, $sql, SQLITE_ASSOC)) !== FALSE) {
        foreach ($result as $row){
          $ar[] = $row;
        }
      }
      return $ar;
    } catch (Exception $e) {
      throw new DBAccessException("DBAccess::select_as_array(..., $sql): $e");
    }
  }

  function db_sqlite_query($db_conn, $sql) {
    if (preg_match('/\bselect\b/', $sql) == 1) {
      return db_sqlite_select_as_array($db_conn, $sql);
    }
    return sqlite_query($db_conn, $sql) !== FALSE;
  }
  
  function db_sqlite_escape_string($db_conn, $str) {
    if (!is_string($str)) {
      echo("db_escape_string(): str is not a string\n");
      throw new DBAccessException("db_escape_string(): str is not a string");
    }
    return sqlite_escape_string($str);
  }
    
  function db_sqlite_error($db_conn) {
    return sqlite_error_string(sqlite_last_error($db_conn));
  }
  
  function db_sqlite_errorP($db_conn)
  {
    return sqlite_last_error($db_conn) ? TRUE:FALSE;
  } // end of db_postgresql_errorP()
  
  function db_sqlite_changes($db_conn)
  {
    return sqlite_changes($db_conn);
  } // end of db_sqlite_changes()
  
  function db_sqlite_table_exists($db_conn, $tablename)
  {
    $tablename = preg_replace('/[^\w]/', '', $tablename);
    $ar = sqlite_query($db_conn, "pragma table_info($tablename);");
    return $ar !== FALSE && sqlite_num_rows($ar) > 0;
  } // end of db_sqlite_table_exists()
  
  function db_sqlite_rows_in_table($db_conn, $tablename)
  {
    if (!$db_conn)
      return FALSE;
    $sql = sqlite_escape_string("select count(*) as cnt from $tablename");
    $result = sqlite_array_query($db_conn, $sql, SQLITE_ASSOC);
    if ($result === FALSE) {
      return FALSE;
    }
    $row = $result[0];
    return intval($row['cnt']);
  } // end of db_sqlite_rows_in_table()
}

DBAccess::add_to_available_dbs('none');

  // NOTE: db_params arg is pushed on by __call() and does not appear in the method signature
function db_none_create_database($db_params, $dbname)
{
  return "db_none_create_database($db_params, $dbname)";
} // end of db_none_create_database()

function db_none_drop_database($db_params, $dbname)
{
  return "db_none_drop_database({$db_params}, {$dbname})";
} // end of db_none_drop_database()

function db_none_createConnection($dbn) {
  return "db_none_createConnection({$dbn})";
}

function db_none_closeConnection($db_conn) {
  return "db_none_closeConnection($db_conn)";
}

function db_none_select_as_array($db_conn, $sql) {
  if (preg_match('/\bselect\b/', $sql) == 0) {
    return db_none_query($db_conn, $sql . ' - called from db_none_select_as_array()');
  }
  return "db_none_select_as_array($db_conn, $sql)";
}

function db_none_query($db_conn, $sql) {
  if (preg_match('/\bselect\b/', $sql) == 1) {
    return db_none_select_as_array($db_conn, $sql . ' - called from db_none_query()');
  }
  return "db_none_query($db_conn, $sql)";
}

function db_none_escape_string($db_conn, $str) {
  return preg_replace("/'/", "''", $str);
}

function db_none_error($db_conn) {
  return "db_none_error($db_conn)";
}

function db_none_errorP($db_conn) {
  return FALSE;
}

function db_none_changes($db_conn) {
  return "db_none_changes($db_conn)";
}

function db_none_table_exists($db_conn, $tablename)
{
  return "db_non_table_exists($tablename)";
} // end of db_none_table_exists()

// some utility functions
function array_as_string($ar)
{
  if (!$ar) {
    return "<NULL>";
  } elseif (is_array($ar)) {
    $tmp = array();
    foreach ($ar as $key => $val) {
      if (is_int($key)) {
        $tmp[] = array_as_string($val);
      } else {
        $tmp[] = "$key => " . array_as_string($val);
      }
    }
    return 'array( ' . implode(',', $tmp) . ' )';
  } else {
    try {
      return (string)$ar;
    } catch (Exception $e) {
      return "\$ar is not a stringable: $e";
    }
  }
} // end of array_as_string()

// generic functions which do not depend on underlieing database implementation
class DBAccessException extends Exception {
  public function __construct($msg)
  {
    if (Globals::$site_installation == 'development') {
      echo "AClassException: $msg\n";
      debug_print_backtrace();
    }
    parent::__construct($msg);
  } // end of __construct()
  
}

class DBAccess {
  private $db_engine;
  private static $available_dbs = array();
  private static $metadata_tables = array('_dbaccess', 'sessions');
  private $pre_close_functions = array();
  private $conn;
  private $connect_params;
  private $verbose = FALSE;
  private static $db_open_flags = array();
  private static $values = array();
  private static $legal_param_keys = array(
    'db_engine',
    'dbname',
    'host',
    'port',
    'user',
    'password',
    'create_database',
    'recreate_database',
    );
  private static $identifier_param_keys = array(
    'db_engine',
    'dbname',
    'host',
    'port',
    'user',
    'password',
    );
  private $dbaccess_id = NULL;

  public static function add_to_available_dbs($db_engine)
  {
    if (!in_array($db_engine, DBAccess::$available_dbs)) {
      DBAccess::$available_dbs[] = $db_engine;
    }
  } // end of add_to_available_dbs()
  
  public static function append_metadata_table($tablename)
  {
    if (!in_array($tablename, DBAccess::$metadata_tables))
      DBAccess::$metadata_tables[] = $tablename;
  } // end of append_metadata_table()
  
  public static function del_metadata_table($tablename)
  {
    if (($key = array_search($tablename, DBAccess::$metadata_tables)) !== FALSE)
      unset(DBAccess::$metadata_tables[$key]);
  } // end of del_metadata_table()

  // $params is an associative array containing all the parameters needed to connect
  // to the database. These are passed directly to the database connector
  //  keys are:
  //  db_engine - one of none, sqlite, sqlite3, mysql, mysqli, postgresql
  //  dbname - name of database to connect to
  //  host - name of host - mysql, mysqli, postgresql
  //  port - port number - mysql, mysqli, postgresql
  //  user - user id for connection - mysql, mysqli, postgresql
  //  password - password for user in connection - mysql, mysqli, postgresql
  public function __construct($params, $verbose = FALSE)
  {
    if (!in_array($params['db_engine'], DBAccess::$available_dbs)) {
      throw new DBAccessException("DBAccess::__construct(" . array_as_string($params) . "): Illegal Database Engine Subsystem"
        . "(legal engines are: " . array_as_string(DBAccess::$available_dbs) . ")");
    }
    $this->db_engine = $params['db_engine'];
    $this->connect_params = $params;
    unset($this->connect_params['db_engine']);
    $this->verbose = $verbose;
    if ($this->verbose) {
      echo "DBAccess::__construct(" . array_as_string($params) . ", $verbose)\n";
    }

    if (isset($params['recreate_database']) && $params['recreate_database']) {
      unset($this->connect_params['recreate_database']);
      if (isset($params['create_database'])) unset($this->connect_params['create_database']);
      $this->drop_database($this->connect_params['dbname']);
      $create_db_on_failure = TRUE;
    } elseif (isset($this->connect_params['create_database']) && $this->connect_params['create_database']) {
      unset($this->connect_params['create_database']);
      $create_db_on_failure = TRUE;
    } else {
      $create_db_on_failure = FALSE;
    }
    if (!($this->conn = $this->createConnection($this->connect_params))) {
      if ($create_db_on_failure) {
        $this->create_database($this->connect_params['dbname']);
        $this->conn = $this->createConnection($this->connect_params);
      }
    }
    
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::connect(" . array_as_string($this->connect_params)
        . "): Connection to $this->db_engine failed");
    }
    
    $ar = array();
    foreach (DBAccess::$identifier_param_keys as $key) {
      if (isset($params[$key])) $ar[] = $params[$key];
    }
    $this->dbaccess_id = implode(':', $params);

    if (!isset(DBAccess::$values[$this->dbaccess_id])) {
      $this->load_values();
    }

    DBAccess::$db_open_flags[$this->dbaccess_id] = TRUE;
    register_shutdown_function(array($this, 'close'));
  } // end of __construct()
  
  private function load_values()
  {
    if (!$this->table_exists('_dbaccess')) {
      // bootstrap the _dbaccess table
      // put database into offline-mode so we can create and initialize the _dbaccess table.
      DBAccess::$values[$this->dbaccess_id] = array('on_line' => 'F');
      $this->create_table('_dbaccess', array(array('tag', 'varchar(255)', TRUE), array('value', 'char(1)')));
      $this->insert_into_table('_dbaccess', array('tag' => 'on_line', 'value' => 'F'));
      $this->insert_into_table('_dbaccess', array('tag' => 'database_valid', 'value' => 'F'));
      $this->insert_into_table('_dbaccess', array('tag' => 'archive_stale', 'value' => 'F'));
      $this->insert_into_table('_dbaccess', array('tag' => 'model_mismatch', 'value' => 'F'));
    }
    $tmp = $this->select_as_array('select * from _dbaccess');
    $tmp_ar = array();
    foreach ($tmp as $row) {
      $tmp_ar[$row['tag']] = $row['value'];
    }
    DBAccess::$values[$this->dbaccess_id] = $tmp_ar;
  } // end of load_values()
  
  // public function __destroy()
  // {
  //   if ($this->conn) {
  //     $this->close();
  //   }
  // } // end of __destroy()
  
  public static function available_db_engines()
  {
    return DBAccess::$available_dbs;
  } // end of available_db_engines()

  public function register_close_function()
  {
    $this->pre_close_functions[] = func_get_args();
  } // end of register_close_function()
  
  public function unregister_close_function()
  {
    $args = func_get_args();
    $func = $args[0];
    for ($i=0;$i<count($this->pre_close_functions);$i++) {
      if ($this->pre_close_functions[$i][0] == $func) {
        unset($this->pre_close_functions[$i]);
        return;
      }
    }
  } // end of unregister_close_function()

  public function __call($name, $arguments)
  {
    switch ($name) {
      case 'create_database':
      case 'drop_database':
        array_unshift($arguments, $this->connect_params);
        break;
      case 'createConnection':
        break;
      case 'closeConnection':
      case 'select_as_array':
      case 'query':
      case 'escape_string':
      case 'error':
      case 'errorP':
      case 'changes':
      case 'table_exists':
      case 'rows_in_table':
        // add database connection for everything EXCEPT connection & database requests
        if (!$this->conn) {
          throw new DBAccessException("DBAccess::{$name}() - no active database connection");
        }
        array_unshift($arguments, $this->conn);
        break;
      default:
        throw new DBAccessException("DBAccess::$name() - method not defined");
    }

    // resolve real function and call it
    $func_name = "db_{$this->db_engine}_{$name}";
    if ($this->verbose) {
      echo "DBAccess::$name(" . array_as_string($arguments) . ") [$func_name]\n";
    }
    return call_user_func_array($func_name, $arguments);
  } // end of __call()
  
  public function __toString()
  {
    ob_start(); var_dump($this->conn);
    return $this->dbaccess_id . ', conn: "' . ob_get_clean() . '"';
  } // end of __toString()
  
  // attribute implementation
  public function attribute_names()
  {
    return array_keys(DBAccess::$values[$this->dbaccess_id]);
  } // end of attribute_names()

  public function __get($name)
  {
    if (!isset(DBAccess::$values[$this->dbaccess_id][$name])) {
      throw new DBAccessException("DBAccess({$this->dbaccess_id})::__get($name): Attribute Error: $name is not defined");
    }
    return DBAccess::$values[$this->dbaccess_id][$name];
  } // end of __get()
  
  public function __set($name, $value)
  {
    if (!is_string($value)) {
      ob_start();
      var_dump($value);
      $val_str = ob_get_clean();
      throw new DBAccessException("DBAccess({$this->dbaccess_id})::__set($name, value): Attribute Assignment Error: value not string: $val_str");
    }
// DEBUGGING CODE:
if ($name == 'model_mismatch') {
  ob_start();
  echo "<div class=\"dump-output\">\nChanging model_mismatch to $value\n";
  debug_print_backtrace();
  echo "</div>\n";
  $f = fopen('/tmp/model_mismatch', 'a');
  fwrite($f, ob_get_clean());
  fclose($f);
}
    if (!isset(DBAccess::$values[$this->dbaccess_id][$name])) {
        $this->insert_into_table('_dbaccess', array('tag'=> $name, 'value' => $value));
        DBAccess::$values[$this->dbaccess_id][$name] = $value;
    } elseif (DBAccess::$values[$this->dbaccess_id][$name] != $value) {
      $this->update_table('_dbaccess', array('value' => $value), array('tag' => $name));
      DBAccess::$values[$this->dbaccess_id][$name] = $value;
    }
  } // end of __set()
  
  public function __isset($name)
  {
    return isset(DBAccess::$values[$this->dbaccess_id][$name]);
  } // end of __isset()
  
  public function __unset($name)
  {
    if (isset(DBAccess::$values[$this->dbaccess_id][$name])) {
      unset(DBAccess::$values[$this->dbaccess_id][$name]);
      $this->delete_from_table('_dbaccess', array('tag' => $name));
    }
  } // end of __unset()

  public function dump($msg = '')
  {
    $str = "$this: $msg\n";
    ob_start();
    var_dump($this->conn);
    $str .= "  conn: " . ob_get_clean();
    foreach (DBAccess::$values[$this->dbaccess_id] as $attr => $val) {
      $str .= "  $attr: $val\n";
    }
    return $str;
  } // end of dump()

  public function verbose($value = TRUE)
  {
    $this->verbose = (bool)$value;
  } // end of verbose()
  
  public function close()
  {
    if (!DBAccess::$db_open_flags[$this->dbaccess_id]) {
      return;
    }
    Globals::$flag_exceptions_on = FALSE;
    // echo "<pre>\n";
    // echo "Executing " . count($this->pre_close_functions) . " Close Functions in close()\n";
    foreach ($this->pre_close_functions as $row) {
      // ob_start();
      // echo "$this\n";
      // print_r($row[0]);
      // error_log(ob_get_clean());
      $func = array_shift($row);
      // echo "Calling " . (is_array($func) ? "{$func[0]}, {$func[1]}" : $func) . " from {$this}->close()\n";
      try {
        call_user_func_array($func, $row);
      } catch (Exception $e) {}
    }
    // flush all registered functions
    $this->pre_close_functions = array();
    // echo "</pre>\n";
    $tmp = $this->closeConnection();
    $this->conn = NULL;
    DBAccess::$db_open_flags[$this->dbaccess_id] = FALSE;
    Globals::$flag_exceptions_on = TRUE;
    return $tmp;
  } // end of close()
  
  public function connectedP()
  {
    return $this->conn ? TRUE : FALSE;
  } // end of connectedP()

  function escape_array_values($ar) {
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::escape_array_values() - no active database connection");
    }
    if (!is_array($ar)) {
      throw new DBAccessException("DBAccess::escape_array_values(ar): ar is not an array");
    }
    $tmp = array();
    foreach ($ar as $key => $value) {
      $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
      $v = $value instanceof DateTime ?  $value->format('c') : $value;
      $tmp[$key] = "'" . $this->escape_string((string)$v) . "'";
    }
    return $tmp;
  }

  function escape_where($where) {
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::escape_where() - no active database connection");
    }
    if (!$where) {
      return '';
    }
    if (is_array($where)) {
      $ar = array();
      foreach ($this->escape_array_values($where) as $key => $value) {
        $ar[] =  "$key = $value";
      }
      return " where " . implode(' and ', $ar);
    } else { 
      return preg_match('/^\s*where/i', $where) ? ' ' . $where : " where " . $where;
    }
  } // end of db_escape_where()
  
  function escape_select_list($select_list) {
    if (!$select_list) {
      return '*';
    }
    if (is_string($select_list)) {
      $select_list = preg_split('/\s*,\s*/', $select_list);
    }
    $tmp = implode(',', array_map(array($this, 'escape_string'), $select_list));
    // ob_start();
    // echo "escape_select_list(): '$tmp'\n";
    // var_dump($select_list);
    // error_log(ob_get_clean());
    return $tmp;
  }

  function update_table($tablename, $dict, $where = NULL) {
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::update_table($tablename) - no active database connection");
    }
    if ($this->on_line == 'R' && !in_array($tablename, DBAccess::$metadata_tables)) {
      return FALSE;
    }
    $ar = array();
    foreach ($this->escape_array_values($dict) as $key => $value) {
      $ar[] = "$key = $value";
    }
    $sql = $where ? "update $tablename set " . implode(',', $ar) . $this->escape_where($where)
      : "update $tablename set " . implode(',', $ar);
    return $this->query($sql);
  }

  function insert_into_table($tablename, $dict) {
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::insert_into_table() - no active database connection");
    }
    if ($this->on_line == 'R' && !in_array($tablename, DBAccess::$metadata_tables)) {
      return FALSE;
    }

    $names = array();
    $values = array();
    foreach ($this->escape_array_values($dict) as $key => $value) {
      $names[] = $key;
      $values[] = $value;
    }
    $sql = "insert into $tablename (" . implode(',',  $names) . 
      ") values (" . implode(',', $values) . ')';
    return $this->query($sql);
  }

  function delete_from_table($tablename, $where) {
    if (!$this->conn) {
      throw new DBAccessException("DBAccess::delete_from_table($tablename) - no active database connection");
    }
    if ($this->on_line == 'R' && !in_array($tablename, DBAccess::$metadata_tables)) {
      return FALSE;
    }
    $sql = "delete from $tablename " . $this->escape_where($where);
    return $this->query($sql);
  }

  public function select_from_table($tablename, $select_list = NULL, $where = NULL, $orderby = NULL)
  {
    $sql = "select " . $this->escape_select_list($select_list)
      . " from $tablename" . $this->escape_where($where) . ($orderby ? ' ' . $this->escape_string($orderby) : '');
if (FALSE)
  file_put_contents('/tmp/sql-foo', $sql . "\n", FILE_APPEND);
    return $this->select_as_array($sql);
  } // end of select_from_table()

  
  function create_table($tablename, $field_defs, $drop_first = FALSE)
  {
    // we can only drop and recreate tables when the database is offline, but we
    //  can create new tables unless it is in read-only mode
    if (($drop_first && $this->on_line != 'F') || $this->on_line == 'R') {
      throw new DBAccessException("DBAccess::create_table($tablename, ): cannot create table: DB is either not off line or is in Read Only mode");
    }
    if (function_exists($func = "db_" . $this->db_engine . "_create_table")) {
      return call_user_func($func, $this->conn, $tablename, $field_defs, $drop_first);
    }
    
    if ($this->table_exists($tablename)) {
      if ($drop_first) {
        if ($this->drop_table($tablename) === FALSE) return FALSE;
      } else {
        return FALSE;
      }
    }
    $ar = array();
    $keys = array();
    foreach ($field_defs as $row) {
      if (!is_array($row)) {
        throw new DBAccessException("DBAccess::create_table($tablename, ...): Field Definition '$row' is not an array");
      }
      @list($field_name, $field_def, $key_flag) = $row;
      if (!$field_name || !$field_def) {
        throw new DBAccessException("DBAccess::create_table($tablename, ...): Field definition is not properly defined");
      }
      $ar[] = "$field_name $field_def";
      if ($key_flag) $keys[] = $field_name;
    }
    $ar[] = "primary key (" . implode(',', $keys) . ")";
    $sql = "create table $tablename (" . implode(',', $ar) . ")";

    return $this->query($sql);
  }

  function drop_table($tablename)
  {
    if ($this->on_line != 'F') {
      return FALSE;
    }
    if (function_exists($func = "db_" . $this->db_engine . "_drop_table")) {
      return $this->table_exists($tablename)
        ? call_user_func($func, $this->conn, $tablename, $field_defs, $drop_first) : FALSE;
    } else {
      return $this->table_exists($tablename) ? $this->query("drop table $tablename") : FALSE;
    }
  }
  

/*

  public function table_exists($table_name)
  {
    return is_array($this->select_as_array("select count(*) from $table_name"));
  } // end of table_exists()
*/
}
?>
