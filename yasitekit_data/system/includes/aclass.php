<?php
/*
#doc-start
h1.  aclass.php - Base Classes for persistent data

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Back in the Generic Object game - once again.

This module defines five classes:

* AnEncryptor for encapsulating encryption and decryption operations
* AClassCmp is used to define comparision functions for arrays of AnInstance objects.
"see below":#aclasscmp for details
* AClass for defining classes which persist in tables. "see below":#aclass
* AnInstance for instances of these classes. "see below":#aninstance
* AJoin for table/class joins among classes and join instances among
AnInstance instances of these classes. This is facistly limited
to joins on keys between AClass instances. "see below":#ajoin
* AManager which encapsulates simple form processing. "see":#amanager

This implementation has modest support for:

* general character data - fixed length (char), variable but bounded length (varchar),
and unlimited (text)
* specialized character - for email addresses (email), discrete single character values (enum),
zero or more token values (set), and file - such as images, and other stuff (file).
* numbers - int and float
* dates - date data (date), time of day (time) [actually time in seconds from any arbitrary
starting point], and time stamps - including both date and time of day (datetime)

h2(#anencryptor). AnEncryptor - Encryption encapsulation

AnEncryptor instances are named objects which know how to encrypt and decrypt
strings. Each _AnEncryptor_ is defined by its key value, is retained between sessions
in a well known database table, and cached within session to reduce database access
overhead.

All encryptors use the TwoFish encryption algorithm.

It's very important that this module not be readable on the net betcause the master
key to the database is given in plain text. The keys to all AnEncryptor instances are
encrypted in the database to reduce the security problem to securing this source code.

h3. Attributes

There are _no_ public attributes.

h3. Class Methods

* AnEncryptor::get_encryptor($dbaccess, $name) - returns an AnEncryptor
instances. The _key_ is either  read from the database table '_encryptors'
OR randomly generated.
** $dbaccess - a valid DBAccess object
** $name - the AnEncryptor name - which must satisfy [a-z][_0-9a-z]&#042;
* AnEncryptor::create_table($dbaccess) - creates the encryptors table,
if it does not exist.
* AnEncryptor::php_create_string($dbaccess, $dump_dir) - writes PHP code to
a file named _encrpytor.php
which can be used to recreate the AnEncryptor table and objects in a clean
datatbase


NOTE: the AnEncryptor supports simultaneous access to multiple databases.

h3. Instance Methods

* encrypt($value) - returns a bas64 encoded string containing the encrypted form
of the value
* decrypt($value) - returns the original value. Note that _value_ must be a
a base64 encoded string returned by the _encrypt_ method of this same AnEncryptor -
or at least one created with exactly the same key.

h3. Other methods

All other methods are private.

h2(#aclasscmp). AClassCmp - comparision objects.

An AClassCmp object is a comparison function which can be used to sort
arrays of AnInstance objects. You use it by creating an instance of AClassCmp
which defines the fields involved in the comparision and whether they are
sorted in ascending or descending sequence. You then simply pass this
instance to _usort()_ - the PHP user defined array sort function. The object
will be invoked as a function, which will trap to the __invoke() magic method
and all good things happen.

The argument to the AClassCmp constructor may be:

* a function (in which case it is redundant)
* an array of field names
* a comma separated list of field names.

Each field name may be optionally suffixed by a direction indicator.
(_more detail immediately below_)

For example: new AClassCmp('foo,bar:desc,baz:asc') will create an object which
is callable and compares the fields 'foo', 'bar', and 'baz' of an array of
some sort of object. _foo_ and _baz_ ar compared in _ascending_ order, whereas
_bar_ is compared in _descending_ order - where ascending and descending have the
usual precise which is determined by the underlying colating order of the character
set.

h3. Instantiation

$foo = new AClassCmp(arg) - where _arg_ is:

* a callable - in which case $foo is simply a wrapper for the function. It is
not checked in any way.
* array - an array of strings of field/attribute names. Each may be optionally
followed by a colon (:) and a sort direction indicator.
* string - a comma separated list of field/attribute names, with optional sort indicator.

Sort indicators are:

* a, asc, + or absent - indicating _ascending_ sort
* d, desc, or - - indicating _descending_ sort

NOTE: field names must satisfy the regular expression [a-z]\w*. If they fail, an exception
is thrown. The regular expression is anchored at both ends of the field name (if you don't
know what this means, don't worry about it)

h3. Attributes

None

h3. Class Methods

None - unless you want to count the magic methods __toString() and __invoke()

h3. Instance Methods

* dump(msg = '') - returns a description of the object.

h2(#aclass). AClass - a class class

AClass is used as a base class for higher level structures. It
serves as the basic object-relational mapper.

AClass supports the following _useful_ data types.

* char - fixed length character data
* varchar - variable length, but bounded character data
* text - arbitrary length character data
* email - an email address - satisfies 
(?P<user>[a-z0-9_][-a-z0-9_]&#042;)@(?P<domain>[a-z_0-9][-a-z_0-9]&#042;(\.[a-z_0-9][-a-z_0-9]&#042;)\.[a-z]+)
and fits within varchar(255)

* blob - arbitrary data - of things like object-maintained arrays. Blobs
are always readonly and managed programatically. They are also invisible
in forms. The default 'default' value is an empty string; this which may be overridden
by explicitly setting the _default_ property. Blobs are stored in base64 formated strings

* pile - collection of key-value pairs - this allows an object to have arbitrary attributes. See
"pile":#pile for details. _piles_ are implemented as arrays and must be managed programatically.

* enum - one of a discrete set of words
* set - zero or more values from a specified set

* file - data is either empty of a path to a local file. The default forms
present a _file_ input element. The default display form presents the local file path -
which almost _*never*_ what you want.

* int - an integer
* float - a floating point number
* date, time, and datetime - PHP DateTime structure

* link - links to a single instance of a different AnInstance class
* join - join to another different AClass object instance. Suppports both 1 to 1 and
1 to many joins.
* category - 0 or more category classification values

Each class definition must have at least one unique key. Legal key types are:
'char', 'varchar', 'enum', 'set', 'email', 'date', 'time', 'datetime', and 'int'.
No other type may be used as a key.

h3. Constructor

*new AClass(cls_name, keys_list, attribute_defs, property_defs)* where

* cls_name satisfies [a-z_0-9][_a-z0-9]&#042; and is used for:
** the name of the class
** the name of the table which provides persistence.
* keys_list is either the name of the single unique key used to access the
object or an array of unique attribute names which are used as a compound
key.
* attribute_defs is an associative array key'ed by attribute name, with data type values:
** attribute names MUST satisfy [a-z][_a-z0-9]&#042; - i.e. lower case alphameric beginning
with a lower case letter.
** data types are defined below
** attribute titles are string literals which are displayed in default forms
and instance renerings.

h4(#aclass_types). Data Types

Text data types - mostly varying in the size of the string.

* char(&lt;num&gt;) - where _num_ is an integer in the range 0 through 255
* varchar(&lt;num&gt;) - as in char
* email - an email address
* text - arbitrary, more or less unlimited text

Files

* file(PATH[,public|private])- uploadable file -
**  _path_ property holds local path template for
path to file, which is relative to either the _document_root_ or _private_data_root_.
The default _path_ is 'files/{{$attr}}, where _$attr_ is the name of the field, so that
the path ends up being 'files/{field-name}'. i.e. 'file/value-of-field-name',
which probably _not_ what you want. You should replace it with something
like: 'files/{some-unique-attribute-name}'.
Because _path_ is in a "property":#aclass_properties, it is easy to modify
the value using _put_prop()_.
** the second parameter is optional and, if present, _must_ be either 'public' or  'private'.
If absent, the path root defaults to 'public' - which is _document_root_. If set to
'private', then the file path is rooted in _private_data_root_.

Enumearated (essentially discrete) data:

* enum(VALUES) - where _values_ is a comma separated list of words. For example:
_enum(Y,N,Maybe)_
* set(VALUES) - where _values_ is a comma separated list of words

Scalar data - one value per entity and fairly simple.

* int
* float or float(DIGIT) - The second form sets the precision following the
decimal point to _digit_ digits
* date - satisfies dd-mm-yyyy
* time - satisfies hh:mm:ss
* datetime satisfies dd-mm-yyy hh:mm:ss

Related Data: The following relate the record to other entities. The _join_ data type
is a 'convenient' interface to AJoin objects - which create and manage
relations between AClass objects. The _link_ data type is a simplified
_join_. It differs from the _join_ type in that it does _not_ use an
AJoin table, can only link to a single instance of the _other_ object,
the other object cannot have a compound key, and the actual value of the
_key_ value of the other object is stored in the _link_ variable.
_category_ data is an entirely different animal - see the discussion below
and in "Category.php":/doc.d/system-objects/Category.html.

* link(Object.display-field-name) - where Object is the name of an
AClass object with a simple key (only one key field). _display-field-name_
is the name of the attribute to display in an HTML Select element. This
creates a link to a single instance of _Object_.
* join(Object.display-field-name[,multiple]) - where Object is the name of an AClass object.
_display-field-name_ is the name of the field to display in the _select_
element option list. If the optional _multiple_ flag is set, then this is a
1 to N join, otherwise only one element from _Object_ will be joined to _this_
*(#category) category(category_root) - comma separated list of 0 or more Category paths.
Categories are restricted to descendents of the specified category root
The _category root_ is a comma separated list of Category paths
See "Category.php":/doc.d/system-objects/Category.html
for a discussion of category objects and paths.
Here are some examples:
** _foo_ - the Category named _foo_ which is a child of root
** _foo_bar_baz_ - the Category named _baz_ which is a descendent of both 'root'
and _foo_ and is a direct child of _bar_.

Blobs

* _blob_ data is arbitrary, binary data. Blobs are not displayed in the default
display and form methods. The support is confined to magic set/get/etc methods
and persistent storage. Blobs are stored as base64 strings and may be encrypted.

p(#pile). Piles

A _pile_ is an unordered collection of key-value pairs. In other words: it's an
associative array without all the nice features. This allows dynamic definition
of collections of loosely related clumps of data. Uses include extensible
collections of HTML element attributes, dynamic and user manageable titling, etc.
It's kind of a mini-unstructured data cache.

_piles_ are managed using "pile methods":#pile_methods. It is illegal to assign
a value to a _pile_ field. Getting a _pile_ will return a string containing all
of the key-value pairs, ordered in ascending ASCII order of _keys_.

NOTES:

* attributes in the _pile_ are not displayed in either the default display or form methods.
* attributes in the _pile_ are not stored separately in the database. They are not searchable
or selectable individually.
* the contents of the _pile_ is stored as a serialized array and may be encrypted.

h3. Attributes

* attribute_defs - array - the array used to define all the attributes
for this AClass instance.
* attribute_dictionary - associative array - maps the field names of this
AClass instance into their respective _attribute_defs_ definition row.
* attribute_names - array - array of attribute names
* attribute_token_patterns - array - of attribute token patterns useable for
replacement in templates. Each one looks like '/{name}/', where _name_ is
the name of an attribute.
* cls_name - string - class name of the AClass instance. It is the job of
the class file to both create the AClass instance and define the class as
an extention of AnInstance.
* default_title - strings - read only - returns the default display string
for this instance, if defined. If not defined, returns the key values.
* enctype - string - used in creating forms. It is either 'application/x-www-form-urlencoded'
or, if there are _file_ type fields, 'multipart/form-data'.
* hash - an MD5 hash of the (field name, data type) pairs created
in an order independent manner. Used to determine if field types have changed.
* keys_list - array - array of the names of attributes used as keys
* tablename - string - the name of the table used to save instances of this
class.
* value_names - array - array of all attributes which are NOT keys

h3. Class Methods

* match_datatype($str) - returns a _match array_ (see PHP function preg_match()) if
_$str_ is a legal data type declaration, else FALSE.
* define_class($cls_name, $keys_list, $attribute_defs) - creates a class
definition if it does NOT exist. Else, throws exception
* existsP($cls_name) - returns TRUE if class definition exists in class directory,
else FALSE
* attribute_existsP($cls_name, $attr) - returns TRUE if _$cls_name_ is a defined class
and has the attribute _$attr_. Else, returns FALSE.
* get_class_instance($cls_name) - returns class definition instance if it exists,
else throws exception
* get_class_directory() - returns the array of defined classes.
* create_aclass_hashes_table($dbaccess, $drop_first = FALSE) - creates the _aclass_hashes
table. Drops if it exists if $drop_first is TRUE, else attempts to create table and
fails silently if it is already there.
* create_all_tables($drop_first = FALSE, $warn_on_drop_error = TRUE) - creates
all currently defined tables by calling _create_table()_ on each object in
_class_directory_.
* php_defs_ar(aclass_object_names) - returns an array of AClass object attribute_defs,
key_lists, and attribute_properties under the keys 'defs', 'keys', and 'props'.
* php_create_string(dbaccess, aclass_object_names, $dump_dir) - write PHP code to a file
named _aclasses.php
which can be used to recreate all the classes which are derived from AClass.
_aclass_object_names_ is an array of object names.

h3. Instance Methods

Public Instance methods:

Support Instance methods are used to support instances of the object.
This is just a way to keep the code which implements object instances
in one place.

* aclass_hash_values() - returns a two element array: array($hash, $key_hash).
Both are MD5 hashes which are used to detect changes to the definitions of
attributes. _$hash_ is based on _all_ attribute definitions, whereas _$key_hash_
uses only the key defintions. A change to an attribute is allowed, but not
changes to the keys - thus a change to _$hash_ w/o changing _$key_hash_ generates
a Model Mismatch event, whereas a change to _$key_hash_ is an Illgal Edit.
* create_table($dbaccess, $drop_first = FALSE, $warn_on_drop_error = FALSE)
- attempts to create the table for this instance.
As a side effect, it also creates an instance of
the Parameters class for this class and inserts the _hash_ value in the *_aclasses*
table [creating the _aclasses table if required].
If table creation fails, the action depends on the value of _drop_first_.
** if _drop_first_ is FALSE, then an exception is thrown. 
** If _drop_first_ is TRUE, then attempts to drop the table prior to creating.
** If the table drop fails, then
*** if $warn_on_drop_error === TRUE, then a warning is echoed;
*** if == 'ignore', then nothing happens
*** otherwise an AClassException is thrown.
* obj_to_db($attr, $value, $encryptor) - returns a string suitable to store in the database
consistent with the data type of _attr_ and containing the data in _value_.
_encryptor_ must be an AnEncryptor object - if any fields are encrypted.
* db_to_obj($attr, $value, $encryptor) - returns the object representation of the value
of _attr_ in the database.
* dump($msg = NULL) - returns a string displaying pertenent information about the
AClass instance. _msg_ is printed if not NULL.

h4(#aclass_props). Properties

Metadata for fields are maintained in property lists. Property values
can be anything, but at present only strings, booleans, numbers, and
arrays are used. Some properties are always present, while some relate
only to the type of field.

These are managed by four functions.

* prop_name_exists($prop_name) - returns TRUE if _prop_name_ is a legal
property name, else FALSE. Used for testing
* put_prop($name, $prop_name, $value = TRUE) - defines the property _prop_name_
for field _name_. If _value_ is omitted, then _prop_name_ becomes a boolean with value TRUE.
* append_to_prop($name, $prop_name, $value) - appends STRING values to a STRING valued
property. If the property exists and is boolean, then throws exception. If exists then
is treated as a string and a space followed by _$value_ are appended. Otherwise _$value_
is assigned to the property.
* has_prop($name, $prop_name) - returns TRUE if _name_ has property _prop_name_, else
FALSE.
* get_prop($name, $prop_name) - returns value of _prop_name_ or FALSE if it is
not defined.

Legal properties are:

* category_root - string - used by _category_ data types to store the
root category.
* category_deep - boolean - Controls depth of category options list. If TRUE,
the all descendents are included in list; if FALSE then only children.
* default - string - optional - default value for this field. Used only
if not defined otherwise. If not defined, then field is set to NULL.
* display_attributes - string - optional - attributes to add to display
html segment
* display_classes - string - optional - classes to add to display html
segment
* display_html - string - always present - html used to display the field
* encrypt - boolean - present only if field is to be encrypted
* enums - array - defined ONLY for _enum_ and _set_ data types
* filter - string - content of a regular expression which text, char, varchar data must
satisfy in order to properly set a value. This expression should NOT contain
any anchors or starting and ending delimiters. They will be added automatically.
* format - string - defined only for int and floats
* form_attributes - string - optional - attributes to add to form
html segment
* form_classes - string - optional - classes to add to form html segment
* form_html - string - always present - html used to edit field value
* form_html_func - string - optional - name of instance method to call
to create _form_html_ value. Supports dynamic creation of input
elements. See "below":#aninstance_form_html_func for details of how to
write a function and an example.
* immutable - boolean - present only for immutable fields. Immutable fields
may only be initialized. NOTE: all key fields are _immutable_
* invisible - boolean - present for fields which are NOT displayed
* joins and join_display_field - these properies are _only_ used for
_join_ data types and are managed automatically by the AClass and AnInstance
objects. It is a _bad idea_ to manage them directly.
** joins - string - present only for 'link' and 'join' field types.
Content is the AClass name of the joined field. Note that this ONLY supports
1-1 and 1-N relationships and only in the object which on the '1' side.
** join_display_field - string - present only for 'join' field types -
field name from joined object to display in _select_ element.
* key - boolean - present if this is a key field
* multiple - boolean - only applies to _join_ data types and indicates a
1 to N relationship.
* path - string - defined only for _file_ types. May contain zero or more
substitution values of the form {field-name}. This is the same substitution
mechanism used for HTML rendering.
* precision - int - only definable for floats. must be an integer between 0 and 9,
inclusive. Is set automatically with the float(DIGIT) declaration form.
* private - boolean - if present, then is not displayed in default form, render,
or dump - automatically sets _invisible_
* public - boolean - if present, then is displayable via web service interface
* readonly - boolean - if present, value cannot be modified via a form
* required - boolean - optional - used to indict field must be filled in
* sql_name - string - always present - used in constructing joins. Consists
of <tablename>.<fieldname>.
* sql_type - string - always present - used in create table statement. Generally
differs from _type_ because _type_ is the field type and _sql_type_ defines
the database column used to contain the values of the field
* title - string - always present - title used in HTML when displaying or
editing this field
* type - string - always present - the AClass field type. "see above":#aclass_types
* unique - must have unique value in db - this is distinct simple and compound key uniqueness
* where - string - used in _join_ and _link_ data types. Allows a _where_ constraint
for joining values. Format is as an SQL WHERE clause. Allows {field-name} interpolations.
*IMPORTANT NOTE:* this _only_ works for _select_elt_join_list() which returns
an HTML _select_ element for the join. Assignment, _add_to_join()_ and _delete_from_join()_
ignore the _where_ clause.
* width - int - not always present. See the code

h2(#aninstance). AnInstance - a class for instances

An AnInstance object is the base class for implementing AClass objects.
It wraps a single instance of AClass - which it uses to provide support
for object / database mapping, consistency checking, etc.

While AnInstance is not an abstract class so it should never be used directly.
It should always be subclassed.

Derived classes of AnInstance should _not_ be extended.

* "Attributes":#aninstance_attributes
* "Class Methods":#aninstance_class_methods
* "Instance Methods":#aninstance_instance_methods
* "Properties"#aninstance_props

h3. Creating Subclasses

Subclass as follows:

# create the object definition using AClass::define_class()
# subclass AnInstance using the same _cls_name_ used in _AClass::define_class()_
# create object instances of the new class using the optional _key_values_ or
_attr_values_.

Here's an example from the test driver:

<pre>
 // define the attributes of _test_class_ using an array of tripples: attribute name,
 //   attribute type, and attribute title
$test_class_def = array(
  array("text_var", "text", "Text"),
  array("char_var","char(10)", "Char"),
  array("varchar_var", "varchar(200)", "Varchar"),
  array("enum_var", "enum(Y,N)", "Yes or No"),
  array("email_var", "email", "Email"),
  array("file_var", "file", "File Variable"),
  array("int_var", "int", "Int"),
  array("float_var", "float", "Float"),
  array("date_var", "date", "Date"),
  array("time_var", "time", "Time"),
  array("datetime_var", "datetime", "DateTime"),
  );
</pre>
<pre>
 // create the class definition as an instance of AClass
AClass::define_class('TestClass', 'char', $test_class_def);
</pre>
<pre>
 // subclass AnInstance. This is boilerplate. The only thing which will
 //  change is the argument 'TestClass' in parent::__construct() - that
 //  will change to the name of your class
</pre>
<pre>
NOTE: dbaccess is an instance of DBAccess - which will save the data
</pre>
<pre>
class TestClass extends AnInstance {
  public function __construct($dbaccess, $attr_values = NULL) {
    parent::__construct('TestClass', $dbaccess, $attr_values);
  } // end of __construct()
}
</pre>
<pre>
 // create instances as needed. This creates an empty instance.
$test_instance = new TestClass($dbaccess);
</pre>
<pre>
 // create an instance which attempts to initialize from the database where
 //  the value of _char_ [the key defined in _define_class()_] has the value _foo_
$t2 = new TestClass($dbaccess, array('char' => 'foo'));
</pre>
<pre>
 // create an instance which initializes the values using the values in the
 //  values array.
$t3 = new TestClass($dbaccess, array('char' => 'foo', ...));
</pre>

h3. Accessing Subclasses

Each AnInstance subclass is defined by an AClass instance. All AClass instances
must define one or more key fields, so AnInstance subclasses are uniquely
specified by their AClass class name and the values of their key fields.

For various reasons of complexity and performance (and personal laziness),
it is illegal to change the value of an key field, once it has been defined.

h3. Integrity

AnInstance subclasses implicitly maintain two copies of the data they contain:
* the object copy which is referenced by the software
* the database copy which is referenced and refreshed by the _load()_ and _save()_
methods. [NOTE: _load()_ is automatically and implicitly called when an instance
is created with _key_values_ specified].

This poses a problem if the same object instance is instantiated using the same
class type and key values. AnInstance does not address this - except to say
_caveat emptor_.

h3(#anininstance_attributes). Attributes

All defined attributes for the class are available as well as the following
read-only attributes from the AClass instance:

* cls_name - the name of the AClass instance - used to identify the class and
also for the database table used for persistent storage
* attribute_names - array of attribute names which is in the same order as the
_attribute_defs_ array used to create the class.
* attribute_token_patterns - an associative array mapping attribute names to Perl
regular expressions of the form /{attribute-name}/. These are used to instantiate
template strings which contain strings of the same form.
* enctype - either 'application/x-www-form-urlencoded' or, if there are _file_ type
fields, 'multipart/form-data'.
* keys_list - list of the names of the key fields
* tablename - name of the table in the database which contains data for the instance
* value_names - names of all the non-key attributes.
* dbaccess - the DBAccess object which holds this AnInstance's data
* record_in_db - boolean - TRUE if record is in database, else FALSE

IMPORTANT NOTE: joins between object are supported for 1-1 and 1-N joins. Use
the 'join' data type for the left side. Direct assignments to the
'join' attributes invoke _assign_join_value()_ to update 'join' fields.
Consequently, the _value_ assigned must conform to the rules of _assign_join_value()_:
it must be an instance of the joined object, an array of key-value pairs, or
an encoded version of this array (as generated by _encode_key_values()_).
Retrieving the value of a 'join' field takes one of two forms:

* $this->field_name - returns an array of all foreign objects which are joined
to _this_ via _field_name_. This is usually what you want for programmatic access.
For display access, you will probably want _join_value_of()_.
* $this->join_value_of(field_name) - which returns the value of the 'join_display_field'
field of the foreign object which is joined to. This is usually what you want to
display, but not for programmatic access.

h3(#aninstance_class_methods). Class Methods

new AnInstance(cls_name, dbaccess, attr = array()) creates a new AnInstance object.
Generally _attr_ must be an array of (attribute, value) pairs. As a miminum, it should
contain values for the key attributes of the class. _As a convenience_, if the
class has a single key, then _attr_ may be a single value which will be implicitly
matched against the single class key.

* existsP(cls_name, dbaccess, attr_values = array()) - returns TRUE if specified
instance exists in _dbaccess_. Returns FALSE if not or if keys are not sufficiently
specified. As, in the constructor, _attr_values_ may be a string if, and only if,
the class has a single, rather than mulitple, keys.
* static_decode_key_values($str) - returns a key value array from a url encoded,
serialize string OR '' if $str is false in some sense.
* php_create_string($dbaccess, $aclass_object_names, $dump_dir)
- write PHP code which can be used to recreate
all the AnInstance objects AND all objects in the _aclass_object_names_ list which
define a static function _php_create_string(dbaccess, dump_dir)_
in the database to a 'dump' file.
_$dbaccess_ is a DBAccess instance. _$aclass_object_names_ is an array
of object names to dump.
_$dump_dir_ is the path to a diretory in which to write dump files. Each dump file
is named _object-name_.dump, where _object-name_ is the name of the object being
dumped.

h3(#aninstance_instance_methods). Instance Methods

h4. Key field support:

* instance_existsP(attr_ar) - array of attributes or value of single key or whatever.
Returns TRUE if there are one or more instances in the database which satisfy _attr_ar_
as a _where_ clause. [This is a convenience function which calls Classname::existsP(...)].
* key_values_complete() - returns TRUE if all key values are completely defined,
else FALSE
* key_value() - returns either:
** the value of the key, if the object has a simple key
** the value of encode_key_values() (below)
* compute_key_values() - returns an associative array where the keys are key field
names for this AnInstance and values are the current values of those fields.
Does NOT check _key_values_complete()_.
* encode_key_values() - returns urlencoded, serialized version of _compute_key_values()_.
Used in forms and text fields which need to reference another object.
* decode_key_values($str) - returns:
** $str if _$str_ is not a urlencoded array - that is: the result of _encode_key_values()_
** associative array produced by privious call
to _encode_key_values()_. This does not reference _this_ object, so it can
be used to instantiate foreign objects. This _actually_ calls AnInstance::static_decode_key_values().

h4. Link Support

Link attributes hold the value of the key of the instance they are linked to. Thus,
$foo->link_name returns the key, not the object. To get the object, use _link_value_of()_.

* link_value_of($attr) - returns instance of object linked to or FALSE if the link
is not defined.

h4. Join Support

NOTE: assigned values for _join_ files can be an instance of an object
derived from AnInstance, an array of key:value pairs which uniquely specify
the object, a single key value for objects which do not have compound keys, or
an array containing a mixture of these values.
The object type MUST match the 'joins' property of _attr_. The assignment
uses the AJoin::update_join() instance method: thus the _join_ will be changed
to contain _exactly_ the object(s) listed in the assignment. In other words,
this is a _destructive_ assignment. Use _add_to_join()_ and _delete_from_join()_
(below) to add and delete without disturbing already
existing join relationships.

All joined objects can be retrieved by referencing the attribute - as in $obj->foo.
[this returns an array of all currently joined foreign objects]

* join_value_of($attr) - returns a string created by selecting all of the foreign object joined to
and extracting their _join_display_field_ values. The values are concatenated
together with comma (,) delimiters.
The string may be empty.
* __get() - simply referencing the attribute returns an array of all joined objects.
* add_to_join(value(s)) - addes the indicated objects to the join. _values_ may be:
** a single instance of the foreign object
** attribute values which can be used to instantiate an instance of the object -
key field array or key value (if single key for foreign object)
** a mixed array of both.
* delete_from_join(value(s)) - similar to _add_to_join()_, except it deletes rather
than add. Rules for _values_ are the same.

h4. Pile Support

Assigning a value to a _pile_ field throws an exception.

Accessing a _pile_ variable returns an associative array containing all the key-value pairs.

isset(_pile_) always returns TRUE.

unset(a _pile_) throws an exception.

* pile_keys($attr) - returns an unsorted array of the _keys_ in pile _$attr_
* pile_put($attr, $key, $value) - creates or overwrites the key-value pair in pile
_$attr_.
* pile_get($attr, $key) - returns the _value_ of _key_ or FALSE if it is not defined.

h4. Category Support

* delete_category($attr, $category_path) - The specified path is
deleted from _$attr_ if it is already included. If not included, it
is silently ignored. If _$category_path_ is not a child of _category_root_,
an exception is thrown.
* add_category($attr, $category_path) - The specified path is added
to _$attr_ or - if it is not a legal path or not a child of _category_root_,
an exception is thrown.
* category_paths_of($attr) - returns an array of all Category paths
in _$attr_.
* default_category($attr) - returns the _default_ category for _$attr_ - which
is the first category value in _$attr_. This makes sense because all category
values are kept in a sorted order: from root outward and at the same
heirarchic level in the category tree, in ASCII sort order.
This is not the same as the sibling order maintained for sibling categories
for two reasons: (1) efficiency and (2) this sort order does not reflect
the tree heirarchy inasmuch as _category_ data may contain data from disjoint
Category subpaths.
* category_objects_of($attr) - returns all of the Category objects in
_$attr_.
* select_objects_in_category($attr, $subpath, $other_class) - returns a (possiblye
empty) array of objects of class _$other_class_ which are in category _$subpath_.
The array will be empty of _$subpath_ is not a subpath of any category in _$attr_
or if there are no _$other_class_ instances in _$subpath_.
* delete_category_references($category_path) - finds all _category_ type attributes and
deletes all categories which either are _$category_path_ or are its descendents
from each one. NOTE: this is used by the Category::delete() method and should NOT
be used in normal coding. This is also a little dangerous because it works with
NEW instances of all AnInstance objects which are in the category to delete rather
than existing, in-memory instances. As a result, previously existing in-memory
instances will lose synchronization with the database. This will NOT be fixed
because:
** it's a low frequency event
** you've been warned
** it requires implementing cached AnInstance objects with a private constructor
and alternative 'get_aninstance' method, so it screws up subclassing and it's
just not worth it.

h4. Object Primatives

* equal(other) - returns TRUE if all attributes of _this_ and _other_ are the same.
* asString($attribute-name) - returns the value of the attribute as a string
* load($where) - attempts to load the record pointed to by the $where clause.
* save() - writes the object to the data store.
* dirtyP() - returns TRUE if this object has been modified since creation, else FALSE.
* mark_saved() - marks instance as not needing to be saved. Use with care or
don't use at all.
* mark_dirty() - marks instance as needing to be saved. Use with care (really for
internal use)
* delete() - deletes this object and all associated AJoin instances.
* get_objects_where($where, $orderby = NULL) - returns an array of whatever-this-object-is which
are in the database and satisfy _where_. NOTE: $where == NULL is OK. The optional
_orderby_ is a string (which is escaped) denoting the ordering.

h4. Generic Display and Forms + Support

* interpolate_string($str, $reset_flag = FALSE) - *This is the primative you want when building your own _render()_ method for taylored objects.*
It interepolates $str by substituting
all '{attribute-name}' and '{class-name.attribute-name}' patterns with the current attribute values.
If $reset_flag is TRUE OR this is the first time it has been called, the interpolation arrays are rebuilt.
The default is generally OK unless an attribute has been changed.
* file_abs_path($attr, $reset_interpolation_flag = TRUE) - *Use this INSTEAD of _interpolate_string()_ for _file_ attribute paths.*
It is similar to _interpolate_string()_
except that it returns the absolute path to a _file_ attribute. The flag _$reset_interpolation_flag_
is passed directly toe _interpolate_string()_
* render($top = NULL, $bottom = NULL) - returns an unordered list of the fields
of _this_ object, listed in the order defined. _$top_ and _$bottom_ are strings
which are prepended and appended to the list, so you can wrap this ugly output
in a &lt;div&gt; - or whatever else you want. This is basically a place to start.
* render_file($path) - returns the contents of the file at _$path_ after passing
through _interpolate_string()_ for _$this_. If _$path_ does not exist, is not a file,
or is not readable, then returns _this->render()_
* render_include($fname) - returns the contents of the file at _$file_ after passing
through _interpolate_string()_ for _$this_. If _$file_ cannot be included, then
returns _this->render()_.
* select_elt_join_list($attr, $classes = NULL, $attributes = NULL) -
returns an HTML Select element for a joined field. _$attr_ must have the 'joins'
property set to a defined AClass object and must have the 'join_display_field'
set to the name of a valid field in the foreign object.
** $attr - the foreign key field in this AClass instance
** $display_name - name of foreign object display name field
** $classes - names of HTML classes to stuff into a class= attribute
** $attributes - additional attribute values to stuff in the _select_ element start tag.
* form($form_action = NULL, $top_half = NULL, $bottom_half = NULL, $actions = NULL) -
similar as _render()_
except it defaults to the default form template.
The first parameter is the HTML form element _action_ attribute. If NULL (the default)
we use $_SERVER['REQUEST_URI'] - which is the URI which got us to the form.
The rest of the parameters make it possible to
modify the form by adding list items to the top and bottom of the form and to
re-define the _submit_ buttons. *NOTE* both _top_half_ and _bottom_half_ can be
callables which generate text.
** _top_half_ and _bottom_half_ can be either something which can be converted
to a string OR a callable which returns something appropriate.
** top_half - this is literally inserted after the <form> start tag
** bottom_half - this is litterally inserted immediately before the list
element which defines the _submit_ buttons
** actions - this is an array of submit button values. If NULL, it defaults
to array('Save', 'Cancel', 'Delete').
* process_form(rc) - _rc_ is request cleaner object. Automatically saves.

h4. Diagnostic Support

* dump(msg = NULL) - returns a string containing a dump of the object instance.

h3(#aninstance_props). Properties

AnInstance instances hold properties and also provide read-only
access to AClass properties.

Legal property keys are:

bq. set - boolean - present if the corresponding field in this instance has
been set. Otherwise, not. Test using _has_prop()_

bq. display_html, form_html, and invisible - optional properties which may
override the AClass properties they correspond to. "see":#aclass_props

bq. default_title  - optional - declares this attribute as an element
of the default display field which will be used when one is not
specifically declared. See Category.php for an example of use.

The following instance methods manage properties. NOTE that these
methods coordinate property values properly between instance and the
underling AClass, so the Class properties should _never_ be accessed
directly.

* put_prop($name, $prop_name, $value = TRUE) - defines the property _prop_name_
for field _name_ for _this_ instance.
If _value_ is omitted, then _prop_name_ becomes a boolean with value TRUE.
* append_to_prop($name, $prop_name, $value) - appends STRING values to a STRING valued
property. If the property exists and is boolean, then throws exception. If exists then
is treated as a string and a space followed by _$value_ are appended. Otherwise _$value_
is assigned to the property.
* has_prop($name, $prop_name) - returns TRUE if _name_ has property _prop_name_
in either the instance or underlieing class, else FALSE.
* get_prop($name, $prop_name) - if _prop_name_ is defined for _name_ in either
the instance or underlieing AClass then returns value of _prop_name_, else FALSE
* del_prop($name, $prop_name) - removes the property from the instance.
NOTE: only instance properties can be removed.

h3(#aninstance_form_html_func). Writing a _form_html_func_ function

The _form_html_func_ is an instance method with no arguments. It must
return a string containing a legal HTML element which can be used to
input a legal field value. This is useful to constrain a _varchar_ field
to a selection set which is created dynamically.

The string MUST also contain the following template symbols:

* '{' form_attributes '}' - which will be filled in by the _form_attributes_ property.
*NOTE*: the '{' construction is necessary to 'fool' textile. Just enclose the word
in curly braces w/o the extra space and quote marks
* '{' form_classes '}' - which defines the classes for this form element

As an example, here is the code from Category.php which generates
the HTML _select_ element which replaces the default _input type=text_ element:

<pre>
protected function parent_form_func()
{
&nbsp;&nbsp;  $tmp_ar = $this->get_objects_where(NULL, 'order by parent,name');
&nbsp;&nbsp;  $str = "&lt;select name=\"parent\" class=\"{form_classes}\" {form_attributes}&gt;\n";
&nbsp;&nbsp;  $str .= "  &lt;option value=\"\"&gt;Top Level []&lt;/option&gt;\n";
&nbsp;&nbsp;  foreach ($tmp_ar as $obj) {
&nbsp;&nbsp;&nbsp;&nbsp;    $str .= "  &lt;option value=\"{$obj->path}\"&gt;$obj->title [$obj->path]&lt;/option&gt;\n";
&nbsp;&nbsp;  }
&nbsp;&nbsp;  return $str . "&lt;/select&gt;\n";
} // end of parent_form_func()
</pre>

*NOTE:* the 'protected' visibility. Private will not work, but, of course, public will
also.

h2(#ajoin). AJoin

*AJoin* objects model a simple join between two AClass instances.

All joins are implemented using the _keys_list_ fields in both classes.
Join objects are named for the objects they join, using a 'left'_'right'.
Which object is which is arbitrary, but determined when the join is created.

h3. Attributes

* left_class_name - name of left class AClass element of AJoin
* right_class_name - name of right class AClass element of AJoin
* tablename - name of table for the AJoin instance

h3. Class Methods

h4. AJoin element

These class methods allow instances to create join instances without
directly messing with the AJoin object. They all take an instance
of an object first parameter. Subsequent parameters depend on the method.

* AJoin::ajc_select_objects($controlling_instance, $joined_class) - returns all
_$joined_class_ instances which are joined to _controlling_instance_.
* AJoin::ajc_add_to_join($controlling_instance, $joining_instance) -
adds _$joining_instance_ to the join for _$controlling_instance_ and the
class of _$joining_instance_.
* AJoin::ajc_delete_from_join($controlling_instance, $joined_instance) -
deletes _$joined_instance_ from the join for _$controlling_instance_
and _$joined_instance_
* AJoin::ajc_update_join($controlling_instance, $new_join_list) -
forces the joins for _$controlling_instance_ to only include the
values of joining objects in _$new_join_list_. NOTE: _$new_join_list_
may be a mixture of objects - multiple joins may processed in a single
call.

h4. AJoin management methods

These methods work with retrieve or destroy AJoin objects themselves

* get_ajoin($dbaccess, left_name, right_name) - where _left_name_ and _right_name_
are the names of AClass classes
* destroy_all_joins(dbaccess) - does just what it says. DON'T DO THIS UNLESS
YOU NEED TO REBUILD EVERYTHING
* get_ajoins_for($aninstance) - returns an array of all AJoin objects defined for the
supplied _aninstance_ or FALSE

h4. Archive Support

* php_create_string(dbaccess, $dump_dir) - writes PHP code to a file named _join_tables.php
which will recreate the join tables in the database. _dbaccess_ is a DBAccess instance.



h3. Instance Methods

* select_joined_objects(controlling_instance) - 
uses the key values from _controlling_instance_ to select all of the
_other_ objects in the join.returns a single array of the object instances joined to
_controlling_instance_. NOTE: the array may be empty.
** name of _other_ AClass
** array of associative arrays. Each associative array contains the
values of one object of the _other_ class. Keys are attribute names.
It contains the values for all the _key_ attributes plus any other
attributes in _additional_attribute_list_. _additional_attribute_list_
may be either a string containing a comma separated list of attribute
names, a simple array of attribute names or the name of a single,
or NULL.

* add_to_join($left, $right) - adds the objects to the join. NOTE:
the order of _left_ and _right_ doesn't matter - even though it looks
like it should.
* delete_from_join(left, right) - deletes the objects from the join.
NOTE: see _add_to_join()_ NOTE
* delete_joins_for($aninstance) - deletes all join entries for the supplied
_aninstance_ for this AJoin instance.
* update_join($controlling_instance, $new_join_list) - adds and deletes the join
table so that all controlling instance is joined to all items in new_join_list,
and nothing else. _$new_join_list_ is a list of join objects, key value arrays,
or urlencoded key arrays or a mix of any and all three.
* in_joinP(left, right) - returns TRUE if _left_ and _right_ are in the join,
else FALSE;
* dump(msg) - dumps out the contents of the join instance.

h2(#amanager). AManager

The *AManager* class implements a simple html form for maintaining
any AnInstance object which is created. It supplies a _select_ element list
which allows any existing AnInstance which currently exists or to create
a new one.

*AManager* is designed to be subclassed by each AnInstance extension.

Typically, for the Foo, where you want to view the _title_ field
in the drop-down list - ordered by increasing value of _title_,
you would do something like:

<pre>
  class Foo extends AnInstance {
    . . .
  }
  
  class FooManager extends AManager {
    public function __construct($dbacces)
    {
      parent::__construct($dbaccess, 'Foo', 'title', array('orderby' => 'title'));
    } // end of __construct()
  }
  
  // then in use, you would do:
  $form = new FooManager(Globals::$dbaccess);
  $form->render_form(Globals::$rc);
</pre>

Of course, it is possible to instantiate AManager directly to provide
alternate means of accessing the objects

h3. Attributes

None of interest

h3. Class Methods

Just the constructor

* __construct($dbaccess, $cls_name, $display_field_names, $options = array()) - 
generally only called in the constructor of an AManager extension
** dbaccess - a DBAccess instance
** cls_name - string - name of class being managed
** display_field_names - string - comma separated list of field names used to
construct the display and also used in the order by clause of the _select_
statement.
** options - array - array of options which can be used to control the operation
of the manager
*** form_action - string OR {form_action} - URL to _this_ form.
*** orderby - string OR NULL - literal SQL 'order by' phrase used to order the _select_
element options. Typically something like "order by $display_field_names". If
false in any sense, then the default ordering is applied.
*** expose_select - boolean - default is TRUE. Determines if the _select_ list is
exposed when the manager's _render_form()_ method is called.


h3. Instance Methods

* select_element($select = NULL) - Not normally called directly
returns an HTML _select_ element
as a string. If _$select_ is not NULL, it is assumed to be an AnInstance
derived object. It's keys are then compared to each key for each object
and the matching object is selected.
* render_form($rc, $form_top = NULL, $form_bottom = NULL, $actions = NULL) - pass it a request cleaner,
and it displays the 'select an object' form. The parameters _form_top_, _form_bottom_ and
_actions_ are passed directly through to the _form()_ method of the AnInstance instance.
If an object or create-new-object is selected, then the content of the
object is displayed in an edit form.

#end-doc
*/

// global variables
require_once('Parameters.php');
// guard to protect from looping - which shouldn't occur anyway - this is just paranoia
require_once('Category.php');
require_once('ObjectInfo.php');

// end global variables

// class definitions

// Encrypter
class AnEncryptorException extends Exception {}

class AnEncryptor {
  private static $master_key = FALSE;
  private static $master_key_size = FALSE;
  private static $master_iv_len = NULL;
  private static $_cache = array();
  private static $field_definitions = array(
    array('name', 'varchar(255)', TRUE),
    array('key_size', 'int'),
    array('iv_len', 'int'),
    array('key_value', 'text'));
  
  private $name = NULL;
  private $dbaccess = NULL;
  private $key_size = NULL;
  private $key_value = NULL;
  private $iv_len = NULL;
  private $in_database = FALSE;

  // static methods

  private function __construct($dbaccess, $name, $key_value, $in_database)
  {
    $this->name = $name;
    $this->dbaccess = $dbaccess;
    $this->key_size = mcrypt_get_key_size('twofish', 'ofb');
    if (!$key_value) {
      $key_value = '';
      for ($i=0;$i<$this->key_size;$i++) {
        $key_value .= chr(rand(0,255));
      }
    }
    $this->key_value = substr($key_value, 0, $this->key_size);
    $this->iv_len = mcrypt_get_iv_size('twofish', 'ofb');
    if (!($this->in_database = $in_database))
      $this->save();
  } // end of __construct()
  
  public static function flush_cache()
  {
    AnEncryptor::$_cache = array();
  } // end of flush_cache()

  private static function load_cache($dbaccess)
  {
    if (!AnEncryptor::$master_key) {
      // Create the IV and determine the keysize length
      AnEncryptor::$master_key_size =
        $master_key_size = mcrypt_get_key_size('twofish', 'ofb');
      AnEncryptor::$master_iv_len = mcrypt_get_iv_size('twofish', 'ofb');

      // AnEncryptor::$master_key = substr('(eRl1X1&/v;lN{Mu%nBo5>b]UH8l%14,uex(@,.O$H=RFjJK[k<o,!OaHgOL', 0, $master_key_size);
      AnEncryptor::$master_key = substr(urldecode(Globals::$encryption_key), 0, $master_key_size);
    }
    // echo "'" . AnEncryptor::$master_key . "'\n";
    // echo '\'(eRl1X1&/v;lN{Mu%nBo5>b]UH8l%14,uex(@,.O$H=RFjJK[k<o,!OaHgOL\'' . "\n";
    // echo "'" . Globals::$encryption_key . "'\n";

    $database_name = (string)$dbaccess;
    if (!isset(AnEncryptor::$_cache[$database_name])) {
      AnEncryptor::$_cache[$database_name] = array();
    }
    if (!$dbaccess->table_exists('_encryptors')) {
      $tmp = AnEncryptor::create_table($dbaccess);
      return;
    }
    $tmp = $dbaccess->select_from_table('_encryptors');

    foreach ($tmp as $row) {
      $name = $row['name'];
      $key_value = IncludeUtilities::_decrypt($row['key_value'], AnEncryptor::$master_key, AnEncryptor::$master_iv_len);
      AnEncryptor::$_cache[$database_name][$name] = new AnEncryptor($dbaccess, $name, $key_value, TRUE);
    }
  } // end of load_cache()

  public static function create_table($dbaccess, $drop_first = FALSE)
  {
    return $dbaccess->create_table('_encryptors', AnEncryptor::$field_definitions, $drop_first);
  } // end of create_table()
  
  public static function get_encryptor($dbaccess, $name)
  {
    if (!($dbaccess instanceof DBAccess)) {
      throw new AnEncryptorException("AnEncryptor::get_encryptor(dbaccess, $name): dbacces is not a DBAccess instance");
    }
    if (!preg_match('/[a-z][_0-9a-z]*/', $name)) {
      throw new AnEncryptorException("AnEncryptor::get_encryptor({$dbaccess}, $name): Illegal AnEncryptor name");
    }

    $database_name = (string)$dbaccess;
    if (!isset(AnEncryptor::$_cache[$database_name])) {
      AnEncryptor::load_cache($dbaccess);
    }
    // if not cached, then create new entry
    if (!array_key_exists($name, AnEncryptor::$_cache[$database_name])) {
      AnEncryptor::$_cache[$database_name][$name] = new AnEncryptor($dbaccess, $name, NULL, FALSE);
    }
    return AnEncryptor::$_cache[$database_name][$name];
  } // end of get_encryptor()
  
  // instance methods
  
  public function __toString()
  {
    return "Encryptor '{$this->name}' in database '{$this->dbaccess}'";
  } // end of __toString()

  private function save()
  {
    if (!$this->dbaccess->table_exists('_encryptors')) {
      AnEncryptor::load_cache($this->dbaccess);
    }
    $tmp = $this->dbaccess->insert_into_table('_encryptors', array('name' => $this->name,
      'key_size' => $this->key_size,
      'key_value' => IncludeUtilities::_encrypt($this->key_value, AnEncryptor::$master_key, AnEncryptor::$master_iv_len),
      'iv_len' => $this->iv_len));
    $this->in_database = TRUE;
    if (!$tmp) {
      throw new AnEncryptorException("AnEncryptor::save(): save failure: {$this->dbaccess->error()}");
    }
  } // end of save()
  
  public static function php_create_string($dbaccess, $dump_dir)
  {
    if (!is_dir($dump_dir)) {
      if (!mkdir($dump_dir)) {
        echo "Skipping Encryptor Data - cannot create directory $dump_dir\n";
        return FALSE;
      }
    }
    if (!is_writable($dump_dir)) {
      echo "Skipping Encryptor Data - directory $dump_dir not writable\n";
      return FALSE;
    }

    AnEncryptor::load_cache($dbaccess);
    $database_name = (string)$dbaccess;
    $ar = array("<?php\nAnEncryptor::create_table(\$dbaccess, \$drop_first) or die(\"Unable to create AnEcryptor table\\n{\$dbaccess->error()}\\n\");");
    foreach (AnEncryptor::$_cache[$database_name] as $an_encrpytor) {
      $ar[] = "\$dbaccess->insert_into_table('_encryptors', unserialize(base64_decode('"
        . base64_encode(serialize(array(
          'name' => $an_encrpytor->name,
          'key_size' => $an_encrpytor->key_size,
          'iv_len' => $an_encrpytor->iv_len,
          'key_value' => IncludeUtilities::_encrypt($an_encrpytor->key_value, AnEncryptor::$master_key, AnEncryptor::$master_iv_len)
          ))) . "')));";
    }

    $fname = $dump_dir . DIRECTORY_SEPARATOR . '_encryptor.php';
    echo "Dumping Encryptor Data\n";
    return file_put_contents($fname, implode("\n", $ar) . "?>\n");
  } // php_create_string()

  public function encrypt($value)
  {
    return $value ? IncludeUtilities::_encrypt($value, $this->key_value, $this->iv_len) : '';
  } // end of encrypt()
  
  public function decrypt($value)
  {
    return $value ? IncludeUtilities::_decrypt($value, $this->key_value, $this->iv_len) : '';
  } // end of decrypt()
  
  public function dump($msg = NULL)
  {
    $str = $msg ? $msg . "\n" : '';
    $str .= "Encryptor $this->name: key_size: $this->key_size, iv_len: $this->iv_len\n";
    $str .= " key_value: " . bin2hex($this->key_value) . "\n";
    return $str;
  } // end of dump()
}

class AClassCmpException extends Exception {}

class AClassCmp {
  private $func = NULL;
  private $attr_list = array();
  public function __construct($arg)
  {
    if (is_callable($arg, FALSE, $arg)) {
      $this->func = $arg;
    } elseif (is_array($arg)) {
      foreach ($arg as $tmp) {
        $this->attr_list = array_map(array($this, 'burst_arg'), $tmp);
      }
    } elseif (is_string($arg)) {
      $this->attr_list = array_map(array($this, 'burst_arg'), preg_split('/\s*,\s*/', $arg));
    } else {
      throw new AClassCmpException("AClassCmp::__construct(arg): Illegal Arg");
    }
  } // end of __construct()
  
  public function __toString()
  {
    if ($this->func) {
      return "{$this->func}(a, b)";
    } else {
      $sort_def = implode(', ', array_map(create_function('$r', 'return "$r[0] " . ($r[1]?"asc":"desc");'),
        $this->attr_list));
      return "cmp(a, b) { $sort_def }";
    }
  } // end of __toString()

  public function __invoke($a, $b)
  {
    if ($this->func) {
      return call_user_func($this->func, $a, $b);
    }
    foreach ($this->attr_list as $row) {
      list($attr, $asc) = $row;
      if ($a->$attr != $b->$attr) {
        $asc_cmp = $a->$attr < $b->$attr ? -1 : 1;
        return $asc ? $asc_cmp : -$asc_cmp;
      }
    }
    return 0;
  } // end of FunctionName()
  
  public function cmp($a, $b)
  {
    $this($a, $b);
  } // end of cmp()
  
  private function burst_arg($str)
  {
    @list($attr, $asc_key) = explode(':', $str);
    if (!preg_match('/^[a-z]\w*$/', $attr)) {
      throw new AClassCmpException("AClassCmp::burst_arg($str): Illegal Attribute Name '$attr'");
    }
    if ($asc_key) {
      switch ($asc_key) {
        case 'a': case 'asc': case '+': return array($attr, TRUE);
        case 'd': case 'desc': case '-':return array($attr, FALSE);
        default: throw new AClassCmpException("AClassCmp::burst_arg($str): Illegal asc/desc");
      }
    } else {
      return array($attr, TRUE);
    }
  } // end of burst_arg()
  
  public function dump($msg = '')
  {
    $str = "$msg\n";
    if ($this->func) {
      $str .= "AClassCmp: is a wrapper for the function $func_name\n";
    } else {
      $str .= "AClassCmp: defines a function comparing the following fields\n";
      foreach ($this->attr_list as $row) {
        $str .= "  {$row[0]} " . ($row[1]?'asc' : 'desc') . "\n";
      }
    }
    return $str;
  } // end of dump()
}

// Class objects
class AClassException extends Exception {}

class AClass {
  // const DATATYPE_REG = '/^(text|char|varchar|enum|set|email|file|join|int|float|date|time|datetime)(\(\s*(\d+|((?i)[\da-z](\s*,\s*[\da-z])*)|[A-Z]\w*\.\w+)\s*\))?$/';
  private static $datatype_regx = array('/^(blob|pile|text|email|int|float|date|time|datetime)$/',
    '/^(char|varchar)\((\d+)\)$/',
    '/^(enum|set)\(\s*(\w+(\s*,\s*\w+)*)\s*\)$/',
    '/^(link)\(([A-Z]\w+)\.(\w+)\)$/',
    '/^(join)\(([A-Z]\w+)\.(\w+)(\s*,\s*(multiple))?\)$/',
    '/^(category)\((([a-z0-9]{1,15}(_[a-z0-9]{1,15})*)(\s*,\s*(([a-z0-9]{1,15})(_[a-z0-9]{1,15})*))*)\)$/',
    '/^(file)\(([^,]*)(,(public|private))?\)$/',
    '/^(float)\((\d)\)$/',
    );
  const NAME_REG = '/^[a-z][_a-z0-9]*$/';
  const EMAIL_FILTER_REGX = '(?P<user>[a-z0–9_][-\.a-z0–9_]*)@(?P<domain>[a-z_0–9][-a-z_0–9]*(\.[a-z_0–9][-a-z_0–9]*)*\.[a-z]+)';
  private static $legal_key_types = array('char', 'varchar', 'enum', 'set', 'email', 'date', 'time', 'datetime', 'int');
  private static $class_directory = array();
  private $cls_name = 'undefined';
  private $default_title_ar = array();
  private $dbaccess = NULL;
  private $enctype = "application/x-www-form-urlencoded";
  private $hash = NULL;
  private $key_hash = NULL;
  private $attribute_defs = NULL;
  private $attribute_dictionary = array();
  private $attribute_names = array();  // all attribute names
  // standard properties:
  //  display_attributes - attributes to add to display string
  //  display_classes - classes to add to display string
  //  default_title - marks this field as an element of the default display field.
  //  display_html
  //  encrypt - boolean - if exists, then should be encrypted in the database
  //  enums - possible values for an enum or set type
  //  format - output format for floats and etc
  //  form_attributes - attributes to add to form html segment
  //  form_classes - classes to add to form html string
  //  form_html - html segment for form
  //  form_html_func - name of instance function which returns a form_html template
  //  immutable - a value which cannot be changed once set
  //  invisible - boolean - if exists [assumed to be true] - then field is not displayed
  //  joins - string - name of AClass object this field joins to
  //  join_display_field - string - name of field to display in select list
  //  key - an element of the unique key for the class
  //  public - boolean - if present, may be viewed via web service
  //  readonly - boolean - if present, value cannot be modified via a form
  //  required - set if field must be filled in
  //  sql_name - field name augmented by table name - e.g. tablename.fieldname
  //  sql_type - SQL data type used in database
  //  title - display title
  //  type - one of the legal data types
  //  unique - must have unique value in db - this is distinct simple and compound key uniqueness
  //  width - integer defining width of a type which requires a width
  private static $legal_prop_names = array(
    'category_root',
    'category_deep',
    'default',
    'default_title',
    'display_attributes',
    'display_classes',
    'display_html',
    // 'display_html_func',
    'encrypt',
    'enums',
    'filter',
    'format',
    'form_attributes',
    'form_classes',
    'form_html',
    'form_html_func',
    'immutable',
    'invisible',
    'joins',
    'join_display_field',
    'key',
    'multiple',
    'path',
    'path_root',
    'precision',
    'private',
    'public',
    'readonly',
    'required',
    'sql_name',
    'sql_type',
    'title',
    'type',
    'unique',
    'where',
    'width',
    );
  // NOTE: property conflicts must have keys for both ends of the conflict
  private static $illegal_prop_combinations = array(
    'key' => array('encrypt', 'invisible'),
    'encrypt' => array('key', 'public'),
    'invisible' => array('key'),
    'public' => array('encrypt', 'private'),
    'private' => array('public', 'key'),
    );
  private static $illegal_type_prop_combinations = array(
      'category' => array('default'),
      // 'char',
      // 'date',
      // 'datetime',
      // 'email',
      // 'enum',
      // 'float',
      // 'file',
      // 'int'
      // 'join',
      // 'set',
      // 'text',
      // 'time',
      // 'varchar',
    );
  private $attribute_properties = array();
  private $attribute_token_patterns = array();
  private $tablename = NULL;
  private $keys_list = NULL;           // names of all key fields
  private $value_names = NULL;        // names of all non-key attributes

  private function __construct($cls_name, $keys_list, $attribute_defs, $property_defs)
  {
    $this->cls_name = $cls_name;
    $this->tablename = strtolower($cls_name);
    $this->attribute_defs = $attribute_defs;
    
    // sanity check the attribute definitions
    $display_tmp = array();
    $form_tmp = array();
    
    // verify and sanitize keys_list
    if (!$keys_list) {
      throw new AClassException("$cls_name::__construct(): illegal keys_list - may not be empty");
    } elseif (is_string($keys_list)) {
      $this->keys_list = array($keys_list);
    } elseif (is_array($keys_list)) {
      $this->keys_list = $keys_list;
    } else {
      throw new AClassException("$cls_name::__construct(): illegal keys_list");
    }
    sort($this->keys_list);

    // create attribute and store hash values
    list($this->hash, $this->key_hash) = $this->aclass_hash_values();
    // $ar = array_map(array('AClass', 'aclass_hash_helper'), $this->attribute_defs);
    // sort($ar);
    // $this->hash = md5(implode(',', $ar));
    // 
    // // create key attributes hash value
    // $ar = array();
    // foreach ($this->attribute_defs as $row) {
    //   if (in_array($row[0], $this->keys_list))
    //     $ar[] = AClass::aclass_hash_helper($row);
    // }
    // sort($ar);
    // $this->key_hash = md5(implode(',', $ar));
    
    // parse, verify, and other stuff for attribute definitions
    foreach ($this->attribute_defs as $row) {
      list($attr, $def, $attr_title) = $row;
      $this->attribute_names[] = $attr;
      $this->attribute_dictionary[$attr] = $row;
      if (preg_match(AClass::NAME_REG, $attr) == 0) {
        throw new AClassException("$cls_name::__construct(): Illegal attr name: '$attr'");
      }
      // parse data type
      if (!($matches = AClass::match_datatype($def))) {
        throw new AClassException("$cls_name::__construct(): Attribute $attr: Illegal type: '$def'");
      }
      $this->attribute_properties[$attr] = array(
        'type' => $matches[1],
        'title' => $attr_title,
        'sql_name' => "{$this->tablename}.{$attr}", );
      switch ($matches[1]) {
        case 'text':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $input_statement = "<span style=\"clear:both;float:right\"><textarea name=\"{$attr}\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} name=\"$attr\" id=\"$attr\" cols=\"50\" rows=\"30\">{{$attr}}</textarea></span>";
          $this->put_prop($attr, 'width', 0); // unlimited
          $this->put_prop($attr, 'sql_type', $matches[1]);
          $this->put_prop($attr, 'form_classes', 'rte');
          break;
        case 'blob':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $input_statement = '';
          $this->put_prop($attr, 'width', 0); // unlimited
          $this->put_prop($attr, 'sql_type', 'text');
          $this->put_prop($attr, 'readonly');
          $this->put_prop($attr, 'invisible');
          $this->put_prop($attr, 'default', '');
          break;
        case 'pile':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $input_statement = '';
          $this->put_prop($attr, 'width', 0); // unlimited
          $this->put_prop($attr, 'sql_type', 'text');
          $this->put_prop($attr, 'readonly');
          $this->put_prop($attr, 'invisible');
          $this->put_prop($attr, 'default', array());
          break;
        case 'file':
          if (($len = count($matches)) != 3 && $len != 5) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $this->enctype = 'multipart/form-data';
          $input_statement = "<span style=\"float:right;clear:both\"> {{$attr}}: <input type=\"file\" name=\"$attr\" id=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} value=\"\" maxlength=\"255\" size=\"60\"></span>";
          $this->put_prop($attr, 'width', 255);
          $this->put_prop($attr, 'sql_type', 'varchar(255)');
          $this->put_prop($attr, 'path_root', $len == 3 || $matches[4] == 'public' ? Globals::$document_root
            : Globals::$private_data_root);
          $this->put_prop($attr, 'path', $matches[2]);
          break;
        case 'email':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $this->put_prop($attr, 'width', 255);
          $this->put_prop($attr, 'sql_type', 'varchar(255)');
          $this->put_prop($attr, 'filter', AClass::EMAIL_FILTER_REGX);
          $input_statement = "<input type=\"text\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" id=\"{$attr}\" size=\"40\" maxlength=\"255\" value=\"{{$attr}}\">";
          break;
        case 'category':
          if (($cnt = count($matches)) != 4 && $cnt != 5 && $cnt != 8 && $cnt != 9) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def ($cnt)");
          }
          // the next three lines are not necessary because excluded by regular expression abov
          // if (!$matchs[2]) {
          //    throw new AClassException("$cls_name::__construct(): syntax error: attribute $attr: category data types must have non-empty root");
          //  }
           $this->put_prop($attr, 'sql_type', 'text');
          // category_root - purge white space from list of category roots
          $tmp = preg_split('/\s*,\s*/', $matches[2]);
          sort($tmp);
          $this->put_prop($attr, 'category_root', implode(',', $tmp));
          $input_statement = "ERROR - category input must be created dynamically";
          break;
        case 'enum':
          if (count($matches) != 4) {
            throw new AClassException("$cls_name::__construct(): syntax error: Missing value list $def");
          }
          $tmp = preg_split('/\s*,\s*/', trim($matches[2]));
          $this->put_prop($attr, 'enums', $tmp);

          $sql_type = 'char(' . max(array_map(create_function('$x', 'return strlen($x);'), $tmp)) . ')';
          $this->put_prop($attr, 'default', $tmp[0]);
          $this->put_prop($attr, 'sql_type', $sql_type);
          $tmp_ar = array();
          $bar = '';
          foreach ($tmp as $value) {
            $tmp_ar[] = "<span style=\"float:right\"><input type=\"radio\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" value=\"{$value}\" {{$attr}-{$value}}> $bar $value</span>";
            $bar = ' |';
          }
          $input_statement = "<span style=\"clear:both;float:right\">" . implode("\n", array_reverse($tmp_ar)) . "</span>\n";
          break;
        case 'set':
          if (count($matches) != 4) {
            throw new AClassException("$cls_name::__construct(): syntax error: Missing value list $def");
          }
          $tmp = preg_split('/\s*,\s*/', trim($matches[2]));
          $this->put_prop($attr, 'enums', $tmp);

          $sql_type = 'varchar(255)';
          $this->put_prop($attr, 'default', array());

          $this->put_prop($attr, 'sql_type', $sql_type);
          $tmp_ar = array();
          $bar = '';
          foreach ($tmp as $value) {
            $tmp_ar[] = "<span style=\"float:right\"><input type=\"checkbox\" name=\"{$attr}[]\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" value=\"{$value}\" {{$attr}-{$value}}> $bar $value</span>";
            $bar = ' |';
          }
          $input_statement = "<span style=\"clear:both;float:right\">" . implode("\n", array_reverse($tmp_ar)) . "</span>\n";
          break;
        case 'link':
          $this->put_prop($attr, 'joins', $matches[2]);
          $this->put_prop($attr, 'join_display_field', $matches[3]);
          $this->put_prop($attr, 'sql_type', 'varchar(255)');
          $input_statement = "ERROR - link input must be created dynamically";
          break;
        case 'join':
          $this->put_prop($attr, 'joins', $matches[2]);
          $this->put_prop($attr, 'join_display_field', $matches[3]);
          if (count($matches) == 6) {
            $this->put_prop($attr, 'multiple');
          }
          // omit sql_type property so the join data does not occupy a field in the table
          // $this->put_prop($attr, 'sql_type', 'none');
          $input_statement = "ERROR - join input must be created dynamically";
          break;
        case 'int':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $this->put_prop($attr, 'width', 255);
          $this->put_prop($attr, 'sql_type', 'int');
          $input_statement = "<input type=\"text\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" id=\"{$attr}\" size=\"20\" maxlength=\"40\" value=\"{{$attr}}\">";
          break;
        case 'date':
        case 'time':
        case 'datetime':
          if (count($matches) != 2) {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $this->put_prop($attr, 'width', 0);
          $this->put_prop($attr, 'sql_type', 'timestamp');
          $input_statement = "<input type=\"text\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" id=\"{$attr}\" size=\"20\" maxlength=\"40\" value=\"{{$attr}}\">";
          break;
        case 'float':
          if (count($matches) == 2) {
            $this->put_prop($attr, 'format', '%f');
          } elseif (count($matches) == 3) {
            $tmp = intval($matches[2]);
            if ($tmp < 0 || $tmp >= 10) {
              throw new AClassException("$cls_name::__construct(): syntax error: Illegal float precision: $tmp");
            }
            $this->put_prop($attr, 'format', "%0.{$tmp}f");
            $this->put_prop($attr, 'precision', $tmp);
          } else {
            throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          }
          $this->put_prop($attr, 'sql_type', 'float');
          $input_statement = "<input type=\"text\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" id=\"{$attr}\" size=\"20\" maxlength=\"40\" value=\"{{$attr}}\">";
          break;
        case 'char':
        case 'varchar':
          if (count($matches) != 3) { throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");}
          $tmp = intval($matches[2]);
          if ($tmp <= 0 || $tmp >= 256) { throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");}
          $this->put_prop($attr, 'width', $tmp);
          $this->put_prop($attr, 'sql_type', "{$matches[1]}($tmp)");
          $html_attributes = "maxlength=\"{$tmp}\" size=\"" . ($tmp > 40 ? 40 : $tmp) . "\"";
          $input_statement = "<input type=\"text\" name=\"$attr\" class=\"{$matches[1]} {_form_classes_}\" {_form_attributes_} style=\"clear:both;float:right\" id=\"{$attr}\" $html_attributes value=\"{{$attr}}\">";
          break;
        default:
          throw new AClassException("$cls_name::__construct(): syntax error: Illegal type $def");
          break;
      }

      // construct display and form segments for this attribute
      $this->put_prop($attr, 'display_html', "<li style=\"clear:both;list-style:none\">
      <span id=\"{$attr}\" class=\"display_value {_display_classes_}\" {_display_attributes_} style=\"float:right\">{{$attr}}</span>
      <span id=\"{$attr}_title\" class=\"display_label\">$attr_title: </span>
      </li>");
      
      $this->put_prop($attr, 'form_html', "<li style=\"clear:both\">
        $input_statement
        <label for=\"$attr\" >{$attr_title}{_required_mark_}</label>
      </li>");
    }

    foreach ($this->keys_list as $key) {
      if (!in_array($key, $this->attribute_names)) {
        throw new AClassException("$cls_name::__construct(): Key '$key' not a defined attribute");
      }
      if (!in_array($this->get_prop($key, 'type'), AClass::$legal_key_types)) {
        throw new AClassException("$cls_name::__construct(): Key '$key' is must be one of " . implode(',', AClass::$legal_key_types) . ", not '{$this->get_prop($key,'type')}");
      }
      $this->put_prop($key, 'immutable');
      $this->put_prop($key, 'key');
      $this->put_prop($key, 'required');
    }
    // compute and save values names
    $this->value_names = array_diff($this->attribute_names, $this->keys_list);

    foreach ($this->attribute_names  as $attr) {
      $this->attribute_token_patterns[$attr] = "/{({$this->cls_name}\.)?{$attr}}/";
    }

    $key_hiddens = array();
    foreach ($this->keys_list as $key) {
      $key_hiddens[] = "<input type=\"hidden\" name=\"key_{$key}\" value=\"{{$key}}\">";
    }
    
    // add in additional property definitions
    if (is_array($property_defs)) {
      $this->unpack_property_array($property_defs);
    }
    
    // add required marks and other odds and ends
    foreach ($this->attribute_names as $attr) {
      $pattern_ar = array('/{_required_mark_}/', '/{_display_attributes_}/', '/{_display_classes_}/',
          '/{_form_attributes_}/', '/{_form_classes_}/');
      $values_ar = array();
      $values_ar[] = $this->has_prop($attr, 'required') ? "<span class=\"required\">*</span>" : '';
      $values_ar[] = $this->has_prop($attr, 'display_attributes') ? $this->get_prop($attr, 'display_attributes') : '';
      $values_ar[] = $this->has_prop($attr, 'display_classes') ? $this->get_prop($attr, 'display_classes') : '';
      $values_ar[] = $this->has_prop($attr, 'form_attributes') ? $this->get_prop($attr, 'form_attributes') : '';
      $values_ar[] = $this->has_prop($attr, 'form_classes') ? $this->get_prop($attr, 'form_classes') : '';
      foreach (array('form_html', 'display_html') as $prop_name) {
        $tmp = $this->get_prop($attr, $prop_name);
        $this->put_prop($attr, $prop_name, preg_replace($pattern_ar, $values_ar, $tmp));
      }
      
      // check joins property
      if ($this->has_prop($attr, 'joins')
          && (($tmp = $this->get_prop($attr, 'type')) != 'join' && $tmp != 'link')) {
        throw new AnEncryptorException("{$cls_name}::__construct(): $attr is a '$tmp' [not a join or link], but has 'joins' property");
      }
      
      /*
        if (!$this->has_prop($attr, 'join_display_field')) {
          throw new AClassException('AClass::__construct($cls_name): Attribute $attr joins '
            . $this->get_prop($attr, 'joins') . ' but does not define "join_display_field"');
        }
        $this->put_prop($attr, 'sql_type', 'varchar(255)');
        $this->put_prop($attr, 'type', 'varchar');
        $this->put_prop($attr, 'width', 255);
      }
      */
    }

    // Add to Class Directory
    AClass::$class_directory[$cls_name] = $this;
  } // end of __construct()

  // Class Methods
  public static function match_datatype($def)
  {
    foreach (AClass::$datatype_regx as $regx) {
      if (preg_match($regx, $def, $matches)) {
        return $matches;
      }
    }
    return FALSE;
  } // end of match_datatype()

  public static function define_class($cls_name, $keys_list, $attribute_defs, $property_defs)
  {
    if (isset(AClass::$class_directory[$cls_name])) {
      throw new AClassException("AClass::define_class($cls_name, ...): Class Already Defined");
    }
    new AClass($cls_name, $keys_list, $attribute_defs, $property_defs);
    return AClass::$class_directory[$cls_name];
  } // end of define()
  
  public static function existsP($cls_name)
  {
    return isset(AClass::$class_directory[$cls_name]);
  } // end of existsP()
  
  public static function get_class_instance($cls_name)
  {
    if (!isset(AClass::$class_directory[$cls_name])) {
      throw new AClassException("AClass::get_class_instance($cls_name): Class not defined");
    }
    return AClass::$class_directory[$cls_name];
  } // end of get_class()

  public static function get_class_directory()
  {
    return AClass::$class_directory;
  } // end of get_class_list()

  public static function attribute_existsP($cls_name, $attr) {
  
    if (!class_exists($cls_name)) {
      if ($include_result = (include_once($cls_name . ".php")) === FALSE) {
        return FALSE;
      }
    }
    if (array_key_exists($cls_name, AClass::$class_directory)) {
      $cls = AClass::get_class_instance($cls_name);
      return in_array($attr, $cls->attribute_names);
    } else {
      return array_key_exists($attr, get_class_vars($cls_name));
    }
  } // end of attribute_existsP()

  // Instance Methods

  public function __toString()
  {
    return $this->cls_name;
  } // end of __toString()
  
  public function __get($attr)
  {
    switch ($attr) {
      case 'attribute_defs':
      case 'attribute_dictionary':
      case 'attribute_names':
      case 'attribute_properties':
      case 'attribute_token_patterns':
      case 'cls_name':
      case 'enctype':
      case 'hash':
      case 'key_hash':
      case 'keys_list':
      case 'tablename':
      case 'value_names':
        return $this->$attr;
      default:
        echo "AClass::__get($attr): attribute does not exist or is not accessible\n";
        throw new AClassException("AClass::__get($attr): attribute does not exist or is not accessible");
    }
  } // end of __get()
  
  public function resolve_attr_values($attr_values)
  {
    if (!$attr_values) {
      return array();
    } elseif (is_array($attr_values)) {
      return $attr_values;
    } elseif (is_string($attr_values) && substr($attr_values, 0, 4) == 'a%3A') {
      return AnInstance::static_decode_key_values($attr_values);
    } elseif (count($this->keys_list) == 1) {
      if (is_string($attr_values)) {
        return array($this->keys_list[0] => $attr_values);
      } else {
        $key_attr = $this->keys_list[0];
        $key_type = $this->get_prop($key_attr, 'type');
        switch ($key_type) {
          case 'int':
            if (is_int($attr_values)) {
              return array($this->keys_list[0] => $attr_values);
            }
            break;
          case 'date':
          case 'time':
          case 'datetime':
            if ($attr_values instanceof DateTime) {
              return array($this->keys_list[0] => $attr_values);
            }
            break;
          default:
            break;
        }
      }
    }
    $exception_name = "{$this->cls_name}Exception";
    throw new $exception_name("$this->cls_name::__construct(): improper attribute values");
  } // end of resolve_attr_values()

  // property list support
  private function unpack_property_array($property_array)
  {
    foreach ($property_array as $attr => $def) {
      if (is_string($def)) {
        $this->put_prop($attr, $def);
        if ($def == 'default_title') {
          $this->default_title_ar[] = $attr;
        }
      } elseif (is_array($def)) {
        foreach ($def as $prop_name => $value) {
          if (is_string($prop_name)) {
            $this->put_prop($attr, $prop_name, $value);
          } elseif (is_int($prop_name)) { // this picks up booleans which are given integer keys in a foreach
            $this->put_prop($attr, $value);
            if ($value == 'default_title') {
              $this->default_title_ar[] = $attr;
            }
          } else {
            throw new AClassException('Ill formed property entry for attribute $attr');
          }
        }
      } else {
        throw new AClassException("AClass::unpack_property_array(): ill formed entry for attribute $attr");
      }
    }
  } // end of unpack_property_array()

  public function prop_name_exists($prop_name)
  {
    return in_array($prop_name, AClass::$legal_prop_names);
  } // end of prop_name_exists()
  
  public function put_prop($name, $prop_name, $val = TRUE)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AClassException("AClass::put_prop($name, $prop_name): Illegal Attribute name '$name' for $this->cls_name");
    }
    if (!in_array($prop_name, AClass::$legal_prop_names)) {
      throw new AClassException("AClass::put_prop($name, $prop_name): Illegal Property name '$prop_name'");
    }
    // check to see if there is a property conflict
    if (isset(AClass::$illegal_prop_combinations[$prop_name])) {
      foreach (AClass::$illegal_prop_combinations[$prop_name] as $other_prop) {
        if (array_key_exists($other_prop, $this->attribute_properties[$name])) {
          throw new AClassException("AClass::put_prop($name, $prop_name): class $this->cls_name: property conflict - cannot set both $prop_name and $other_prop");
        }
      }
    }
    $type = $this->get_prop($name, 'type');
    if (isset(AClass::$illegal_type_prop_combinations[$type])
        && in_array($prop_name, AClass::$illegal_type_prop_combinations[$type])) {
      throw new AClassException("AClass::put_prop($name, $prop_name, value): Illegal property for type {$type}");
    }
    switch ($prop_name) {
      case 'filter':
        $this->append_to_prop($name, 'form_attributes', "filter=\"$val\"");
        $this->append_to_prop($name, 'form_classes', 'filtered');
        $val = '/^' . $val . '$/';  // wrap in anchors for PHP preg_match
        break;
      default:
        break;
    }
    $this->attribute_properties[$name][$prop_name] = $val;
  } // end of put_prop()
  
  public function append_to_prop($name, $prop_name, $value)
  {
    if ($this->has_prop($name, $prop_name)) {
      $cur_val = $this->get_prop($name, $prop_name);
      if (is_bool($cur_val)) {
        throw new AnInstanceException("$this->cls_name::append_to_prop($name, $prop_name, value): cannot append to boolean property");
      }
      if ($cur_val) {
        $value = "$cur_val $value";
      }
    }
    $this->put_prop($name, $prop_name, $value);
  } // end of append_to_prop()
  
  public function has_prop($name, $prop_name)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AClassException("AClass::has_prop($name, $prop_name): Illegal Attribute name '$nam'");
    }
    if (!in_array($prop_name, AClass::$legal_prop_names)) {
      throw new AClassException("AClass::has_prop($name, $prop_name): Illegal Property name '$prop_name'");
    }
    return isset($this->attribute_properties[$name][$prop_name]);
  } // end of has_prop()
  
  public function get_prop($name, $prop_name)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AClassException("AClass::get_prop($name, $prop_name): Illegal Attribute name '$nam'");
    }
    if (!in_array($prop_name, AClass::$legal_prop_names)) {
      throw new AClassException("AClass::get_prop($name, $prop_name): Illegal Property name '$prop_name'");
    }
    return isset($this->attribute_properties[$name][$prop_name])
      ? $this->attribute_properties[$name][$prop_name] : FALSE;
  } // end of get_prop()
  
  // end of property list support
  
  private function create_table_def_array()
  {
    $ar = array();
    foreach ($this->attribute_names as $attr) {
      if ($this->has_prop($attr, 'sql_type')) {
        $ar[] = array($attr, $this->get_prop($attr, 'sql_type'), in_array($attr, $this->keys_list));
      }
    }
    return $ar;
  } // end of create_table_def_array()
  
  // hash functions
  private function aclass_hash_helper($ar)
  {
    return "{$ar[0]}:{$ar[1]}";
  } // end of aclass_hash_helper()
  
  public function aclass_hash_values()
  {
    // create attribute hash value
    $ar = array_map(array($this, 'aclass_hash_helper'), $this->attribute_defs);
    sort($ar);
    $hash = md5(implode(',', $ar));
    
    // create key attributes hash value
    $ar = array();
    foreach ($this->attribute_defs as $row) {
      if (in_array($row[0], $this->keys_list))
        $ar[] = $this->aclass_hash_helper($row);
    }
    sort($ar);
    $key_hash = md5(implode(',', $ar));

    return array($hash, $key_hash);
  } // end of aclass_hash_values()

  public static function create_aclass_hashes_table($dbaccess, $drop_first = FALSE)
  {
    $dbaccess->create_table(AnInstance::ACLASS_HASHES_TABLENAME, array(array('cls_name', 'varchar(255)', TRUE), array('hash', 'char(32)'), array('key_hash', 'char(32)')), $drop_first);
  } // end of create_aclass_hashes_table()

  public function aclass_instance_hashes_array()
  {
    return array('cls_name' => $this->cls_name, 'hash' => $this->hash, 'key_hash' => $this->key_hash);
  } // end of aclass_instance_hashes_array()

  // database functions
  public function create_table($dbaccess, $drop_first = FALSE, $warn_on_drop_error = TRUE)
  {
    if (!($dbaccess instanceof DBAccess)) {
      throw new AClassException("AClass::create_table(dbaccess) for $this->cls_name - dbacces is NOT a DBAccess instance");
     }

    $ar = $this->create_table_def_array();
    if (!$dbaccess->create_table($this->tablename, $ar, $drop_first)) {
      switch ($warn_on_drop_error) {
        case TRUE:
          echo "Unable to create Table {$this->tablename}: " . $dbaccess->error() . "\n";
          break;
        case 'ignore':
          break;
        default:
          throw new AClassException("Unable to create Table {$this->tablename}: " . $dbaccess->error());
      }
    }
    // create a parameter entry for this class - if none exists
    new Parameters($dbaccess, $this->cls_name);
    
    // add entry into _aclass_hashes table
    if (!$dbaccess->table_exists(AnInstance::ACLASS_HASHES_TABLENAME)) {
      AClass::create_aclass_hashes_table($dbaccess, $drop_first);
    }
    
    $tmp = $dbaccess->select_from_table(AnInstance::ACLASS_HASHES_TABLENAME, NULL,
        array('cls_name' => $this->cls_name));
    // lame logic is:
    //   if there is no entry, put one in
    //   if there is an entry and if we dropped the table before recreating, then update the hashes table
    //   otherwise, check to see if the hashes match. If not, then make a state transition
    if (count($tmp) == 0) {
      $dbaccess->insert_into_table(AnInstance::ACLASS_HASHES_TABLENAME, $this->aclass_instance_hashes_array());
    } elseif ($drop_first) {
      $dbaccess->update_table(AnInstance::ACLASS_HASHES_TABLENAME, $this->aclass_instance_hashes_array(),
          array('cls_name' => $this->cls_name));
    } elseif ($tmp[0]['hash'] != $this->hash) {
      require_once('StateMgt.php');
      if ($this->key_hash == $tmp[0]['hash_key']) {
        StateMgt::handle_event('MODEL_MISMATCH_EDIT');
      } else {
        StateMgt::handle_event('ILLEGAL_EDIT');
      }
      // change_site_state('model_mismatch', $this->key_hash == $tmp[0]['key_hash'] ? 'R' : 'X');
    }
    return TRUE;
  } // end of create_table()
  
  public static function create_all_tables($dbaccess, $drop_first = FALSE, $warn_on_drop_error = FALSE)
  {
    require_once('dbaccess.php');
    if (!($dbaccess instanceof DBAccess) ) {
      throw new AClassException("AClass::create_all_tables(dbaccess): dbaccess is not an instance of DBAccess");
    }
    $exception_msgs = array();
    foreach (AClass::$class_directory as $cls) {
      try{
        $cls->create_table($dbaccess, $drop_first, $warn_on_drop_error);
      } catch (Exception $e) {
        $exception_msgs[] = (string)$e;
      }
    }
    if (count($exception_msgs) > 0) echo "<pre>\n" . implode("\n", $exception_msgs) . "\n</pre>\n";
  } // end of create_all_tables()

  
  public static function php_defs_ar($aclass_name_list = NULL)
  {
    // if aclass_name_list is null, scan the object directories
// var_dump(scandir(Globals::$system_objects));
// var_dump(scandir(Globals::$objects_root));
    $dir_scan = array_merge(scandir(Globals::$system_objects), scandir(Globals::$objects_root));
// var_dump($dir_scan);
    $tmp = array_filter($dir_scan, create_function('$a', 'return preg_match("/.php$/", $a) ? TRUE : FALSE;'));
// var_dump($tmp);
    $aclass_name_list = array_map(create_function('$a', 'return substr($a,0,strlen($a)-4);'),$tmp);
// var_dump($aclass_name_list);
    $ar = array();
    foreach ($aclass_name_list as $aclass_name){
      require_once($aclass_name . ".php");
      $ref_obj = new ReflectionClass($aclass_name);
      if (!$ref_obj->isSubclassOf('AnInstance')) {
        continue;
      }
      $aclass = AClass::get_class_instance($aclass_name);
      $ar[$aclass_name] = array('defs' => $aclass->attribute_defs,
        'keys' => $aclass->keys_list, 'props' => $aclass->attribute_properties);
    }

    return $ar;
  } // end of php_defs_ar()

  public static function php_create_string($dbaccess, $aclass_name_list, $dump_dir)
  {
    if (!is_dir($dump_dir)) {
      if (!mkdir($dump_dir)) {
        echo "Skipping AClass Definitions - cannot create directory $dump_dir\n";
        return FALSE;
      }
    }

    if (!is_writable($dump_dir)) {
      echo "Skipping AClass Definitions - directory $dump_dir not writable\n";
      return FALSE;
    }
    
    if (!($dbaccess instanceof DBAccess)) {
      echo "Skipping AClass Definitions - dbaccess parameter is NOT a DBAccess object\n";
      return FALSE;
    }
    
    if ($dbaccess->on_line != 'F'){
      echo "Skipping AClass Definitions - $dbaccess is NOT Off-Line\n";
      return FALSE;
    }
    
    // this is a special case: if a model mismatch has been detected, but the archive
    //  is not stale - then we can terminate and return TRUE. If it is stale, then
    //  we can't proceed, but must return FALSE - signalling that the superior process
    //  should halt.
    if ($dbaccess->model_mismatch != 'F'){
      echo "Skipping AClass Definitions - Model Mismatch w.r.t. data in database $dbacess\n";
      if (!$dbaccess->table_exists(AnInstance::ACLASS_HASHES_TABLENAME)) {
        echo "AClass hashes Table does not Exist: Administrator should Recreate It\n";
      }
      return $dbaccess->archive_stale == 'T' ? FALSE : TRUE;
    }

    $create_aclass_tables_str = "<?php\n if (!isset(\$drop_first)) \$drop_first = FALSE;\n";
    $aclass_attribute_defs_str = "\$aclass_defs = array();\n";
    foreach ($aclass_name_list as $aclass_name) {
      try {
        // require_once($aclass_name . ".php");
        if (include_once("{$aclass_name}.php") === FALSE) {
          echo "Class {$aclass_name} cannot be included - Skipping\n";
          continue;
        }
        $aclass = AClass::get_class_instance($aclass_name);
        if (!($aclass instanceof AClass)) {
          continue;
        }
        $aclass_attribute_defs_str .= " \$aclass_defs['$aclass_name'] = array(\n";
        $aclass_attribute_defs_str .= "  'defs' => unserialize(base64_decode('"
          . base64_encode(serialize($aclass->attribute_defs)) . "')),\n";
        $aclass_attribute_defs_str .= " 'keys' => unserialize(base64_decode('"
          . base64_encode(serialize($aclass->keys_list)) . "')),\n";
        $aclass_attribute_defs_str .= " 'props' => unserialize(base64_decode('"
          . base64_encode(serialize($aclass->attribute_properties)) . "')),\n";
        $aclass_attribute_defs_str .= " 'tablename' => '{$aclass->tablename}',\n";
        $aclass_attribute_defs_str .= ");\n";
        
        $create_aclass_tables_str .= " require_once('{$aclass_name}.php');\n";
        $create_aclass_tables_str .= " \$aclass = AClass::get_class_instance('$aclass_name');\n";
        $create_aclass_tables_str .= " \$aclass->create_table(\$dbaccess, \$drop_first);\n\n";
      } catch (AClassException $e) { /* ignore AClass Exceptions here */ }
    }
    $create_aclass_tables_str .= "?>\n";

    $fname = $dump_dir . DIRECTORY_SEPARATOR . '_aclass_attribute_defs.php';
    echo "Dumping AClass Definitions\n";
    if (!file_put_contents($fname, $aclass_attribute_defs_str)) return FALSE;

    $fname = $dump_dir . DIRECTORY_SEPARATOR . '_aclass_create_tables.php';
    echo "Dumping AClass Create Tables Code\n";
    return file_put_contents($fname, $create_aclass_tables_str);
  } // php_create_string()
  
  public function obj_to_db($attr, $value, $encryptor)
  {
    switch ($this->get_prop($attr, 'type')) {
      case 'join':
        return NULL;
      case 'category':
      case 'enum':
      case 'link':
      case 'text':
        return $this->has_prop($attr, 'encrypt') ? $encryptor->encrypt($value) : $value;
      case 'blob':
        return $this->has_prop($attr, 'encrypt') ? $encrypt->encrypt($value) : base64_encode($value);
      case 'pile':
      case 'set':
        return $this->has_prop($attr, 'encrypt') ? $encrypt->encrypt($value) : serialize($value);
      case 'char':
      case 'email':
      case 'file':
      case 'varchar':
        $value = substr($value, 0, $this->get_prop($attr, 'width'));
        return $this->has_prop($attr, 'encrypt') ? $encryptor->encrypt($value) : $value;
      case 'int':
      case 'float':
        return $value;
      case 'date':
      case 'time':
      case 'datetime':
        $value = $value instanceof DateTime ? $value->format('c') : $value;
        return $this->has_prop($attr, 'encrypt') ? $encryptor->encrypt($value) : $value;
      default:
        throw new AClassException("${$this->cls_name}::obj_to_db($attr,...): illegal data type: {$this->get_prop($attr, 'type')}");
    }
  } // end of obj_to_db()
  
  public function db_to_obj($attr, $value, $encryptor)
  {
    if (!($encryptor instanceof AnEncryptor)) {
      throw new AClassException("AClass::db_to_obj($attr, value, encryptor): supplied encryptor is not an AnEncryptor instance");
    }
    switch ($this->get_prop($attr, 'type')) {
      case 'join';
        return NULL;
      case 'category':
      case 'enum':
      case 'link':
      case 'text':
        return $this->has_prop($attr, 'encrypt') ? $encryptor->decrypt($value) : $value;
      case 'blob':
        return $this->has_prop($attr, 'encrypt') ? $encrypt->decrypt($value) : base64_decode($value);
      case 'pile':
      case 'set':
        return $this->has_prop($attr, 'encrypt') ? $encrypt->decrypt($value) : unserialize($value);
      case 'char':
        $value = $this->has_prop($attr, 'encrypt') ? $encryptor->decrypt($value) : $value;
        return substr($value, 0, $this->get_prop($attr, 'width'));
      case 'email':
      case 'email':
      case 'file':
      case 'varchar':
        // the trim() is required for postgresql. This works without it for sqlite,sqlite3,mysql, and mysqli
        $value = $this->has_prop($attr, 'encrypt') ? $encryptor->decrypt($value) : $value;
        return trim(substr($value, 0, $this->get_prop($attr, 'width')));
      case 'int':
        return intval($value);
      case 'float':
        return floatval($value);
      case 'date':
      case 'time':
      case 'datetime':
        $value = $this->has_prop($attr, 'encrypt') ? $encryptor->decrypt($value) : $value;
        return $value ? new DateTime($value) : '';
      default:
        throw new AClassException("${$this->cls_name}::obj_to_db($attr,...): illegal data type: {$this->get_prop($attr, 'type')}");
    }
  } // end of db_to_obj()
  
  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\"> <!-- dump of $this -->\n$msg\nAClass: $this\n";
    echo "Class: $this->cls_name\n";
    echo "Table Name: $this->tablename\n";
    foreach ($this->keys_list as $key) {
      $str .= "  Key: $key\n";
    }
    foreach ($this->attribute_names as $attr) {
      $str .= "  Attr: $attr [{$this->get_prop($attr, 'title')}]: {$this->get_prop($attr,'type')}";
      switch ($this->get_prop($attr,'type')) {
        case 'char':
        case 'varchar':
          $str .= " ({$this->get_prop($attr, 'width')})";
          break;
        case 'enum':
        case 'set':
          $str .= " (" . implode(',', $this->get_prop($attr, 'enums')) . ")";
          break;
        case 'blob':
          $str .= "  (- blob data -)";
          break;
        case 'pile':
          $str .= "  (- pile storage -)";
          break;
      }
      $ar = array();
      $str .= "\n";
      foreach ($this->attribute_properties[$attr] as $prop_name => $value) {
        if (in_array($prop_name, array('type', 'title', 'sql_name')))
          continue;
        if (is_array($value)) {
          $ar[] = "$prop_name: " . implode(',', $value);
        } elseif ($value instanceof DateTime) {
          $ar[] = "$prop_name: " . $value->format('c');
        } else {
          $ar[] = "$prop_name: $value";
        }
      }
      if ($ar) {
        $str .= '    Properties:' . implode("\n      ", $ar) . "\n";
      }
    }
    return $str . "</div> <!-- dump of $this -->\n";
  } // end of dump()
}

// Instances of AClass objects
class AnInstanceException extends Exception {}

class AnInstance {
  const ACLASS_HASHES_TABLENAME = '_aclass_hashes';
  private static $aclass_hashes = array();
  private static $legal_prop_names = array(
    'display_html',
    'form_html',
    'invisible',
    'readonly',
    'set',
    );
  private $cls;
  private $dbaccess;
  private $dbaccess_id;
  private $encryptor;
  private $in_constructor = TRUE;  // TRUE while in constructor
  private $values = array();
  private $record_in_db = FALSE;
  private $needs_save = TRUE;
  private $attribute_properties = array();
  
  public function key_value()
  {
    switch (count($keys_list = $this->keys_list)) {
      case 1:
        $key = $keys_list[0];
        return $this->$key;
      case 0:
        throw new AnInstanceException("$this->cls_name::key_value(): Internal Error: object w/o keys");
      default:
        return $this->encode_key_values();
    }
  } // end of key_value()

  public function compute_key_values()
  {
    $ar = array();
    foreach ($this->keys_list as $key) {
      $ar[$key] = array_key_exists($key, $this->values) ? $this->values[$key] : NULL;
    }
    return $ar;
  } // end of compute_key_values()
  
  // public function key_values_as_str()
  // {
  //   return implode(', ', array_values($this->compute_key_values()));
  // } // end of key_values_as_str()

  public static function static_encode_array($ar)
  {
    return urlencode(serialize($ar));
  } // end of static_encode_array()
  
  public function encode_key_values()
  {
    return $this->key_values_complete() ? AnInstance::static_encode_array($this->compute_key_values()) : '';
  } // end of encode_key_values()

  public static function static_decode_key_values($str)
  {
    try {
      if (($tmp = unserialize(urldecode($str))) === FALSE) {
        ob_start();
        var_dump($str);
        echo "Failed to unserialize: $str [" . ob_get_clean() . "]\n";
        debug_print_backtrace();
      }
    } catch (Exception $e) {  }
    return $str ? unserialize(urldecode($str)) : '';
  } // end of static_decode_key_values()

  public function decode_key_values($str)
  {
    return preg_match('/^a%3A/', $str) ? AnInstance::static_decode_key_values($str) : $str;
  } // end of decode_key_values()
  
  // returns TRUE if all keys are set and are either Not empty OR have a filter property and satisfy it
  public function key_values_complete()
  {
    foreach ($this->keys_list as $key) {
      if (!isset($this->values[$key])) {
// echo "key_values_complete():$key is not set - returning FALSE\n";
        return FALSE;
      }
      if ($this->has_prop($key, 'filter')) {
        if (!preg_match($this->get_prop($key, 'filter'), $this->$key)) {
// echo "key_values_complete(): $key: '{$this->$key} fails filter: " . $this->get_prop($key, 'filter') . "\n";
            return FALSE;
        } else {
          continue;
        }
      }
      if (!$this->$key) {
// echo "key_values_complete():$key is empty - returning FALSE\n";
        return FALSE;
      }
    }
    return TRUE;
  } // end of key_values_complete()

  protected function __construct($cls_name, $dbaccess, $attr_values = array())
  {
    // sanity tests:
    if (!is_string($cls_name)) {
      throw new AnInstanceException("$this->cls_name::__construct(): arg0 is not a class name");
    }
    if (!($dbaccess instanceof DBAccess)) {
      throw new AnInstanceException("$cls_name::__construct($cls_name, dbaccess,...): dbaccess is not an instance of DBAccess");
    }
    $this->in_constructor = TRUE;

    // enqueue save on exit
    // FIXME: this has been causing problems - tends to be executed w/o stack frame
//    $dbaccess->register_close_function(array($this, 'save'));
    
    $this->cls = AClass::get_class_instance($cls_name);
    $this->dbaccess = $dbaccess;
    $this->dbaccess_id = (string)$dbaccess;
    $this->encryptor = AnEncryptor::get_encryptor($dbaccess, $this->cls->tablename);
    $attr_values = $this->cls->resolve_attr_values($attr_values);
    
    // check for model mismatches
    $this->check_model_mismatch();
    // this dance lets us create and/or re-define values at object instantiation
    //  while using them from database.
    foreach ($this->cls->keys_list as $key) {
      if (array_key_exists($key, $attr_values)) {
        $this->$key = $attr_values[$key];
      }
    }
    if ($this->key_values_complete()) {
      $this->load();
    }

    // initialize the undefined, non-key attributes. Defined attribute values are assigned
    // through __set(); Undefined are initialized NULL directly. [this is the only
    //  way to get a NULL into a DateTime object]
    foreach ($this->cls->value_names as $attr) {
      if (isset($attr_values[$attr])) {
        switch ($this->get_prop($attr, 'type')) {
          case 'join':
            $this->values[$attr] = AJoin::get_ajoin($this->dbaccess, $this->cls_name, $this->get_prop($attr, 'joins'));
            $this->put_prop($attr, 'set');
            break;
          default:
            $this->$attr = $attr_values[$attr];
            break;
        }
      } elseif (!isset($this->$attr)) {
        switch ($this->get_prop($attr, 'type')) {
          case 'join':
            $this->values[$attr] = AJoin::get_ajoin($this->dbaccess, $this->cls_name, $this->get_prop($attr, 'joins'));
            $this->put_prop($attr, 'set');
            break;
          case 'category':
            // defaults are illegal for category attributes
            $this->values[$attr] = NULL;
            break;
          case 'date':
          case 'time':
          case 'datetime':
            // we are doing this to explicitly force unset values for date & time to a string
            //  because they seem to come back as a string even when they are nulls - at
            //  least for sqlite. The database distinguishes between NULL and '', so equality
            //  tests on empty values don't work properly w/o this hack.
            if ($this->has_prop($attr, 'default')) {
              $this->values[$attr] = new DateTime($this->get_prop($attr, 'default'));
              $this->put_prop($attr, 'set');
            } else {
              $this->values[$attr] = '';
            }
            break;
          case 'set':
            if ($this->has_prop($attr, 'default')) {
              $this->$attr = $this->get_prop($attr, 'default');
              $this->put_prop($attr, 'set');
            } else {
              $this->values[$attr] = array();
            }
            break;
          default:
            if ($this->has_prop($attr, 'default')) {
              $this->values[$attr] = $this->get_prop($attr, 'default');
              $this->put_prop($attr, 'set');
            } else {
              $this->values[$attr] = NULL;
            }
            break;
        }
      }
    }
    $this->in_constructor = FALSE;
  } // end of __construct()

  // this makes no sense - there is no close() function defined for $this
  // public function __destroy()
  // {
  //   $this->close();
  //   $this->unregister_close_function(array($this, 'close'));
  // } // end of __destroy()
  
  public static function existsP($cls_name, $dbaccess, $attr_values = array())
  {
    if (!($cls = AClass::get_class_instance($cls_name))) {
      return FALSE;
    }
    if (!($dbaccess instanceof DBAccess)) {
      return FALSE;
    }
    $attr_values = $cls->resolve_attr_values($attr_values);
    $where = $dbaccess->escape_where($attr_values);
    $tmp = $dbaccess->select_from_table($cls->tablename, NULL, $where);
    return is_array($tmp) && count($tmp) > 0;
  } // end of existsP()
  
  public function instance_existsP($attr_values = array())
  {
    return AnInstance::existsP($this->cls_name, $this->dbaccess, $attr_values);
  } // end of instance_existsP()

  public function get_objects_where($where, $orderby = NULL)
  {
    $where = $where ? $this->dbaccess->escape_where($where) : '';
    $orderby = $orderby ? $this->dbaccess->escape_string($orderby) : '';
    $sql = "select * from $this->tablename $where $orderby";
    $tmp = $this->dbaccess->select_from_table($this->tablename, NULL, $where, $orderby);
    if (!$tmp) {
      // echo "get_objects_where(): query failed\nsql: '$sql'\nerror message: {$this->dbaccess->error()}\n";
      return array();
    }
    $ret = array();
    // get name of derived class
    // need to do this instead of $this->cls_name because they are not the same.
    $class_name = get_class($this);
    foreach ($tmp as $attribute_values) {
      $obj = new $class_name($this->dbaccess);
      $obj->assign_value_array($attribute_values);
      $ret[] = $obj;
    }
    return $ret;
  } // end of get_objects_where()
  
  public function equal($other, $check_all_fields = FALSE)
  {
    if ($this->cls_name != $other->cls_name) {
      // echo "not-equal: different classes: $this->cls_name != $other->cls_name\n";
      return FALSE;
    }
    if ($check_all_fields) {
      foreach ($this->attribute_names as $attr) {
        if ($this->$attr != $other->$attr) {
          // echo "not-equal: different values for attribute $attr: '{$this->$attr}' != '{$other->$attr}'\n";
          return FALSE;
        }
      }
    } else {
      foreach ($this->attribute_names as $attr) {
        if (isset($this->$attr)) {
          if (!isset($other->$attr) || $this->$attr != $other->$attr) {
            return FALSE;
          }
        } elseif (isset($other->$attr)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  } // end of equal()

  public function __toString()
  {
    $tmp = $this->compute_key_values();
    $ar = array();
    foreach ($this->keys_list as $key) {
      $ar[] = "$key:" . $this->asString($key);
    }
    return "$this->cls_name(" . implode(', ', $ar) . ')';
  } // end of __toString()
  
  public function pile_keys($attr) {
    if ($this->get_prop($attr, 'type') != 'pile') {
      throw new AnInstanceException("AnInstance::pile_keys($attr): '$attr' is not a pile");
    }
    return array_keys($this->values[$attr]);
  } // end of pile_keys()
  
  public function pile_put($attr, $name, $value) {
    if ($this->get_prop($attr, 'type') != 'pile') {
      throw new AnInstanceException("AnInstance::pile_put($attr): '$attr' is not a pile");
    }
    $this->values[$attr][$name] = $value;
  } // end of pile_put()
  
  public function pile_get($attr, $name) {
    if ($this->get_prop($attr, 'type') != 'pile') {
      throw new AnInstanceException("AnInstance::pile_get($attr): '$attr' is not a pile");
    }
    return array_key_exists($name, $this->values[$attr]) ? $this->values[$attr][$name] : FALSE;
  } // end of pile_get()

  public function __get($attr)
  {
    // FIXME: (someday) - this was a mistake - all of these attributes should
    //  be syntactically different from legal data attributes. Probably begin them with
    //  and underscore, which we will strip when accessing the class object - or some such
    switch ($attr) {
      case 'attribute_defs':
      case 'attribute_dictionary':
      case 'attribute_names':
      case 'attribute_token_patterns':
      case 'cls_name':
      case 'enctype':
      case 'keys_list':
      case 'tablename':
      case 'value_names':
        return $this->cls->$attr;
      case 'dbaccess':
      case 'record_in_db':
        return $this->$attr;
      case 'default_title':
        $attr_list = $this->cls->default_title_ar ? $this->cls->default_title_ar : $this->keys_list;
        $ar = array();
        foreach ($attr_list as $attr) {
          $ar[] = $this->$attr;
        }
        return implode(' / ', $ar);
      default:
        break;
    }
    // always throw exception when attempting to get a illegal attribute
    if (in_array($attr, $this->cls->attribute_names)) {
      if (!$this->has_prop($attr, 'set')) {
        return FALSE;
        // return '';  // FIXME: do something better than returning ''
        // throw new AnInstanceException("$this->cls_name::__get($attr): Attribute '$attr': Value Not Set");
      }

      // return instance value
      switch ($this->get_prop($attr, 'type')) {
        case 'join':
          $ajoin = $this->values[$attr];
          return $ajoin->select_joined_objects($this);
        case 'float':
          return $this->has_prop($attr, 'precision')
            ? round($this->values[$attr], $this->get_prop($attr, 'precision'))
            : $this->values[$attr];
        default:
          return $this->values[$attr];
      }
    } else {
      throw new AnInstanceException("$this->cls_name::__get($attr): Attribute Name Error");
    }
  } // end of __get()

  public function join_value_of($attr)
  {
    if (!$this->has_prop($attr, 'joins')) {
      throw new AnInstanceException("{$this->cls_name}::join_value_of($attr): $attr is not a join field");
    }

    // this should invoke __get() which will return the joined objects
    if (($foreign_obj_ar = $this->$attr)) {
      $foreign_attr = $this->get_prop($attr, 'join_display_field');
      return implode(', ', array_map(create_function('$o', "return \$o->{$foreign_attr};"), $foreign_obj_ar));
    } else {
      return '';
    }
  } // end of join_value_of()
  
  public function link_value_of($attr)
  {
    if (!$this->get_prop($attr, 'type') == 'link') {
      throw new AnInstanceException("$this->cls_name::link_value_of($attr): $attr is not a 'link'");
    }
    if ($this->$attr) {
      $foreign_class_name = $this->get_prop($attr, 'joins');
      if (!class_exists($foreign_class_name)) {
        ObjectInfo::do_require_once($foreign_class_name . ".php");
      }
      return new $foreign_class_name($this->dbaccess, $this->$attr);
    } else {
      return FALSE;
    }
  } // end of link_value_of()

  public function __set($attr, $value)
  {
    // if $attr is not an attribute, then throw exception if database is on-line,
    //   unless we have a pile
    if (!in_array($attr, $this->cls->attribute_names)) {
      if ($this->dbaccess->on_line == 'T') {
        throw new AnInstanceException("$this->cls_name::__get($attr): Attribute Name Error");
      }
      return;
    }
    // make keys 'write once'
    if ($this->has_prop($attr, 'immutable') && $this->has_prop($attr, 'set')) {
      throw new AnInstanceException("$this->cls_name::__set($attr, ...): Attempt to redefine immutable value: from {$this->$attr} to $value");
    }
    
    // this prepares to test to see if this is a modifying assignment. See needs_save logic
    //   below the 'switch' for the completion. [if attr is not previously defined, we set
    //   needs_save to latch TRUE]
    if (isset($this->values[$attr])) {
      $old_value = $this->values[$attr];
    } else {
      $this->needs_save = TRUE;
    }
    
    // do assignment according to data type
    switch ($this->cls->get_prop($attr,'type')) {
      case 'link':
        $this->assign_link_value($attr, $value);
        break;
      case 'join';
      // echo "__set($attr, $value\n)";
        $this->assign_join_value($attr, $value);
        break;
      case 'category':
        $this->assign_category_value($attr, $value);
        break;
      case 'text':
        // we always allow something to be set to an empty value - ignoring filter
        if ($value && $this->has_prop($attr, 'filter')) {
          $regx = $this->get_prop($attr, 'filter');
          if (!preg_match($regx, $value)) {
            throw new AnInstanceException("$this->cls_name::__set($attr, $value): Value fails to satisfy $regx");
          }
        }
        $this->values[$attr] = $value;
        break;
      case 'blob':
        $this->values[$attr] = $value;
        break;
      case 'pile':
        throw new AnInstanceException("AnInstance::__set():Illegal attempt to assign to pile attribute: '$attr'");
      case 'enum':
        if (!in_array($value, $this->get_prop($attr,'enums')) ) {
          throw new AnInstanceException("$this->cls_name::__set($attr, '$value'): Illegal value");
        }
        $this->values[$attr] = $value;
        break;
      case 'set':
        // Set's take arrays, comma separated lists of strings and whatever can be transformed into as tring
        if (!is_array($value)) {
          $value = preg_split('/\s*,\s*/', (string)$value);
        }
        if (is_array($value)) {
          $this->values[$attr] = array_intersect($this->get_prop($attr, 'enums'), $value);
        } else {
          $this->values[$attr] = array();
        }
        break;
        // if (!in_array($value, $this->get_prop($attr,'enums')) )
        //   break;
        // if (!in_array($value, $this->values[$attr]))
        //   $this->values[$attr][] = $value;
        // break;
      case 'char':
      case 'varchar':
      case 'email':
      case 'file':
        // we always allow something to be set to an empty value - ignoring filter
        if ($value && $this->has_prop($attr, 'filter')) {
          $regx = $this->get_prop($attr, 'filter');
          if (!preg_match($regx, $value)) {
            throw new AnInstanceException("$this->cls_name::__set($attr, $value): Value fails to satisfy $regx");
          }
        }
        $this->values[$attr] = substr($value, 0, $this->cls->get_prop($attr, 'width'));
        break;
      case 'int':
        $this->values[$attr] = intval($value);
        break;
      case 'float':
        $this->values[$attr] = floatval($value);
        break;
      case 'date':
      case 'time':
      case 'datetime':
        $this->values[$attr] = $value instanceof DateTime ? $value : new DateTime($value);
        break;
      default:
        throw new AnInstanceException('AnInstance::__set($attr, value): Illegal property type: ' . $this->get_prop($attr, 'type'));
        break;
    }
    
    // latch needs_save to TRUE if the value has changed
    //  We need the isset() test because assigning to a category type does an implicit
    //   save which will reset a needs_save of TRUE to FALSE w/o setting $old_value
    $this->needs_save = $this->needs_save || (isset($old_value) && $old_value != $this->values[$attr]);

    // mark as set
    $this->put_prop($attr, 'set');
  } // end of __set()
  
  private function assign_join_helper($attr, $value)
  {
    if ($this->get_prop($attr, 'type') != 'join') {
      throw new AnInstanceException("$this->cls_name::assign_join_value($attr): $attr is not a 'join' field");
    }

    if (!$value) {
      return array();
    }
    
    // sanity check $value
    $foreign_class = $this->get_prop($attr, 'joins');
    $foreign_class_obj = AClass::get_class_instance($foreign_class);
    $foreign_instance_list = array();
    if (is_array($value)) {
      foreach ($value as $tmp) {
        $foreign_instance_list[] = $tmp instanceof $foreign_class ? $tmp : new $foreign_class($this->dbaccess, $tmp);
      }
      return $foreign_instance_list;
    } else {
      return array( $value instanceof $foreign_class ? $value : new $foreign_class($this->dbaccess, $value) );
    }
  } // end of assign_join_helper()

  private function assign_join_value($attr, $value)
  {
    $ajoin = $this->values[$attr];
    // sanity and value conversions
    // if assigning NULL, then clear, mark and return
    if (!$value) {
      $ajoin->delete_joins_for($this);
      return;
    }

    $update_list = $this->assign_join_helper($attr, $value);
    $ajoin->update_join($this, $update_list);
  } // end of assign_join_value()

  public function add_to_join($attr, $value)
  {
    $ajoin = $this->values[$attr];
    $add_list = $this->assign_join_helper($attr, $value);
    if ($this->$attr) {
      $add_list = array_diff($add_list, $this->$attr);
    }
    foreach ($add_list as $foreign_instance) {
      $foreign_instance->save();
      $ajoin->add_to_join($this, $foreign_instance);
    }
  } // end of add_to_join()
  
  public function delete_from_join($attr, $value)
  {
    $ajoin = $this->values[$attr];
    foreach ($this->assign_join_helper($attr, $value) as $foreign_instance) {
      $ajoin->delete_from_join($this, $foreign_instance);
    }
  } // end of delete_from_join()
  
  // link support
  
  private function assign_link_value($attr, $value)
  {
    if ($this->get_prop($attr, 'type') != 'link') {
      throw new AnInstanceException("$this->cls_name::assign_link_value($attr, value): attribute '$attr' is not a link");
    }
    if ($value instanceof AnInstance) {
      if ($value->cls_name != $this->get_prop($attr, 'joins')) {
        throw new AnInstanceException("$this->cls_name::assign_link_value($attr, value): value is not an instance of " . $this->get_prop($attr, 'joins'));
      }
      $value = $value->key_value();
    } elseif (is_array($value)) {  // assume this is a key value array
      if (count($value) == 1) {
        $tmp = array_values($value);
        $value = $tmp[0];
      } else {
        $value = AnInstance::static_encode_array($value);
      }
    }
    if (!isset($this->values[$attr]) || $value != $this->values[$attr]) {
      $this->values[$attr] = $value;
      $this->mark_dirty();
    }
  } // end of assign_link_value()

  // category support
  
  private static function cmp_category($a, $b)
  {
    $a_ar = explode('_', $a);
    $b_ar = explode('_', $b);
    if (($a_len = count($a_ar)) != ($b_len = count($b_ar))) {
      return $a_len < $b_len ? -1 : 1;
    }
    for ($idx = 0; $idx < $a_len;$idx += 1) {
      if ($tmp = strcmp($a_ar[$idx], $b_ar[$idx])) {
        return $tmp < 0 ? -1 : 1;
      }
    }
    return 0;
  } // end of cmp_category()

  private function assign_category_value($attr, $value)
  {
    if ($this->get_prop($attr, 'type') != 'category') {
      throw new AnInstanceException("$this->cls_name::category_objects_of($attr): $attr is not a category type");
    }
    $current_paths = $this->category_paths_of($attr);
    // NOTE: the assignments to the values array MUST occur before the category operations
    //  because the category operations use AJoin which (conservatively) saves all joining
    //  instances prior to updating the join table. This screws up the sequence of operations
    //  in AnInstance::__set(). One assumption of __set() is that there will be no calls
    //  to save() while it is running - which this violates. I don't know a clean method
    //  around this w/o making AJoin manipulation less robust.
    if (!$value) {
      $this->values[$attr] = NULL;
      foreach ($current_paths as $category) {
        Category::delete_from_category($category, $this);
      }
    } else {
      if (is_string($value)) {
        $ar = array_unique(preg_split('/\s*,\s*/', $value));
      } elseif (is_array($value)) {
        $ar = array_unique($value);
      } else {
        throw new AnInstanceException("$this->cls_name::assign_category_value($attr, value): Illegal value");
      }
      
      usort($ar, array('AnInstance', 'cmp_category'));
      $paths_adding = array_diff($ar, $current_paths);
      
      // check all subpaths to make sure they are subpaths of a path in the root
      $category_root_ar = explode(',', $this->get_prop($attr, 'category_root'));
      foreach (array_diff($paths_adding, $category_root_ar) as $new_path) {
        if (!Category::subpath_of_path_groupP($new_path, $category_root_ar)) {
          throw new AnInstanceException(get_class($this) . "->{$attr} =  value:Attempt to add path '$new_path' which is not subpath of any root in $attr");
        }
      }
      $this->values[$attr] = implode(',', $ar);
      foreach (array_diff($current_paths, $ar) as $category) {
        Category::delete_from_category($category, $this);
      }
      foreach ($paths_adding as $category) {
        Category::add_to_category($category, $this);
      }
    }
  } // end of assign_category_value()
  
  public function default_category($attr)
  {
    return preg_replace('/,.*$/', '', $this->$attr);
  } // end of default_category()

  public function category_paths_of($attr)
  {
    if ($this->get_prop($attr, 'type') != 'category') {
      throw new AnInstanceException("$this->cls_name::category_paths_of($attr): $attr is not a category type");
    }
    return isset($this->values[$attr]) && $this->values[$attr] ? explode(',', $this->values[$attr]) : array();
  } // end of category_paths_of()

  // returns array of all category objects _this_ is in
  public function category_objects_of($attr)
  {
    $ar = array();
    foreach ($this->category_paths_of($attr) as $path) {
      $ar[] = new Category($this->dbaccess, $path);
    }
    return $ar;
  } // end of category_objects_of()
  
  public function add_category($attr, $category_path)
  {
    if (!Category::subpath_of_path_groupP($category_path, $this->get_prop($attr, 'category_root'))) {
      throw new AnInstanceException("::add_category($attr, $category_path): $category_path not child of any of '"
        . $this->get_prop($attr, 'category_root') . "'");
    }
    
    // add this path and all ancestors
    $cat_obj = new Category($this->dbaccess, $category_path);
    if ($cat_obj->dirtyP()) {
      $cat_obj->save();
    }
    // recursively add category_path and all antecedents to the ajoin table
    Category::add_to_category($cat_obj->path, $this);
    
    // $ar = array_unique(array_merge($this->category_paths_of($attr), $cat_obj->antecedents(TRUE)));
    $ar = array_unique(array_merge($this->category_paths_of($attr), array($category_path)));
    usort($ar, array('AnInstance', 'cmp_category'));
    $this->$attr = implode(',', $ar);
    $this->save();
  } // end of add_to_category()
  
  public function delete_category($attr, $category_path)
  {
    $cat_obj = new Category($this->dbaccess, $category_path);
    $ar = array_diff($this->category_paths_of($attr), $cat_obj->descendents(TRUE));
    $ar =  array_filter($ar, create_function('$x', "return \$x != '$category_path';"));
    usort($ar, array('AnInstance', 'cmp_category'));
    $this->$attr = implode(',', $ar);
    Category::delete_from_category($category_path, $this);
    $this->save();
  } // end of add_to_category()

  public function select_objects_in_category($attr, $subpath, $other_class)
  {
    if ($this->get_prop($attr, 'type') != 'category') {
      throw new AnInstanceException("$this->cls_name::select_objects_in_category($attr, $other_class): $attr is not a category data type");
    }
    $category_list = $this->category_paths_of($attr);
    $found = FALSE;
    foreach ($category_list as $cat) {
      if (preg_match("/^$cat/", $subpath)) {
        $found = TRUE;
        break;
      }
    }
    return $found ? Category::get_instances_for_category($subpath, $this->dbaccess, $other_class) : array();
  } // end of select_objects_in_category()
  
  public function delete_category_references_test($category_path)
  {
    $this->delete_category_references($category_path, TRUE);
  } // end of delete_category_references_test()

  public function delete_category_references($category_path, $dry_run = FALSE)
  {
    foreach ($this->attribute_names as $attr) {
      if ($this->get_prop($attr, 'type') != 'category') {
        continue;
      }
      $category_roots = explode(',', $this->get_prop($attr, 'category_root'));
      // $category_root_path = substr($category_root, 0, strlen($category_root) - 1);
      if (in_array($category_path, $category_roots)) {
        throw new AnInstanceException("$this->cls_name::delete_category_references($category_path): attempt to delete Category used as root for $attr");
      }
      if (!$dry_run) {
        foreach ($category_roots as $cr) {
          if (Category::subpath_of_pathP($category_path, $cr)) {
            $categories = $this->category_paths_of($attr);
            foreach ($categories as $cat) {
              if ($category_path == $cat || Category::subpath_of_pathP($cat, $category_path)) {
                $this->delete_category($attr, $cat);
              }
            }
          }
        }
      }
    }
  } // end of delete_category_references()

  // end of category support

  public function __isset($attr)
  {
    return $this->has_prop($attr, 'set');
  } // end of __isset()
  
  public function __unset($attr)
  {
    switch ($this->get_prop($attr, 'type')) {
      case 'pile':
        throw new AnInstanceException("AnInstance::__unset(): attempt to unset pile attribute: $attr");
      default:
        $this->del_prop($attr, 'set');
        break;
    }
  } // end of __unset()

  private function attr_asString($attr, $value, $fmt = NULL)
  {
    if (!in_array($attr, $this->cls->attribute_names)) {
      throw new AnInstanceException("$this->cls_name::__get($attr): Attribute Name Error");
    }
    switch ($this->cls->get_prop($attr,'type')) {
      case 'blob':
        // this may be wrong. It might be better to return a hex value or something
        return '';
      case 'pile':
        $ar = array();
        foreach ($this->values[$attr] as $key => $val) {
          $ar[] = "$key:'$val'";
        }
        return implode(', ', $ar);
      case 'text':
      case 'char':
      case 'varchar':
      case 'enum':
      case 'file':
      case 'email':
        return $fmt ? sprintf($fmt, $value) : $value;
      case 'set':
        $tmp = implode(',', $value);
        return $fmt ? sprintf($fmt, $tmp) : $tmp;
      case 'link':
        return (string)$this->link_value_of($attr);
      case 'join':
        return $this->join_value_of($attr);
      case 'category':
        return $this->$attr;
      case 'float':
        if (!$fmt) {
          $fmt = $this->get_prop($attr, 'format');
        }
        return sprintf($fmt, $value);
      case 'int':
        return $fmt ? sprintf($fmt, $value) : (string)$value;
      case 'date': return $value instanceof DateTime ? $value->format($fmt ? $fmt : 'Y-m-d') : $value;
      case 'time': return $value instanceof DateTime ? $value->format($fmt ? $fmt : 'H:i:s') : $value;
      case 'datetime': return $value instanceof DateTime ? $value->format($fmt ? $fmt : 'Y-m-d H:i:s') : $value;
      default: throw new AnInstanceException("$this->cls_name::attr_asString($attr, ...): Illegal attribute type");
    }
  } // end of attr_asString()
  
  public function asString($attr, $fmt = NULL)
  {
    return isset($this->values[$attr]) ? $this->attr_asString($attr, $this->values[$attr], $fmt) : '';
  } // end of asString()

  public function put_prop($name, $prop_name, $value = TRUE)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AnInstanceException("$this->cls_name::put_prop($name, $prop_name): Illegal Attribute name '$nam'");
    }
    if (!in_array($prop_name, AnInstance::$legal_prop_names)) {
      throw new AnInstanceException("$this->cls_name::put_prop($name, $prop_name): Illegal Property name '$prop_name'");
    }
    $this->attribute_properties[$name][$prop_name] = $value;
  } // end of put_prop()
  
  public function append_to_prop($name, $prop_name, $value)
  {
    if ($this->has_prop($name, $prop_name)) {
      $cur_val = $this->get_prop($name, $prop_name);
      if (is_bool($cur_val)) {
        throw new AnInstanceException("$this->cls_name::append_to_prop($name, $prop_name, value): cannot append to boolean property");
      }
      if ($cur_val) {
        $value = "$cur_val $value";
      }
    }
    $this->put_prop($name, $prop_name, $value);
  } // end of append_to_prop()

  public function del_prop($name, $prop_name)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AnInstanceException("$this->cls_name::del_prop($name, $prop_name): Illegal Attribute name '$nam'");
    }
    if (!in_array($prop_name, AnInstance::$legal_prop_names)) {
      throw new AnInstanceException("$this->cls_name::del_prop($name, $prop_name): Illegal Property name '$prop_name'");
    }
    if (isset($this->attribute_properties[$name][$prop_name])) {
      unset($this->attribute_properties[$name][$prop_name]);
    }
  } // end of del_prop()

  public function has_prop($name, $prop_name)
  {
    if (!in_array($name, $this->cls->attribute_names)) {
      throw new AnInstanceException("$this->cls_name::has_prop($name, $prop_name): Illegal Attribute name '$name'");
    }
    if (in_array($prop_name, AnInstance::$legal_prop_names)) {
      if (isset($this->attribute_properties[$name][$prop_name])) {
        return $this->attribute_properties[$name][$prop_name];
      } elseif (!$this->cls->prop_name_exists($prop_name)) {
        return FALSE;
      }
      // falls through
    }
    return $this->cls->has_prop($name, $prop_name);
  } // end of has_prop()
  
  public function get_prop($name, $prop_name)
  {
    if (!in_array($name, $this->attribute_names)) {
      throw new AnInstanceException("$this->cls_name::get_prop($name, $prop_name): Illegal Attribute name '$name'");
    }
    if (in_array($prop_name, AnInstance::$legal_prop_names)) {
      if (isset($this->attribute_properties[$name][$prop_name])) {
        return $this->attribute_properties[$name][$prop_name];
      } elseif (!$this->cls->prop_name_exists($prop_name)) {
        return  FALSE;
      }
      // falls through
    }
    return $this->cls->get_prop($name, $prop_name);
  } // end of get_prop()

  protected function load_values()
  {
    if (!$this->key_values_complete()) {
      return FALSE;
    }
    $where_clause = $this->dbaccess->escape_where($this->compute_key_values());
    $tmp = $this->dbaccess->select_from_table($this->tablename, NULL, $where_clause);
    if (!is_array($tmp)) {
      if (!$this->dbaccess->table_exists($this->tablename)) {
        echo "creating table\n";
        $this->cls->create_table($this->dbaccess);
      }
      $tmp = $this->dbaccess->select_from_table($this->tablename, NULL, $where_clause);
    }

    if (!is_array($tmp)) {
      throw new AnInstanceException("{$this->cls_name}::load($where): Bad load: select did not return an array: "
        . $this->dbaccess->error());
    }
    if (count($tmp) > 1) {
      throw new AnInstanceException("{$this->cls_name}::load($where): Bad load: select returned "
        . count($tmp) . " rows: " . $this->dbaccess->error());
    }
    return count($tmp) == 1 ? $tmp[0] : FALSE;
  } // end of load_values()
  
  public function assign_value_array($value_array)
  {
    // load values which are defined, discarding excess
    foreach ($this->attribute_names as $attr) {
      if ($this->has_prop($attr, 'immutable') && $this->has_prop($attr, 'set')) {
        continue;
      }
      switch ($this->get_prop($attr, 'type')) {
        case 'join':
          $this->values[$attr] = AJoin::get_ajoin($this->dbaccess, $this->cls_name, $this->get_prop($attr, 'joins'));
          break;
        default:
          $this->values[$attr] = isset($value_array[$attr])
            ? $this->cls->db_to_obj($attr, $value_array[$attr], $this->encryptor) : NULL;
          break;
      }
      $this->put_prop($attr, 'set');
    }
    
    $this->record_in_db = TRUE;
    $this->needs_save = FALSE;
  } // end of assign_value_array()

  public function load()
  {
    // load fails
    if (!($load_values = $this->load_values())) {
      $this->record_in_db = FALSE;
      $this->needs_save = TRUE;
      return;
    }

    // load values succeeds - copy to object, but throw exception on conflict
    $this->assign_value_array($load_values);
  } // end of load()

  private function insert_new_record($db_values, $die_on_exception = FALSE)
  {
    if (!$this->key_values_complete()) {
      throw new AnInstanceException("$this->cls_name::insert_new_record(): key values incompletely specified");
    }
    if ($this->record_in_db) {
      throw new AnInstanceException("$this->cls_name::insert_new_record(): record already in database - cannot insert");
    }

    // insert and return value or throw exception
    $result = $this->dbaccess->insert_into_table($this->tablename, $db_values);
    if (!$result && $die_on_exception) throw new AnInstanceException('fucked up!!!! ' . $this->dbaccess->error());
    return $this->record_in_db = $result ? TRUE : FALSE;
  } // end of insert_new_record()

  public function dirtyP()
  {
    return $this->needs_save;
  } // end of needs_saving()

  public function mark_saved()
  {
    $this->needs_save = FALSE;
  } // end of mark_saved()

  public function mark_dirty()
  {
    $this->needs_save = TRUE;
  } // end of mark_dirty()

  public function save()
  {
    // discard save if record is not modified or database is in read only mode
    if (!$this->needs_save) {
      return TRUE;
    } else if ($this->dbaccess->on_line == 'R') {
      return $this->needs_save == TRUE ? FALSE : TRUE;
    }
    $db_values = array();
    foreach ($this->cls->attribute_names as $attr) {
      if (!isset($this->values[$attr])) {
        $this->values[$attr] = $this->has_prop($attr, 'default') ? $this->get_prop($attr, 'default') : NULL;
      }
      if ($this->has_prop($attr, 'sql_type')) {
        $db_values[$attr] = $this->cls->obj_to_db($attr, $this->values[$attr], $this->encryptor);
      }
    }
    if ($this->key_values_complete()) {
      if (!$this->record_in_db) {
        if (!($result = $this->insert_new_record($db_values))) {
          $result = $this->dbaccess->update_table($this->tablename, $db_values, $this->compute_key_values());
        }
      } else {
        $result = $this->dbaccess->update_table($this->tablename, $db_values, $this->compute_key_values());
      }
    } else {
      $result = FALSE;
echo $this->dump();
      throw new AnInstanceException("$this->cls_name::save(): unable to save - key values not fully defined: "
        . $this->dbaccess->error());
    }
    if (!$result) {
      throw new AnInstanceException("{$this->cls_name}: Record Not Saved: " . $this->dbaccess->error() . "\n");
    } elseif (Globals::$dbaccess->on_line == 'T') {
      // WORRY ABOUT THIS - only mark archive stale if site is on line.
      require_once('StateMgt.php');
      StateMgt::handle_event('SAVE_RECORD');
      // change_site_state('archive_stale', 'T', $this->dbaccess);
    }
    $this->needs_save = FALSE;
    return $this->record_in_db = ($result !== FALSE ? TRUE : FALSE);
  } // end of save()


  // NOTE: These comments are out of date.
  // model mismatch is declared and Read-Only entered if:
  //  - the hashes table doesn't exist
  //  - if no entry if found for _this_ class in the hashes table
  //  - if the hash value for the this object doesn't match the value in the table.
  private function check_model_mismatch()
  {
    if (!isset(AnInstance::$aclass_hashes[$this->dbaccess_id])) {
      // if $aclass_hashes isn't loaded, try to load it
      // if aclass_hashes doesn't exist, then go Off Line
      if (!$this->dbaccess->table_exists(AnInstance::ACLASS_HASHES_TABLENAME)) {
        AClass::create_aclass_hashes_table(Globals::$dbaccess, TRUE);
        // $this->enter_readonly_mode('X');
        // require_once('StateMgt.php');
        // StateMgt::handle_event('ILLEGAL_EDIT');
        // Globals::$session_obj->add_message("ERROR: AClass Hash Tables Missing: Have Administrator Rebuild Them");
        return;
      }
      
      // load table
      $ar = array();
      $tmp = $this->dbaccess->select_from_table(AnInstance::ACLASS_HASHES_TABLENAME);
      foreach ($tmp as $row) {
        $ar[$row['cls_name']] = $row;
      }
      AnInstance::$aclass_hashes[$this->dbaccess_id] = $ar;
    }
    
    // at this point we know that we have the aclass_hashes table loaded, so we can check
    //  for a mismatch
    if (!isset(AnInstance::$aclass_hashes[$this->dbaccess_id][$this->cls_name])) {
      // it is an ILLEGEL_EDIT event iff there are rows in the table, otherwise update hashes table
      //   here and in database
      if (!$this->dbaccess->table_exists($this->tablename)) {
        $this->cls->create_table($this->dbaccess);
        $this->dbaccess->insert_into_table(AnInstance::ACLASS_HASHES_TABLENAME,
            $this->cls->aclass_instance_hashes_array(), array('cls_name' => $this->cls_name));
        $obj_info = new ObjectInfo($this->dbaccess, $this->cls_name);
        $obj_info->manageable = class_exists("{$this->cls_name}Manager") ? 'Y' : 'N';
        $obj_info->save();
      } elseif ($this->dbaccess->rows_in_table($this->tablename)) {
        require_once('StateMgt.php');
        echo "Illegal Edit of $this->cls_name\n";
        StateMgt::handle_event('ILLEGAL_EDIT');
      } else {
         $this->dbaccess->update_table(AnInstance::ACLASS_HASHES_TABLENAME,
            $this->cls->aclass_instance_hashes_array(), array('cls_name' => $this->cls_name));
        AnInstance::$aclass_hashes[$this->dbaccess_id][$this->cls_name] =
          $this->cls->aclass_instance_hashes_array();
      }
    } else {
      $tmp = AnInstance::$aclass_hashes[$this->dbaccess_id][$this->cls_name];
      // if the hash doesn't match, we do something
      if($this->cls->hash != $tmp['hash']) {
        // if there is no data, this is a model mismatch OR (if keys have changed) an Illegal Edit
        if ($this->dbaccess->rows_in_table($this->tablename)) {
          require_once('StateMgt.php');
          StateMgt::handle_event($this->cls->key_hash == $tmp['key_hash']
            ? 'MODEL_MISMATCH_EDIT' : 'ILLEGAL_EDIT');
        } else {
          // otherwise, it's benign - just change the hash value
          $hashes_tmp = $this->cls->aclass_instance_hashes_array();
          $this->dbaccess->update_table(AnInstance::ACLASS_HASHES_TABLENAME,
              $hashes_tmp, array('cls_name' => $hashes_tmp['cls_name']));
          AnInstance::$aclass_hashes[$this->dbaccess_id][$this->cls_name]['hash'] = $hashes_tmp['hash'];
          AnInstance::$aclass_hashes[$this->dbaccess_id][$this->cls_name]['key_hash'] =
            $hashes_tmp['key_hash'];
        }
      }
    }
  } // end of check_model_mismatch()

  public static function php_create_string($dbaccess, $object_name_list, $dump_dir)
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
    
    foreach ($object_name_list as $object_name) {
      try {
        if (!class_exists($object_name)) {
          require_once($object_name . ".php");
        }
        // get_class_instance() throws an exception for classes which are not AnInstance extensions
        $object_class_instance = AClass::get_class_instance($object_name);
        echo "Dumping $object_name\n";
        $f = fopen($dump_dir . DIRECTORY_SEPARATOR . "{$object_name}.dump", "w");
        fwrite($f, "<?php\n");
        fwrite($f, "if (!class_exists('$object_name')) require_once('{$object_name}.php');\n");
        fwrite($f, "\$obj = new $object_name(\$dbaccess);\n");
        fwrite($f, "if (!function_exists('map_data')) {function map_data(\$a, \$b){return \$b;}}\n");
        // NOTE: this code does NOT use the AClass data model, but uses the data fields actually
        //  in the table. This allows us to rename fields and preserve the data by creating
        //  an appropriate map function. If we used the data model for the object here, we
        //  would lose the data from renamed fields because they would look like they were deleted.
        $tmp = $dbaccess->select_from_table($object_class_instance->tablename);
        if ($tmp) {
          foreach ($tmp as $row) {
            fwrite($f, "\$dbaccess->insert_into_table('$object_class_instance->tablename',"
            . " map_data('$object_name', unserialize(base64_decode('"
            . base64_encode(serialize($row))
            . "'))));\n");
          }
        }

        fwrite($f, "?>\n");
        fclose($f);
        
      } catch (AClassException $e) {
        // manage non AnInstance objects which are also durable.
        if (method_exists($object_name, 'php_create_string')) {
          if (call_user_func(array($object_name, 'php_create_string'), $dbaccess, $dump_dir)) {
            echo "Dumping $object_name\n";
          } else {
            echo "Dump of $object_name Failed\n";
          }
        } else {
          echo "Skipping $object_name\n";
        }
      }
    }
    return TRUE;
  } // end of php_create_string()
  
  public function delete()
  {
    if (($ajoin_list = AJoin::get_ajoins_for($this))) {
      foreach ($ajoin_list as $ajoin) {
        $ajoin->delete_joins_for($this);
      }
    }
    $this->dbaccess->delete_from_table($this->tablename, $this->compute_key_values());
  } // end of delete()
  
  private function _render_helper($template, $attr_values_extension = NULL)
  {
    // build ordered list of attr values
    $attr_token_patterns = array();
    $attr_values = array();
    foreach ($this->cls->attribute_names as $attr) {
      $attr_token_patterns[] = $this->attribute_token_patterns[$attr];
      $attr_values[] = $this->asString($attr);
    }
    
    if (is_array($attr_values_extension)) {
      foreach ($attr_values_extension as $key => $value) {
        $attr_token_patterns[] = '/{' . $key . '}/';
        $attr_values[] = $value;
      }
    }

    return preg_replace($attr_token_patterns, $attr_values, $template);
  } // end of _render_helper()
  
  public function render($top = NULL, $bottom = NULL)
  {
    $template = $top ? $top : "<ul class=\"obj-display-form\">\n";
    foreach ($this->attribute_names as $attr) {
      if (!$this->has_prop($attr, 'invisible') && !$this->has_prop($attr, 'private')) {
        $template .= $this->get_prop($attr, 'display_html') . "\n";
      }
    }
    $template .= $bottom ? $bottom : "</ul>\n";
    return $this->_render_helper($template);
  } // end of render()

  public function render_file($path)
  {
    static $prev_path = FALSE;
    static $prev_content = '';
    if ($path && $prev_path == $path) {
      return $this->interpolate_string($prev_content);
    } elseif (file_exists($path) && is_file($path) && is_readable($path)) {
      return $this->interpolate_string(($prev_content = file_get_contents($path)));
    } else {
      return $this->render();
    }
  } // end of render_file()

  public function render_include($file)
  {
    static $prev_file = FALSE;
    static $prev_content = '';
    if ($file && $prev_file != $file) {
      ob_start();
      $include_result = include($file);
      if ($include_result === FALSE) {
        return $this->render();
      }
      $prev_content = ob_get_clean();
    }

    return $this->interpolate_string($prev_content);
  } // end of render_include()
  
  // return a select list for a joined field
  public function select_elt_join_list($attr, $display_name, $classes = NULL, $attributes = NULL)
  {
    if (!$this->has_prop($attr, 'joins')) {
      throw new AnInstanceException("$this->cls_name::select_elt_join_list($attr, $element_name, $display_name): $attr is not a join field");
    }
    if (($attr_type = $this->get_prop($attr, 'type')) != 'join') {
      throw new AnInstanceException("$this->cls_name::select_elt_join_list($attr, ): $attr is not a join or a link");
    }
    $foreign_obj_name = $this->get_prop($attr, 'joins');
    if (!class_exists($foreign_obj_name)) {
      ObjectInfo::do_require_once($foreign_obj_name . ".php");
    }

    $selected_objects = $this->$attr;

    // get foreign object and list of possible values
    $foreign_obj = new $foreign_obj_name($this->dbaccess);
    
    $where_clause = $this->has_prop($attr, 'where') ? $this->interpolate_string($this->get_prop($attr, 'where'))
      : NULL;
    $foreign_obj_list = $foreign_obj->get_objects_where($where_clause, "order by $display_name");

    // construct select element
    $ar = array("<select name=\"$attr\" id=\"$attr\""
      . ($classes ? " class=\"$classes\"" : '')
      . ($attributes ? " $attributes" : '')
      . ">");
    foreach ($foreign_obj_list as $foreign_obj) {
      $selected_attribute = $selected_objects && in_array($foreign_obj, $selected_objects) ? 'selected' :'';
      $key_value = $foreign_obj->encode_key_values();
      $ar[] = "<option value=\"$key_value\" $selected_attribute>{$foreign_obj->$display_name}</option>";
    }
    $ar[] = "</select>";
    
    // return as string
    return implode("    " . "\n", $ar);
  } // end of select_elt_join_list()

  private function join_field_form_helper($attr)
  {
    // sanity checks
    if (!$this->has_prop($attr, 'joins')) {
      throw new AnInstanceException("$this->cls_name::$select_elt_join_list($attr): $attr does not join anything");
    }
    if (!$this->has_prop($attr, 'join_display_field')) {
      throw new AnInstanceException("$this->cls_name::$select_elt_join_list($attr): property 'join_display_field' not set");
    }

    // get instance of foreign class
    $join_class = $this->get_prop($attr, 'joins');
    if (!class_exists($join_class)) {
      ObjectInfo::do_require_once($join_class . ".php");
    }
    // $join_obj = new $join_class($this->dbaccess);

    // create new html_form select list
    $form_html = "<li style=\"clear:both\">\n"
      . $this->select_elt_join_list($attr, $this->get_prop($attr, 'join_display_field'),
            $this->get_prop($attr, 'form_classes'),
            $this->get_prop($attr, 'form_attributes') . " style=\"clear:both;float:right\" id=\"{$attr}\"")
      . "\n<label for=\"$attr\" >"
      . $this->get_prop($attr, 'title')
      . "<span class=\"required\">*</span></label>\n"
      . "</li>";
    $this->put_prop($attr, 'form_html', $form_html);
  } // end of join_field_form_helper()

  // return a select list for a joined field
  public function select_elt_link_list($attr, $display_name, $classes = NULL, $attributes = NULL)
  {
    if (!$this->has_prop($attr, 'joins')) {
      throw new AnInstanceException("$this->cls_name::select_elt_join_list($attr, $element_name, $display_name): $attr is not a join field");
    }
    if (($attr_type = $this->get_prop($attr, 'type')) != 'link') {
      throw new AnInstanceException("$this->cls_name::select_elt_join_list($attr, ): $attr is not a link");
    }
    $foreign_obj_name = $this->get_prop($attr, 'joins');
    if (!class_exists($foreign_obj_name)) {
      ObjectInfo::do_require_once($foreign_obj_name . ".php");
    }

    $selected_obj = isset($this->$attr) ? new $foreign_obj_name($this->dbaccess, $this->$attr) : FALSE;
    if (isset($this->$attr)) {
      $selected_obj = new $foreign_obj_name($this->dbaccess, $this->$attr);
      $selected_key = $selected_obj->key_value();
    } else {
      $selected_key = NULL;
    }

    // get foreign object and list of possible values
    $foreign_obj = new $foreign_obj_name($this->dbaccess);
    
    $where_clause = $this->has_prop($attr, 'where') ? $this->interpolate_string($this->get_prop($attr, 'where'))
      : NULL;
    $foreign_obj_list = $foreign_obj->get_objects_where($where_clause, "order by $display_name");

    // construct select element
    $ar = array("<select name=\"$attr\" id=\"$attr\""
      . ($classes ? " class=\"$classes\"" : '')
      . ($attributes ? " $attributes" : '')
      . ">");
    foreach ($foreign_obj_list as $foreign_obj) {
      $key_value = $foreign_obj->key_value();
      $selected_attribute = $selected_key == $key_value ? 'selected' : '';

      $ar[] = "<option value=\"$key_value\" $selected_attribute>{$foreign_obj->$display_name}</option>";
    }
    $ar[] = "</select>";
    
    // return as string
    return implode("    " . "\n", $ar);
  } // end of select_elt_link_list()

  private function link_field_form_helper($attr)
  {
    // sanity checks
    if (!$this->has_prop($attr, 'joins')) {
      throw new AnInstanceException("$this->cls_name::$select_elt_join_list($attr): $attr does not join anything");
    }
    if (!$this->has_prop($attr, 'join_display_field')) {
      throw new AnInstanceException("$this->cls_name::$select_elt_join_list($attr): property 'join_display_field' not set");
    }

    // get instance of foreign class
    $join_class = $this->get_prop($attr, 'joins');
    if (!class_exists($join_class)) {
      ObjectInfo::do_require_once($join_class . ".php");
    }
    // $join_obj = new $join_class($this->dbaccess);

    // create new html_form select list
    $form_html = "<li style=\"clear:both\">\n"
      . $this->select_elt_link_list($attr, $this->get_prop($attr, 'join_display_field'),
            $this->get_prop($attr, 'form_classes'),
            $this->get_prop($attr, 'form_attributes') . " style=\"clear:both;float:right\" id=\"{$attr}\"")
      . "\n<label for=\"$attr\" >"
      . $this->get_prop($attr, 'title')
      . "<span class=\"required\">*</span></label>\n"
      . "</li>";
    $this->put_prop($attr, 'form_html', $form_html);
  } // end of link_field_form_helper()


  public function form_field_func_helper($attr)
  {
    $func_name = $this->get_prop($attr, 'form_html_func');
    $form_html_template = $this->$func_name();
    
    $pattern_ar = array('/{form_attributes}/', '/{form_classes}/');
    $values_ar = array();
    $values_ar[] = ($this->has_prop($attr, 'form_attributes')
          ? $this->get_prop($attr, 'form_attributes') : '')
        .  " style=\"clear:both;float:right\" id=\"{$attr}\"";
    $values_ar[] = $this->has_prop($attr, 'form_classes') ? $this->get_prop($attr, 'form_classes') : '';
    // create new html_form select list
    $form_html = "<li style=\"clear:both\">\n"
      . preg_replace($pattern_ar, $values_ar, $form_html_template)
      . "\n<label for=\"$attr\" >" . $this->get_prop($attr, 'title')
      . ($this->has_prop($attr, 'required') ? "<span class=\"required\">*</span>" : '')
      . "</li>";
    $this->put_prop($attr, 'form_html', $form_html);
  } // end of form_field_func_helper()

  public function category_field_form_helper($attr)
  {
    $category_root = $this->get_prop($attr, 'category_root');
    $selected_list = $this->category_paths_of($attr);

    $pattern_ar = array('/{form_attributes}/', '/{form_classes}/');
    $values_ar = array();
    $values_ar[] = ($this->has_prop($attr, 'form_attributes')
          ? $this->get_prop($attr, 'form_attributes') : '')
        .  " style=\"clear:both;float:right\" id=\"{$attr}\"";
    $values_ar[] = $this->has_prop($attr, 'form_classes') ? $this->get_prop($attr, 'form_classes') : '';

    // use Category function to create an HTML select element
    $select_header = "<select name=\"{$attr}[]\" class=\"{form_classes}\" {form_attributes} multiple rows=\"8\">\n";
    $options_str = '';
    foreach (explode(',', $category_root) as $cr) {
      $options_str .= Category::options_elt_for_category($this->dbaccess, $cr, $selected_list,
          $this->has_prop($attr, 'category_deep'));
    }
    $form_html = "<li style=\"clear:both\">\n"
      . preg_replace($pattern_ar, $values_ar, $select_header)
      . $options_str
      . "</select>\n"
      . "\n<label for=\"$attr\" >" . $this->get_prop($attr, 'title')
      . ($this->has_prop($attr, 'required') ? "<span class=\"required\">*</span>" : '')
      . "</li>";
    $this->put_prop($attr, 'form_html', $form_html);
  } // end of form_field_func_helper()
 
  public function form($form_action = NULL, $top = NULL, $bottom = NULL, $actions = NULL)
  {
    if (!$form_action) {
      $form_action = $_SERVER['REQUEST_URI'];
    }
    $key_array = $this->encode_key_values();
    $template = "<form  action=\"$form_action\" method=\"post\" accept-charset=\"utf-8\" enctype=\"{$this->enctype}\">\n"
      . "<input type=\"hidden\" name=\"key_array\" value=\"$key_array\">\n"
      . "<ul class=\"obj-edit-form\">\n";
    if ($top) {
      $template .= trim(is_callable($top) ? call_user_func($top) . "\n" : $top) . "\n";
    }
    $attr_values_extension = array();
    foreach ($this->attribute_names as $attr) {
      if ($this->has_prop($attr, 'invisible')) {
        continue;
      }
      if ($this->has_prop($attr, 'form_html_func')) {
        $this->form_field_func_helper($attr);
      } else {
        switch ($this->get_prop($attr, 'type')) {
          case 'join':
            $this->join_field_form_helper($attr);
            break;
          case 'link':
            $this->link_field_form_helper($attr);
            break;
          case 'category':
            $this->category_field_form_helper($attr);
            break;
          default:
            break;
        }
      }
      if (($this->has_prop($attr, 'key') && $this->has_prop($attr, 'set'))
        || $this->has_prop($attr, 'readonly')) {
        $template .= $this->get_prop($attr, 'display_html');
      } else {
        $template .= $this->get_prop($attr, 'form_html') . "\n";
      }

      switch ($this->get_prop($attr, 'type')) {
        case 'enum':
          $enum_possible_vals = $this->get_prop($attr, 'enums');
          if (isset($this->$attr)) {
            $enum_current = $this->$attr;
          } else {
            $this->$attr = $enum_current = $enum_possible_vals[0];
          }
          foreach ($enum_possible_vals as $enum_val) {
            $attr_values_extension["$attr-{$enum_val}"] = $enum_val == $enum_current ? 'checked' : '';
          }
          break;
        case 'set':
          $enum_possible_vals = $this->get_prop($attr, 'enums');
          if (isset($this->$attr)) {
            $enum_current = $this->$attr;
          } else {
            $this->$attr = $enum_current = array();
          }
          foreach ($enum_possible_vals as $enum_val) {
            $attr_values_extension["$attr-{$enum_val}"] = in_array($enum_val, $enum_current) ? 'checked' : '';
          }
          break;
        default:
          break;
      }
    }
    if ($bottom) {
      $template .= trim(is_callable($bottom) ? call_user_func($bottom) : $bottom) . "\n";
    }
    if (!$actions) $actions = array('Save', 'Cancel', 'Delete');
    $template .= "<li style=\"clear:both\">";
    foreach ($actions as $act)
      $template .= "<input type=\"submit\" name=\"submit\" value=\"{$act}\" id=\"submit\"> ";

    $template .= "</ul>\n</form>\n";

    return $this->_render_helper($template, $attr_values_extension);
  } // end of form()

  public function interpolate_string($str, $reset = FALSE)
  {
    static $pattern_ar = NULL;
    static $value_ar = NULL;

    if ($reset || !$pattern_ar) {
      $pattern_ar = array();
      $value_ar = array();
      foreach ($this->attribute_names as $attr) {
        switch ($this->get_prop($attr, 'type')) {
          case 'file':
            break;
          case 'date':
          case 'time':
          case 'datetime':
            $pattern_ar[] = $this->attribute_token_patterns[$attr];
            $tmp = $this->$attr;
            $value_ar[] = $tmp instanceof DateTime ? $tmp->format('c') : '';
            break;
          case 'join':
            $pattern_ar[] = $this->attribute_token_patterns[$attr];
            $value_ar[] = $this->join_value_of($attr);
            break;
          default:
            $pattern_ar[] = $this->attribute_token_patterns[$attr];
            $value_ar[] = $this->$attr;
            break;
        }
      }
    }
    return preg_replace($pattern_ar, $value_ar, $str);
  } // end of interpolate_string()
  
  // returns absolute path to the directory where files are placed
  // for a file type attribute OR throws an exception
  // returns the absolute path to the file.
  public function file_abs_path($attr, $reset_interpolation_flag = FALSE)
  {
    // determine relative path and do sanity check
    $relative_path = $this->interpolate_string($this->get_prop($attr, 'path'),
        $reset_interpolation_flag);

    if ($relative_path[0] == '/' || $relative_path[0] == DIRECTORY_SEPARATOR) {
      $relative_path = substr($relative_path, 1);  // DANGER: this assumes a single leading '/'
    }
    if (!$relative_path) {
      throw new AnInstanceException("$this->cls_name::process_form(): file attribute '$attr': path property missing");
    }

    // create directories as needed
    $regx = DIRECTORY_SEPARATOR == '/' ? '/\//' : '/\/' . DIRECTORY_SEPARATOR . '/';
    $path_ar = preg_split($regx, $relative_path);
    $fname = array_pop($path_ar);
    $abs_path = $this->get_prop($attr, 'path_root');

    foreach ($path_ar as $dir) {
      $abs_path .= DIRECTORY_SEPARATOR . $dir;
      if (!file_exists($abs_path)) {
        if (mkdir($abs_path,0755) === FALSE) {
          throw new AnInstanceException("$this->cls_name::process_form(): Unable to create directory for file attribute $attr: $abs_path - correct directory permissions");
        }
      }
    }
    $abs_path .= DIRECTORY_SEPARATOR . $fname;
    return $abs_path;
  } // end of file_abs_path()

  public function process_form($rc)
  {
//    echo "process_form(): " . $this->cls_name . "\n";
    foreach ($this->keys_list as $key) {
      if (!isset($this->$key)) {
        $form_field = "safe_post_{$key}";
        if (isset($rc->$form_field)) {
          $this->$key = $rc->$form_field;
        }
      }
    }
    if (!$rc->safe_post_key_array) {
      $rc->safe_post_key_array = $this->encode_key_values();
    }
    
    // pass 1 - skip all 'file' data, but build list of file type attributes for processing step 2
    $file_attributes = array();
    foreach ($this->value_names as $attr) {
      if (isset($this->$attr) && $this->get_prop($attr, 'immutable')) {
        continue;
      }
      switch ($this->get_prop($attr, 'type')) {
        case 'file':
          // note that we need to process file data types lalter
          $file_attributes[] = $attr;
          break;
        case 'blob':
        case 'pile':
          // blobs & pile cannot receive input from a form
          break;
        case 'text':
          $form_field = "raw_post_{$attr}";
          if (isset($rc->$form_field)) {

            $new_value = preg_replace(
              array(
                '/(<\s*(?!\/?(p|div|h[1-6]|blockquote|a|span|em|strong|ul|ol|li|img))[^>]*>)/',
                '/&amp;/',   // restore ampersands for entity encoded text
                ),
              array(
                '',
                '&',  // restore ampersands for entity encoded text
                ),
              $rc->$form_field);
            if (!isset($this->$attr) || $new_value != $this->$attr) {
              $this->$attr = $new_value;
            }
          }
          break;
        case 'join':
          // note: __set() performs all necessary data validation
          $form_field = "safe_post_{$attr}";
          if (isset($rc->$form_field)) {
            $new_value = $rc->$form_field;
            $this->$attr = $new_value;
          }
          break;
        case 'category':
          $form_field = "safe_post_{$attr}";
          if (isset($rc->$form_field)) {
            $new_value = $rc->$form_field;
            $this->$attr = $new_value;
          }
          break;
        default:
          // note: __set() performs all necessary data validation
          $form_field = "safe_post_{$attr}";
          if (isset($rc->$form_field)) {
            $new_value = $rc->$form_field;
            // update value
            if (!isset($this->$attr) || $new_value != $this->$attr) {
              $this->$attr = $new_value;
            }
          }
          break;
      }
    }

    // step 2 - process file attributes, if needed
    $reset_interpolation_flag = TRUE;
    foreach ($file_attributes as $attr) {
      // NOTE: we IGNORE the immutable flag for 'file' data types
      $abs_path = $this->file_abs_path($attr, $reset_interpolation_flag);
      $reset_interpolation_flag = FALSE;

      // check for file upload
      $form_field = "safe_files_{$attr}";
      if (isset($rc->$form_field)) {
        $file_ar = $rc->$form_field;
        // echo "form_field: $form_field\n";
        // var_dump($file_ar);
        if (!$file_ar || !isset($file_ar['name']) || intval($file_ar['error']) != 0) {
          // echo "Skipping $attr\n";
          continue;
        }

        // get file names -local name of uploaded file and client file name - not that we use it
        $upload_name = $file_ar['name'];
        $uploaded_file_path = $file_ar['tmp_name'];
      }
      
      // set the attribute to show if the file exists or not
      $this->$attr = file_exists($abs_path) ? 'defined' : 'empty';
    }

    $save_result = $this->save();
    if (!$save_result) IncludeUtilities::report_bad_thing("Save Failed in process_form(): " . basename(__FILE__) . ':' . __LINE__,
      $this->dump("$this->cls_name::process_form(): $this not saved"));

    return $save_result;
  } // end of process_form()
  
  public function dump($msg = '')
  {
    $str = "<div class=\"dump-output\"> <!-- dump of $this -->\n$msg\nAnInstance: $this\n";
    $str .= "Class: {$this->cls->cls_name}, DB: {$this->dbaccess} " . ($this->needs_save ? ' Needs Save' : ' Doesn\'t need Saving') . "\n";
    $str .= " Record " . ($this->record_in_db ? "in Database" : "Not in Database") . "\n";
    $tmp = $this->compute_key_values();
    $str .= " Keys and Values:\n";
    foreach ($this->cls->keys_list as $key) {
      // do not include private attributes in dump
      if ($this->has_prop($key, 'private')) {
        continue;
      }
      if ($tmp[$key] instanceof DateTime) {
        $str .= "  Key: $key => " . $tmp[$key]->format('c') . "\n";
      } else {
        $str .= "  Key: $key => {$tmp[$key]}\n";
      }
    }
    $str .= " Attributes:\n";
    foreach ($this->cls->attribute_names as $attr) {
      $str .= "  Attr: {$this->cls->get_prop($attr, 'title')} [$attr] ({$this->cls->get_prop($attr, 'type')}): {$this->asString($attr)}\n";
    }
    // This was necessary for debugging a save/restore bug in encrytpors - but shouldn't be necessary now
    // $str .= $this->encryptor->dump();
    return $str . "</div> <!-- end dump of $this -->\n";
  } // end of dump()
} // end of AnInstance

// joins

class AJoinException extends Exception {}

class AJoin {
  // Data Required to reconstruct an AJoin object:
  //  left class name
  //  right class name
  //  join table name
  //  join table index
  //  field_map
  const JOIN_MAP_TABLENAME = '_join_map';
  static private $join_map_field_definitions = array(
    array('left_class_name', 'varchar(255)', TRUE),
    array('right_class_name', 'varchar(255)', TRUE),
    array('tablename', 'varchar(255)'),
    array('tableindex', 'int'),
    array('field_map', 'text'),
    array('field_definitions', 'text'),
    );
  static private $ajoin_max_tabnum = 0;
  static private $join_map = array();
  private $dbaccess;
  private $left_class;
  private $left_class_name;
  private $right_class;
  private $right_class_name;
  private $field_map;
  private $field_definitions;
  private $tablename;
  
  private function __construct($dbaccess, $left_class_name, $right_class_name)
  {
    $this->dbaccess = $dbaccess;
    $tmp = $dbaccess->select_from_table('_join_map', NULL, array('left_class_name' => $left_class_name, 'right_class_name' => $right_class_name));
    $this->left_class_name = $left_class_name;
    $this->left_class = NULL;
    $this->right_class_name = $right_class_name;
    $this->right_class = NULL;
    if ($tmp && count($tmp) > 0) {
      $tmp = $tmp[0];
      $this->tablename = $tmp['tablename'];
      $this->field_map = unserialize($tmp['field_map']);
      if ($tmp['field_definitions']) {
        $this->field_definitions = unserialize($tmp['field_definitions']);
      } else {
        $this->construct_field_map_and_definitions();
        $this->dbaccess->update_table(AJoin::JOIN_MAP_TABLENAME, array('field_definitions' => serialize($this->field_definitions)),
          array('left_class_name' => $left_class_name, 'right_class_name' => $right_class_name));
      }
    } else {
      $this->construct_and_save_field_map_and_definitions();
    }
    
    AJoin::add_to_map($this);
  } // end of __construct()
  
  private function construct_field_map_and_definitions()
  {
// echo "construct_field_map_and_definitions()" . __LINE__ . "\n";
    if ($this->field_map && count($this->field_map) == count($this->field_definitions))
      return;
// echo "construct_field_map_and_definitions()" . __LINE__ . "\n";

    if (!$this->left_class) {
      if (!class_exists($this->left_class_name)) {
        ObjectInfo::do_require_once($this->left_class_name . ".php");
      }
      $this->left_class = AClass::get_class_instance($this->left_class_name);
    }
    if (!$this->right_class) {
      if (!class_exists($this->right_class_name)) {
        ObjectInfo::do_require_once($this->right_class_name . ".php");
      }
      $this->right_class = AClass::get_class_instance($this->right_class_name);
    }

    // construct field_map
    $this->field_map = array();
    $this->field_definitions = array();
    $idx = 0;
    foreach ($this->left_class->keys_list as $key) {
      $this->field_map[$this->left_class->get_prop($key, 'sql_name')] = "_f{$idx}";
      $this->field_definitions[] = array("_f{$idx}", "{$this->left_class->get_prop($key, 'sql_type')}", TRUE);
      $idx += 1;
    }
    foreach ($this->right_class->keys_list as $key) {
      $this->field_map[$this->right_class->get_prop($key, 'sql_name')] = "_f{$idx}";
      $this->field_definitions[] = array("_f{$idx}", "{$this->right_class->get_prop($key, 'sql_type')}", TRUE);
      $idx += 1;
    }
//var_dump($this);
  } // end of construct_field_map()    

  private function construct_and_save_field_map_and_definitions()
  {
    // rebuild field_map and field_defintions
    $this->construct_field_map_and_definitions();
    
    // create table name
    $tmp = $this->dbaccess->select_from_table('_join_map', 'max(tableindex) as maxtableindex');
    $tableindex = intval($tmp[0]['maxtableindex']) + 1;
    $this->tablename = "_j{$tableindex}";

    // create table
    if ($this->dbaccess->create_table($this->tablename, $this->field_definitions) === FALSE) {
      throw new AJoinException("AJoin::__construct(): unable to create join table {$this->tablename} for classes $this->left_class_name and $this->right_class_name:\n {$this->dbaccess->error()}");
    }

    // insert table information into _join_map
    $tmp = $this->dbaccess->insert_into_table(AJoin::JOIN_MAP_TABLENAME,
        array('left_class_name' => $this->left_class_name,
          'right_class_name' => $this->right_class_name,
          'tablename' => $this->tablename,
          'tableindex' => (string)$tableindex,
          'field_map' => serialize($this->field_map),
          'field_definitions' => serialize($this->field_definitions)));
    if (!$tmp) {
      throw new AJoinException("AJoin::construct_field_map(): failed to insert definition of join table $this->tablename into "
        . AJoin::JOIN_MAP_TABLENAME . "\nError: '{$this->dbaccess->error()}'");
    }
    return TRUE;
  } // end of construct_and_save_field_map_and_definitions()

  
  private static function create_join_map_table($dbaccess)
  {
    if (!$dbaccess->table_exists(AJoin::JOIN_MAP_TABLENAME)
        && !$dbaccess->create_table(AJoin::JOIN_MAP_TABLENAME, AJoin::$join_map_field_definitions)) {
      throw new AJoinException("AJoin::create_join_map_table($dbaccess): failed: {$dbaccess->error()}");
    }
    return TRUE;
  } // end of create_join_map_table()

  public static function destroy_all_joins($dbaccess)
  {
    if (!AJoin::$join_map) AJoin::load_or_create_ajoin_map($dbaccess);
    foreach (AJoin::$join_map[(string)$dbaccess] as $left => $left_map) {
      foreach ($left_map as $right => $ajoin_obj) {
        $dbaccess->drop_table($ajoin_obj->tablename);
      }
    }
    $dbaccess->drop_table(AJoin::JOIN_MAP_TABLENAME);
    AJoin::$join_map = NULL;
  }

  public function __toString()
  {
    return "$this->tablename($this->left_class_name, $this->right_class_name)";
  } // end of __toString()

  public function __get($name)
  {
    switch ($name) {
      case 'left_class_name':
      case 'right_class_name';
      case 'tablename':
        return $this->$name;
      default:
        return NULL;
    }
  } // end of __get()
  
  // AJoin::join_map handling
  private static function load_or_create_ajoin_map($dbaccess)
  {
    // auto-load and auto initialize join support
    if (!AJoin::$join_map) {
      if (!AJoin::create_join_map_table($dbaccess)) {
        throw new AJoinException("AJoin::__construct(): Unable to create AJoins control table '" . AJoin::JOIN_MAP_TABLENAME . "': {$dbaccess->error()}");
      }
      AJoin::$join_map = array((string)$dbaccess => array());
      $tmp = $dbaccess->select_from_table(AJoin::JOIN_MAP_TABLENAME, 'left_class_name,right_class_name');
      foreach ($tmp as $row) {
        AJoin::add_to_map(new AJoin($dbaccess, $row['left_class_name'], $row['right_class_name']));
      }
      if (FALSE) {
        foreach (AJoin::$join_map as $dba => $dba_map) {
          foreach ($dba_map as $left => $left_map) {
            foreach ($left_map as $right => $ajoin_obj) {
              $ajoin_obj->dump("$dba: $left, $right");
            }
          }
        }
      }
    }
  } // end of load_or_create_ajoin_map()

  private static function add_to_map($ajoin_obj)
  {
    $left_class_name = $ajoin_obj->left_class_name;
    $right_class_name = $ajoin_obj->right_class_name;
    if (!AJoin::$join_map) AJoin::load_or_create_ajoin_map($ajoin_obj->dbaccess);
    
    // add this instance to cache
    $dba_key = (string)$ajoin_obj->dbaccess;
    if (!isset(AJoin::$join_map[$dba_key])) AJoin::$join_map[$dba_key] = array();
    if (!isset(AJoin::$join_map[$dba_key][$left_class_name])) AJoin::$join_map[$dba_key][$left_class_name] = array();
    AJoin::$join_map[$dba_key][$left_class_name][$right_class_name] = $ajoin_obj;
    if (!isset(AJoin::$join_map[$dba_key][$right_class_name])) AJoin::$join_map[$dba_key][$right_class_name] = array();
    AJoin::$join_map[$dba_key][$right_class_name][$left_class_name] = $ajoin_obj;
  } // end of add_to_map()
  
  public static function php_create_string($dbaccess, $dump_dir)
  {
    if (!is_dir($dump_dir)) {
      if (!mkdir($dump_dir, 0755)) {
        echo "Skipping AJoin Data - directory $dump_dir not accessible\n";
        return FALSE;
      }
    }
    if (!is_writable($dump_dir)) {
      echo "Skipping AJoin Data - directory $dump_dir not accessible\n";
      return FALSE;
    }

    echo "Dumping AJoin Data\n";
    $join_map_ar = $dbaccess->select_from_table(AJoin::JOIN_MAP_TABLENAME);
    
    // string to create join map table
    $str = "<?php\n\$dbaccess->create_table('" . AJoin::JOIN_MAP_TABLENAME
        . "', unserialize('" . serialize(AJoin::$join_map_field_definitions) . "'), \$drop_first)"
        . " or die(\"Unable to create Join Map Table\\n{\$dbaccess->error()}\\n\");\n";

    // save _join_map values
    foreach ($join_map_ar as $row) {
      $str .= "\$dbaccess->insert_into_table('" . AJoin::JOIN_MAP_TABLENAME . "', array(";
      $comma = '';
      foreach ($row as $field_name => $value) {
        $str .= "{$comma}'{$field_name}' => '{$value}'";
        $comma = ', ';
      }
      $str .=  "));\n";
      
      // create the join table
      $str .= "\$dbaccess->create_table('{$row['tablename']}', unserialize('{$row['field_definitions']}'), \$drop_first)"
        . " or die(\"Unaable to create join table {$row['tablename']}\\n{\$dbaccess->error}\\n\");\n";

      // create the insert table stuff
      $join_table_ar = $dbaccess->select_from_table($row['tablename']);
      foreach ($join_table_ar as $join_row) {
        $str .= "\$dbaccess->insert_into_table('{$row['tablename']}', unserialize(base64_decode('"
          . base64_encode(serialize($row)) . "')));\n";
      }
    }
    
    return file_put_contents($dump_dir . DIRECTORY_SEPARATOR . '_join_tables.php', $str);
  } // end of php_create_string()

  public static function get_ajoins_for($aninstance)
  {
    if (!($aninstance instanceof AnInstance)) {
      throw new AJoinException("AJoin::get_ajoin(arg): arg is not an AnInstance object");
    }
    if (!($aninstance->dbaccess instanceof DBAccess)) {
      throw new AJoinException("AJoin::get_ajoin(): aninstance->dbaccess is not a DBAccess object");
    }

    if (!AJoin::$join_map) AJoin::load_or_create_ajoin_map($aninstance->dbaccess);
    $dba_key = (string)($aninstance->dbaccess);
    return isset(AJoin::$join_map[$dba_key][$aninstance->cls_name])
      ? array_values(AJoin::$join_map[$dba_key][$aninstance->cls_name])
      : FALSE;
  } // end of get_ajoins_for($aninstance)

  public static function get_ajoin($dbaccess, $left_class, $right_class)
  {
    if (!($dbaccess instanceof DBAccess)) {
      throw new AJoinException("AJoin::get_ajoin(): dbaccess is not a DBAccess object");
    }

    if (!$left_class || !$right_class) {
      ob_start(); debug_print_backtrace();
      throw new AJoinException("AJoin::get_ajoin($dbaccess, $left_class, $right_class): left or right class not defined: " . ob_get_clean());
    }
    if (!AJoin::$join_map) AJoin::load_or_create_ajoin_map($dbaccess);
    
    // if ajoin object exists, then return it. Otherwise, create new one
    $dba_key = (string)$dbaccess;
    if (isset(AJoin::$join_map[$dba_key])
        && isset(AJoin::$join_map[$dba_key][$left_class])
        && isset(AJoin::$join_map[$dba_key][$left_class][$right_class])) {
      return AJoin::$join_map[$dba_key][$left_class][$right_class];
    } else {
      return new AJoin($dbaccess, $left_class, $right_class);
    }
  } // end of get_ajoin()
  
  public static function ajc_select_objects($controlling_instance, $joined_class) {
    $ajoin = AJoin::get_ajoin($controlling_instance->dbaccess, $controlling_instance->cls_name,
        $joined_class instanceof AnInstance ? $joined_class->cls_name : $joined_class);
    return $ajoin->select_joined_objects($controlling_instance);
  }

  public static function ajc_add_to_join($controlling_instance, $joining_instance) {
    $ajoin = AJoin::get_ajoin($controlling_instance->dbaccess, $controlling_instance->cls_name,
        $joining_instance->cls_name);
    return $ajoin->add_to_join($controlling_instance, $joining_instance);
  }
 
  public static function ajc_delete_from_join($controlling_instance, $joined_instance) {
    $ajoin = AJoin::get_ajoin($controlling_instance->dbaccess, $controlling_instance->cls_name,
        $joined_instance->cls_name);
    return $ajoin->delete_from_join($controlling_instance, $joined_instance);
  }

  public static function ajc_update_join($controlling_instance, $new_join_list) {
    $ar = array();
    foreach ($new_join_list as $joining_instance) {
      if (!isset($ar[$joining_instance->cls_name])) {
        $ar[$joining_instance->cls_name] = array();
      }
      $ar[$joining_instance->cls_name][] = $joining_instance;
    }
    foreach (array_keys($ar) as $joining_class) {
      $ajoin = AJoin::get_ajoin($controlling_instance->dbaccess, $controlling_instance->cls_name,
          $joining_class);
      $ajoin->update_join($controlling_instance, $ar[$joining_class]);
    }
  }
  
  // start instance methods
  
  private function join_sql($controlling_instance, $joining_obj, $additional_attribute_list = NULL)
  {
    $ar = array();
    foreach ($controlling_instance->keys_list as $key) {
      $join_field_name = $this->field_map[$controlling_instance->get_prop($key, 'sql_name')];
      $ar[$join_field_name] = $controlling_instance->$key;
    }
    $where_clause = $this->dbaccess->escape_where($ar);
    if ($additional_attribute_list) {
      $select_field_list = is_array($additional_attribute_list) ? $additional_attribute_list
        : array(preg_split('/\s*,\s*/', $additional_attribute_list));
      $select_field_list = array_diff($select_field_list, $joining_obj->keys_list);
      foreach ($select_field_list as $attr) {
        if (!in_array($attr, $joining_obj->attribute_names)) {
          throw new AJoinException("AJoin::join_sql($controlling_instance->cls_name, $joining_obj->cls_name, "
          . implode(',', $select_field_list) . "): '$attr' not in $joining_obj->cls_name");
        }
      }
    } else {
      $select_field_list = array();
    }
    foreach ($joining_obj->keys_list as $key) {
      $joining_obj_field_name = $joining_obj->get_prop($key, 'sql_name');
      $join_field_name = $this->field_map[$joining_obj_field_name];
      $where_clause .= " and $join_field_name = $joining_obj_field_name";
      $select_field_list[] = $key;
    }

    $sql = "select " . implode(',', $select_field_list) . " from {$joining_obj->tablename}, {$this->tablename} "
      . $where_clause ;
    return $sql;
  } // end of join_where_sql()
  
  private function joining_class_name($controlling_instance)
  {
    if ($controlling_instance->cls_name == $this->left_class_name) {
      return $this->right_class_name;
    } elseif ($controlling_instance->cls_name == $this->right_class_name) {
      return $this->left_class_name;
    } else {
      throw new AJoinException("AJoin::joining_class_name($controlling_instance->cls_name): This join not defined on supplied controlling class");
    }
  } // end of joining_class_name()

  public function select_joined_objects($controlling_instance)
  {
    $joining_class_name = $this->joining_class_name($controlling_instance);
    if (!class_exists($joining_class_name)) {
      ObjectInfo::do_require_once("{$joining_class_name}.php");
      // require_once("{$joining_class_name}.php");
    }
    $tmp = $this->dbaccess->query($this->join_sql($controlling_instance,
      new $joining_class_name($this->dbaccess)));

    $ar = array();
    if (is_array($tmp)) {
      foreach ($tmp as $row) {
        $ar[] = new $joining_class_name($this->dbaccess, $row);
      }
    }
    return $ar;
  } // end of select_joined_objects()
  
  public function select_joined_objects_sorted($controlling_instance, $cmp = NULL)
  {
    if (!is_callable($cmp)) {
      $cmp = new AClassCmp($cmp);
    }
    $ar = $this->select_joined_objects($controlling_instance);
    usort($ar, $cmp);
    return $ar;
  } // end of select_joined_objects_ordered()

  // NOTE: right is optional to allow deleting all join entries for a single
  private function join_entry_values($left, $right = NULL)
  {
    $ar = array();
    $left->save();
    foreach ($left->keys_list as $key) {
      $join_field_name = $this->field_map[$left->get_prop($key, 'sql_name')];
      $ar[$join_field_name] = $left->$key;
    }
    if ($right) {
      $right->save();
      foreach ($right->keys_list as $key) {
        $join_field_name = $this->field_map[$right->get_prop($key, 'sql_name')];
        $ar[$join_field_name] = $right->$key;
      }
    }
    return $ar;
  } // end of join_entry_values()

  public function add_to_join($left, $right)
  {
    if ($left->dbaccess != $this->dbaccess || ($right && $right->dbaccess != $this->dbaccess)) {
      throw new AJoinException("AJoin::add_to_join($left, $right): attempt to join instances across databases");
    }
    try {
      $this->dbaccess->insert_into_table($this->tablename, $this->join_entry_values($left, $right));
      return TRUE;
    } catch (AnInstanceException $e) {
      return FALSE;
    }
    
  } // end of add_join()
  
  public function delete_from_join($left, $right)
  {
    if ($left->dbaccess != $this->dbaccess || $right->dbaccess != $this->dbaccess) {
      throw new AJoinException("AJoin::add_to_join($left, $right): attempt to delete join instance across databases");
    }
    try {
      $this->dbaccess->delete_from_table($this->tablename, $this->join_entry_values($left, $right));
      return TRUE;
    } catch (AnInstanceException $e) {
      return FALSE;
    }
  } // end of delete_from_join()
  
  public function delete_joins_for($aninstance)
  {
    try {
      $aninstance->dbaccess->delete_from_table($this->tablename, $this->join_entry_values($aninstance));
      return TRUE;
    } catch (AnInstanceException $e) {
      echo $e . "\n";
      return FALSE;
    }
  } // end of delete_joins_for()
  
  public function update_join($controling_instance, $joined_list)
  {
    $ar = array();
    $joining_class = $this->joining_class_name($controling_instance);
    if (!class_exists($joining_class)) {
      ObjectInfo::do_require_once($joining_class . '.php');
    }
    foreach ($joined_list as $tmp) {
      if ($tmp instanceof $joining_class) {
        $ar[] = $tmp;
      } elseif (is_array($tmp)) {
        $ar[] = new $joining_class($this->dbaccess, $tmp);
      } elseif (is_string($tmp) && substr($tmp, 0, 4) == 'a%3A') {
        $ar[] = new $joining_class($this->dbaccess, unserialize(urldecode($tmp)));
      } else {
        throw new AJoinException("AJoin::update_join($controling_instance, ...): bad element in list");
      }
    }
    
    $current_join_list = $this->select_joined_objects($controling_instance);
    if ($current_join_list) {
      if ($ar) {
        foreach (array_diff($ar, $current_join_list) as $obj) {
          $this->add_to_join($controling_instance, $obj);
        }
        foreach (array_diff($current_join_list, $ar) as $obj) {
          $this->delete_from_join($controling_instance, $obj);
        }
      } else {
        $this->delete_joins_for($controling_instance);
      }
    } else {
      foreach ($ar as $tmp) {
        $this->add_to_join($controling_instance, $tmp);
      }
    }
  } // end of update_join()
  
  public function in_joinP($left, $right)
  {
    try { $where_ar = $this->join_entry_values($left, $right); }
    catch (AnInstanceException $e) {
      return FALSE;
    }
    $where_clause = $this->dbaccess->escape_where($where_ar);
    $tmp = $this->dbaccess->select_from_table($this->tablename, NULL, $where_clause);
    return is_array($tmp) && count($tmp) == 1;
  } // end of in_joinP()
  
  public function dump($msg = '')
  {
    $ar = array("<div class=\"dump-output\"> <!-- dump of $this -->\n$msg\nAJoin: {$this->tablename}\n");
    $ar[] = "$this->tablename: $this->left_class_name x $this->right_class_name";
    if (!($tmp = $this->dbaccess->select_from_table($this->tablename))) {
      $ar[] = $this->dbaccess->error();
    } else {
      $reverse_map = array_flip($this->field_map);
      $keys_in_order = array_values($this->field_map);
      sort($keys_in_order);
      foreach ($tmp as $row) {
        $line_ar = array();
        foreach ($keys_in_order as $key) {
          $line_ar[] = "$key ({$reverse_map[$key]}): {$row[$key]}";
        }
        $ar[] = implode(', ', $line_ar);
      }
    }
    $ar[] = "</div> <!-- dump of AJoin $this -->\n";
    
    return implode("\n", $ar);
  } // end of dump()
} // end of class AJoin

class AManagerException extends Exception {}

class AManager {
  private $options = array(
      'form_action' => NULL,
      'orderby' => NULL,
      'expose_select' => TRUE,
    );
    
  public function __construct($dbaccess, $cls_name, $display_field_names, $options = array())
  {
    $this->cls_name = $cls_name;
    $this->dbaccess = $dbaccess;
    $this->display_field_names = trim($display_field_names);
    foreach ($options as $option => $value) {
      if (array_key_exists($option, $this->options)) {
        $this->options[$option] = $value;
      } else {
        throw new AManagerException("AManager::__construct(dbaccess, $cls_name, $display_field_names, options): Illegal option '$option'");
      }
    }
    if (!$this->options['form_action']) {
      $this->options['form_action'] = $_SERVER['REQUEST_URI'];
    }
    $this->instance = new $cls_name($dbaccess);
    $this->cls = AClass::get_class_instance($cls_name);
    if (!$dbaccess->table_exists($this->cls->tablename)) {
      $this->cls->create_table($dbaccess);
    }
  } // end of __construct()
  
  public function set_option($name, $value) {
    switch ($name) {
      case 'form_action':
      case 'orderby':
      case 'expose_select':
        $this->options[$name] = $value;
        break;
      default:
        throw new AManagerException("AManager::set_option(): Illegal option name: $name");
    }
  } // end of set_option()

  public function select_element($selected = NULL)
  {
    if (!$this->options['expose_select']) {
      return;
    }
    $orderby = $this->options['orderby'] ? "order by " . $this->options['orderby'] : "order by $this->display_field_names";
    $lst = $this->instance->get_objects_where(NULL, $orderby);
    if (count($lst) == 0) {
      return '';
    }
    $display_field_name_ar = preg_split('/\s*,\s*/', $this->display_field_names);
    $selected_key_values = $selected ? $selected->compute_key_values() : NULL;
    // $ar = array("<select name=\"key_array\">", " <option value=\"-new-\">New {$this->cls_name}</option>");
    $ar = array("<select name=\"key_array\">");
    foreach ($lst as $obj) {
      $option_selected = $selected_key_values && $obj->compute_key_values() == $selected_key_values
        ? 'selected' : '';
      $value = $obj->encode_key_values();
      $tmp_ar = array();
      foreach ($display_field_name_ar as $field) {
        switch ($obj->get_prop($field, 'type')) {
          case 'join':
            $tmp_ar[] = $obj->join_value_of($field);
            break;
          case 'link':
            if ($linked_obj = $obj->link_value_of($field)) {
              $display_field_name = $obj->get_prop($field, 'join_display_field');
              $tmp_ar[] = $linked_obj->$display_field_name;
            } else {
              $tmp_ar[] = 'Unknown';
            }
            break;
          case 'category':
            // FIXME!!!!!
            break;
          default:
            $tmp_ar[] = $obj->$field;
            break;
        }
      }
      $display_name = implode(' / ', $tmp_ar);
      $ar[] = "  <option value=\"{$value}\" {$option_selected}>{$display_name}</option>";
    }
    $ar[] = "</select>";
    return "  " . implode("\n  ", $ar) . "\n";
  } // end of select_article()

  public function render_form($rc, $form_top = NULL, $form_bottom = NULL, $actions = NULL)
  {
    $cls_name = $this->cls_name;

    // get object instance
    if ($rc->safe_post_submit == 'New') {
      $obj = new $cls_name($this->dbaccess);
    } elseif (isset($rc->safe_post_key_array) && $rc->safe_post_key_array) {
      if ($rc->safe_post_key_array == '-new-') {
        $obj = new $cls_name($this->dbaccess);
      } else {
        $keys_array = AnInstance::static_decode_key_values($rc->safe_post_key_array);
        $obj = new $cls_name($this->dbaccess, $keys_array);
      }
    } else {
      $cls_obj = AClass::get_class_instance($cls_name);
      $ar = array();
      foreach ($cls_obj->keys_list as $key) {
        $field_name = "safe_post_{$key}";
        if (isset($rc->$field_name)) {
          $ar[$key] = $rc->$field_name;
        }
      }
      $obj = $ar ? new $cls_name($this->dbaccess, $ar) : NULL;
    }

    // handle delete requests
    if ($rc->safe_post_submit == 'Delete') {
      echo $obj->render();
?>
    <form action="<?php echo $this->options['form_action']; ?>" method="post" accept-charset="utf-8">
      <input type="hidden" name="key_array" value="<?php echo $rc->safe_post_key_array; ?>">
      <p>
        <input type="submit" name="submit" value="Confirm Delete">
        <input type="submit" name="submit" value="Retain">
      </p>
    </form>
<?php
      return;
    } elseif ($rc->safe_post_submit == 'Confirm Delete') {
      $obj->delete();
      $obj = NULL;
      // Fall through to choice box
    }
    
    // if this is a save, then process form data [implicitly saves]
    if ($rc->safe_post_submit == 'Save') {
      if (!$obj) echo $rc->dump('no key array');
      $obj->process_form($rc);
      $error_messages = '';
      foreach ($obj->attribute_names as $attr) {
        if ($obj->has_prop($attr, 'required')) {
          if (!isset($obj->$attr)
              || (($tmp = $obj->has_prop($attr, 'filter'))
                  && !preg_match($obj->get_prop($attr, 'filter'), $obj->$attr))
              || (!$tmp && !$obj->$attr)) {
            $error_messages .= "<li>{$obj->get_prop($attr, 'title')}: Missing Value"
              . ($tmp ? ' tmp is TRUE' : ' tmp is FALSE')
              . " $attr is '{$obj->$attr}'"
              . " $attr is " . (isset($obj->$attr) ? 'Set':'Not Set')
              . " and preg_match is " . (preg_match($obj->get_prop($attr, 'filter'), $obj->$attr)?'TRUE':'FALSE')
              . " and filter is '" . $obj->get_prop($attr, 'filter') . "'"
              . "</li>\n";
          }
        }
      }
    } 

    // render error messages
    if (isset($error_messages) && $error_messages) echo "<div class=\"error-fixed-format\">
    <p>Please Correct the Following Errors:</p>
    <ol>
    {$error_messages}
    </ol>
    </div>";

    // render select form if required
    if ($this->options['expose_select']) {
      echo "    <form  action=\"{$this->options['form_action']}\" method=\"post\" accept-charset=\"utf-8\">
        <p>
          <span class=\"box\">";
      if (($select_elt = $this->select_element($obj))) {
        echo "{$this->select_element($obj)}
            <input type=\"submit\" value=\"Select\">\n";
      } else {
        echo "No Entries - click to create ";
      }
      echo "   <input type=\"submit\" name=\"submit\" value=\"New\">
          </span>
        </p>
      </form>";
    }
    
    // display form if object specified
    if ($obj) {
      echo $obj->form($this->options['form_action'], $form_top, $form_bottom, $actions);
    }    
  } // end of render_form()
} // end of class AManager
// end class definitions

?>
