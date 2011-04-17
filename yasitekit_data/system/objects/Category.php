<?php
/*
#doc-start
h1. Category.php - Category objects for tags, classifications, etc

Created by  on 2010-02-16.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

*Category* objects are used to create a hierarchic structure of tags or classifications.
This module also provides an interface to the Session object ("see":/doc.d/system-includes/session.html)

Most category operations do not directly use the Category classes but rather use
_category_ data types which are defined in "aclass.php":/doc.d/system-includes/aclass.html#category

Read the next section in order to find out how to use them. Read the following for a detailed
discussion of Category objects.

h2. How to Use Categories

h3. Classifying with Sub-Categories

First of all, you use can Categories to put AnInstance subclasses into one or more
classifications.

Let's say we have a newsletter, so we set up a 'top level' category called '_newsletter'.
We can add a bunch of subcategories, such as '_newsletter_latest', '_newsletter_jan2010',
and things like that.

To do this, we'll create a Newsletter object by extending
"AnInstance":/doc.d/system-includes/aclass.html#aninstance. One of the attributes will
have the name _category_ and will have the type 'category(_newsletter_)'. This
will automatically classify every Newsletter object as a member of the _newsletter_
category.

Every AnInstance extension - Newsletter in our case - has several 'support' methods
which interface with Category objects. We'll look at them first before we start
looking at Category methods.

First of all, when we create a new Newsletter instance, we probably want to assign
it to a few categories. For example we might want one for the month and year and and
another for the year alone and maybe a few for special topics. The easiest way to
do this is to assign a bunch of categories to the _category_ attribute. So we
might write something which does:

pre. $this->category = '_newsletter_y2010,_newsletter_jun2010,_newsletter_special'

This will replace the current set of categories by the comma separated list show -
plus the root category of '_newsletter'.

If you want to add or delete categories individually, you will use the _add_category()_
and _delete_category()_ methods. For example to make this Newsletter the latest,
you could write:

pre. $this->add_category('category', '_newsletter_latest');

But this would result in two _latest_ newsletters unless you find the current
newsletter and write:

pre. $latest_newsletter->delete_category('category', '_newsletter_latest');

The easiest way to get the latest newsletter object is to use a Category class method:

pre. $latest_newsletter = Category::get_instances_for_category('_newsletter_latest', $this->dbaccess, 'Newsletter');

Then - of course - you can add categories at will.

h3. Using Categories to Relate Objects

An additional feature is that if objects of different types - say Products and Deliverables -
share the same Categories, then it's easy to manage them.

The YASiteKit system has both a Product and a Deliverable class. The Product class
defines the content of a product - say an image or book content. The Deliverable
class defines the format of the deliverable - say a print, electronic pdf, engraving,
etc.

Both classes have a _deliverable_category_ attribute. As an example, let's assume
we have set up the following deliverable categories:

* _deliverable - the root of all deliverables

For deliverable formats which are free

* _deliverable_freedownload - the root of all free products and product samples
* _deliverable_freedownload_image - free downloadable images
* _deliverable_freedownload_sw - free downloadable software
* _deliverable_freedownload_report - free downloadable reports

And for deliverables which are for sale:

* _deliverable_image - images
* _deliverable_image_download - images download
* _deliverable_image_print - physical print of an image
* _deliverable_sw - software
* _deliverable_sw_download - downloadable software delivery
* _deliverable_sw_physical - packaged software

Suppose we have - as is often the case - software for sale which operates in both
a licensed and trial mode. Then we for the product, we will probably have a line
of code which looks like:

pre. $this->deliverable_category = '_deliverable_freedownload_sw,_deliverable_sw_download,_deliverable_sw_physical';

When we want to retrieve a list of all the Deliverable objects which relate to this product,
we will use the _select_objects_in_category()_ method of the Product class:

pre. $deliverables = $this->select_objects_in_category('deliverable_category', 'Deliverable');

We can then create a shopping cart style menu which displays the product information and
all deliverables which are appropriate.

Notice that we can change the deliverable mix without modifying the program by using
Deliverable and Product management.

If we want to dress up our item menu a little, we can use the _category_paths_of()_
and _category_objects_of()_ to retrieve all the Categories [either path name only or
entire objects] for any _category_ attribute. This makes it easy to write code
which describes the general types of deliverables which are available for any given
product.

h3. Category Class Methods

The Category class methods are described in boring-ease below. Here we concentrate
on when you might want to use them.

* get_instances_for_category(category, dbaccess, class-name) - this is useful if you
need to find instancs of _class-name_ objects in a category specific category or
in a different database or something like that. Mostly you _won't_ use this because
the AnInstance _select_objects_in_category()_ is what you want.

* add_to_category() and delete_from_category() - shouldn't be used directly. Use the
AnInstance support methods instead because they manage the instance bookeeping for
your AnInstance derived object. The Category add and delete methods only manage
the category objects and join tables.

* categories_for_instance() - gets all the categories a specific instance is in.
It does not restrict the list by attribute - not surprisingly. The return is an array
of category paths. It may be useful in some cases.

h2. Category Objects

Each Category object is represented by a sequence of tokens which are connected
by the underscore (==_==) character. This string is called the category _path_.
This is similar to UNIX path names. [The
underscore was chosen so that the category name satisfies the PHP identifier
construction rules - which makes many things much easier.]

h3. Path Name Syntax

Each token must satisfy [a-z0-9]{1,15} - lower case letters and digits.
Thus, each token may be at most 15 characters long.

The rightmost segment of a _path_ is called the _name_.

If a _path_ contains an underscore, then the string to the
left of the rightmost underscore is a path and is called the
_parent_. If it does not contain an underscore, then the _path_
is a 'top level' path and it's parent is empty - or the 'root'
category - which is represented by the empty string ('').
The 'root' category can never be instantiated.

Some examples of legal paths:

<pre>
foo - the immediate child of the root category with name 'foo'
foo_bar - name _bar_, parent _foo_
foo_bar_baz - name _baz_, parent _foo_bar_
</pre>

h4. Children and Subpaths

Each Category may have zero or more sub-categories represented by _subpath_
strings. A _subpath_ of a _path_ is a legal path string which consists
of _path_ followed by a connecting underscore (_) and another legal path string.

Thus, if _foo_ is a path, then _foo_bar_ and _foo_baz_beez_ are both
_subpaths_ of _foo_ and _bar_foo_ is not.

If a _subpath_ of _path_ consists of _path_ conjoined to a single _token_,
then _subpath_ is a _child_ of _path_ (also called a _direct child_ or
_direct descendent_). Otherwise, it is a _descendent_.

In the above examples, the subpath _foo_bar_ of _foo_ is a _child_,
wherease _foo_baz_beez_ is a descendent, but not a child.

h4. Membership Implementation

Membership in a category is implemented using AJoin objects to join
Category instances to other instances of classes derived from AnInstance.

The Category class supports primatives to associate other AClass objects
with specific categories, disassiocate them and retrieve lists of objects
'in a category'.

The Category class also supports default categories.
This is
implemented by interfacing with the "Session":/doc.d/system-includes/session.html
object. See "below":#session_interface for details.

h3. Instantiation

pre. $cat = new Category(dbaccess, arg), where _arg_ can be:

* an array defining the keys _parent_ and _name_.
* a category name of the form 'foo_bar' - as described above.
* a string of the form _name_, which recreates a top level category which is
a child of the (nameless) root category.

h3. Instance Attributes

* path - string - the underscore separated names of all category tokens
from 'root' to the 
* parent - string (read only) - underscore (_) separated names of all ancestor categories.
* name - string (read only) - name for this category. Must be unique within the parent
* sibling_ordinal - int - order of sorting within parent. Must be monotonically increasing within
a parent
* title - string - visible title

h3. Class Methods

Theses Class methods support category mapulation for instances of other objects.
This is implemented by defining AJoin objects between Category and other
objects.

In these methods, _category_ must be an appropriate key which can
be used to instantiate a Category object (see above). Instance is the instance
of the class we want to join the category to.

* Category::add_to_category(category, instance) - adds instance and category to the
AJoin table for _category_. Recursively adds all antecedents of _category_.
* Category::delete_from_category(category, instance) - Deletes AJoin entries
for _instance_ and _category_ and all descendents of _category_ from AJoin
table.
* Category::get_instances_for_category(category, dbaccess, aclass_name) -
returns an array of all object instances joined to the specified _category_
which are _aclass_name_ objects in database _dbaccess_

* Category::categories_for_instance(instance) - returns an array of category
names for the specified instance. _instance_ can be an class deriveed from
AnInstance.

General Stuff:

* Category::subpath_of_pathP($subpath, $path) - returns TRUE if _$subpath_
is a subpath of _$path_, but not equal to _$path_. Otherwise, returns FALSE.
* Category::subpath_of_path_groupP($subpath, $path_group) - returns TRUE
if _$subpath_ is a subpath of any member of _$path_group_. _$path_group_ can
be empty, a string of zero or more comma separated Category paths, or an
array of Category paths.
* Category::options_elt_for_category(dbaccess, parent = '', select_list = NULL, deep = FALSE) -
returns an HTML options string for all children (or descendents if _deep_ is TRUE)
of _parent_. _parent_ defaults to the root category. _select_list_ may be empty,
a single category path string, or an array of paths.

Session Support:
Each Category with children may have a _default_ child stored in the session.
The session interface consists of two methods:

* Category::set_default_category(parent_path, sub_path) - sets _sub_path as the
default sub-path for _parent_path_ in the session store.
* Category::get_default_category(parent_path) - gets the default sub-category of _parent_path_ from the
session store. Returns a Category instance or FALSE if the default is not set.

<!--
* Category::select_elt_for_instance(instance, category, other_class, name_of_select, multi = FALSE) -
returns an HTML select element which can be used in a form. The _name_of_select_
is the name of the select element. Values for options are urlencoded key values.
The display text is default display field for the object.
-->


h3. Instance Methods

Category Support:

* children() - returns a list of all immediate children of _this_
* descendents(include_self = FALSE) - returns a list of all descendents of _this_.
If _include_self_ is TRUE, then the array includes _this_
* antecedients(include_self = FALSE) - like descendents(), but goes upward.

Form support:

* options_elt_for_category($dbaccess, $selected_list = NULL, $parent = '') -
returns options elements for all children of _$parent_
* table_of_categories($parent = NULL) - WARNING - only used by the Category management
form.
Returns an HTML table of all category data
for the supplied _parent_ or all Category instances. ManageCategory.php has some
javascript which supports easy manipulation of the sort order within parents
* process_form() - overrides the default AnInstance::process_form() method in order
to provide special functioning for Category objects. (See
"aclass.php":http://doc.d/system-includes/aclass.html for general details)

Misc

* delete() - overrides the default AnInstance _delete()_ method to handle some special
stuff for categories. Relies on parent::delete() to actually do the delete and on
AnInstance::delete_category_references(). This method screws up synchronization between
any effected AnInstance subclass instance which is in memory. You should not trust
them after running _delete()_, so you should either terminate processing the HTML
request OR regenerate any instances you need to mess with. Of course you only have
to do this with AnInstance subclasses which contain _category_ attributues.

#end-doc
*/

// global variables
require_once('aclass.php');
require_once('Parameters.php');

AClass::define_class('Category', 'path', 
  array( // field definitions
    array('path', 'varchar(255)', 'Path'),
    array('parent', 'varchar(255)', 'Parent'),
    array('sibling_ordinal', 'int', 'Sort Index'),
    array('title', 'varchar(255)', 'Title'),
  ),
  array(// attribute definitions
      'path' => array('public', 'filter' => '[a-z0-9]{1,15}(_[a-z0-9]{1,15})*'),
      'parent' => array('readonly'),
      'sibling_ordinal' => array('public', 'readonly'),
      'title' => 'public',
      ));
// end global variables

// class definitions
class CategoryException extends Exception {}

class Category extends AnInstance {
  public static $parameters = NULL;
  public function __construct($dbaccess, $attribute_values = array())
  {
    if (!Category::$parameters) {
      Category::$parameters = new Parameters($dbaccess, 'Category');
    }

    parent::__construct('Category', $dbaccess, $attribute_values);

    if ($this->key_values_complete()) {
      $this->parent = strpos($this->path, '_') !== FALSE ? preg_replace('/_[a-z0-9]*$/', '', $this->path)
            : '';
      if (!isset($this->title) || !$this->title) {
        $this->title = preg_replace('/ /', '->', ucwords(preg_replace('/_/', ' ', $this->path)));
      }
      // $this->save();
      
      if (!isset($this->sibling_ordinal)) {
        $this_parent = $this->parent ? $this->parent : '_root_';
        if (!isset(Category::$parameters->$this_parent)) {
          if ($this_parent != '_root_') {
            $parent_obj = new Category($this->dbaccess, $this_parent);
            $parent_obj->save();
          }
          Category::$parameters->$this_parent = 0;
        }
        Category::$parameters->$this_parent += 1;
        $this->sibling_ordinal = Category::$parameters->$this_parent;
      }
      $this->save();
    }
  } // end of __construct()

  // Magic methods
  
  public function __toString()
  {
    return "$this->path";
  } // end of __toString()
  
  public function __get($name)
  {
    switch ($name) {
      case 'name':
        return preg_replace('/^([a-z0-9]*_)*/', '', $this->path);
      case '':
        ob_start();
        debug_print_backtrace();
        error_log(ob_get_clean());
        throw new Exception("Error Processing Request", 1);
      default:
        return parent::__get($name);
    }
  } // end of __get()

  // Class Methods

  public static function subpath_of_pathP($subpath, $path)
  {
    return preg_match("/^{$path}_/", $subpath) ? TRUE : FALSE;
  } // end of subpath_of_pathP()
  
  public static function subpath_of_path_groupP($subpath, $path_group)
  {
    if (!$path_group) {
      return FALSE;
    } elseif (is_string($path_group)) {
      $path_group = preg_split('/\s*,\s*/', $path_group);
    } elseif (!is_array($path_group)) {
      throw new CategoryException("Category::subpath_of_path_groupP($subpath, path_group): illegal path_group");
    }
    switch (count($path_group)) {
      case 0: return FALSE;
      case 1: return Category::subpath_of_pathP($subpath, $path_group[0]);
      default:
        foreach ($path_group as $path) {
          // if (Category::subpath_of_pathP($subpath, $path)) {
          if (preg_match("/^{$path}_/", $subpath)) {
            return TRUE;
          }
        }
        return FALSE;
    }
  } // end of subpath_of_path_groupP()

  public static function add_to_category($category, $instance) {
    $category_obj = is_string($category) ?  new Category($instance->dbaccess, $category)
      : $category;
    $ajoin = AJoin::get_ajoin($instance->dbaccess, 'Category', $instance->cls_name);
    // include self in antecedents()
    foreach ($category_obj->antecedents(TRUE) as $path) {
      if (!$ajoin->in_joinP(($obj = new Category($instance->dbaccess, $path)), $instance)) {
        $ajoin->add_to_join($obj, $instance);
      }
    }
  }
  
  public static function delete_from_category($category, $instance) {
    $category_obj = is_string($category) ?  new Category($instance->dbaccess, $category)
      : $category;
// echo $category_obj->dump("delete_from_category($category_obj, $instance)");
    foreach ($category_obj->descendents() as $obj) {
      AJoin::ajc_delete_from_join($obj, $instance);
    }
    AJoin::ajc_delete_from_join($category_obj, $instance);
  }
  
  public static function get_instances_for_category($category, $dbaccess, $aclass_name, $deep = FALSE) {
    if (!$aclass_name) {
      throw new CategoryException("Category::get_instances_for_category(): arg error: aclass_name cannot be NULL");
    }
    $category_obj = is_string($category) ?  new Category($dbaccess, $category) : $category;

    $ajoin = AJoin::get_ajoin($dbaccess, 'Category', $aclass_name);
    $instance_ar = $ajoin->select_joined_objects($category_obj);
    if ($deep) {
      foreach ($category_obj->children() as $obj) {
        $instance_ar = array_merge($instance_ar,
            $this->get_instances_for_category($obj, $dbaccess, $aclass_name, $deep));
      }
    }
    
    return $instance_ar;
  }
  
  // select element utilities
  public static function options_elt_for_category($dbaccess, $parent = '', $selected_list = NULL, $deep = FALSE)
  {
// echo "parent: $parent " . ($deep ? 'DEEP!!!!':'SHALLOWWWWWW') . "\n";
    if (!$selected_list)
      $selected_list = array();
    elseif (is_string($selected_list)) {
      $selected_list = array($selected_list);
    }
    if (!is_string($parent) || ($parent && !preg_match('/[a-z0-9]{1,15}/', $parent))) {
      ob_start(); var_dump($parent) ; $tmp = ob_get_clean();
      throw new CategoryException("Category::options_elt_for_category(dbaccess, $tmp,...): Illegal parent value");
    }
    $parent_obj = new Category($dbaccess, $parent);
    if ($parent) {
      $cat_list = $deep ? $parent_obj->descendents() : $parent_obj->children();
    } else {
      $cat_list = $parent_obj->get_objects_where(array('parent' => ''));
      if ($deep) {
        $ar = array();
        foreach ($cat_list as $cat) {
          $ar = array_merge($ar, $cat->descendents());
        }
        $cat_list = array_merge($cat_list, $ar);
      }
    }
    
    $str = "";
    foreach ($cat_list as $obj) {
      $selected = in_array($obj->path, $selected_list) ? 'selected' :'';
      $str .= "<option value=\"$obj->path\" $selected>{$obj->title}</option>\n";
    }
    return $str;
  } // end of options_elt_for_category()
  
  // select element utilities
  public static function select_elt_for_categories($dbaccess, $selected_list = NULL, $parent = '',
    $select_element_name = 'category', $display_field = 'title', $multi = FALSE)
  {
    if (!$selected_list)
      $selected_list = array();
    $parent_obj = new Category($dbaccess, $parent);
    
    $str = "<select name=\"$select_element_name\" " . ($multi?'multi':'') . ">\n";
    foreach ($parent_obj->children() as $cat) {
      $selected = in_array($cat->path, $selected_list) ? 'selected' :'';
      $str .= "<option value=\"$cat->path\" $selected>$cat->title</option>\n";
    }
    $str .= "</select>\n";
    return $str;
  } // end of select_elt_for_categories()
    
  // returns all categories for $instance
  public static function categories_for_instance($instance) {
    $category_ar = AJoin::get_ajoins_for($instance);
    return array_map(
      create_function('$a',
        "return $a->left_class_name == '{$instance->cls_name}' ? $a->right_class_name : $a->left_class_name"),
      $category_ar);
  }
  
  public static function set_default_category($parent_path, $new_default_subpath)
  {
    if (!(Globals::$session_obj instanceof Session)) {
      return FALSE;
    }
    if (!preg_match("/^$parent_path/", $new_default_subpath)) {
      throw new CategoryException("Category::set_default_category($parent_path, $new_default_subpath): new subpath is not a descendent of '$parent_path'");
    }
    
    // This nonsense is necessary to deal with PHP's lame magic method implementation: it doesn't
    //  work for array or object valued attributes
    $ar = Globals::$session_obj->category_defaults;
    if (!is_array($ar)) {
      $ar = array();
    }
    $ar[$parent_path] = $new_default_subpath;
    Globals::$session_obj->category_defaults = $ar;
    return TRUE;
  } // end of set_default_category()
  
  public static function get_default_category($parent_path)
  {
    if (!(Globals::$session_obj instanceof Session)) {
      return FALSE;
    }
    $ar = Globals::$session_obj->category_defaults;
    return is_array($ar) && isset($ar[$parent_path]) ? new Category($this->dbaccess, $ar[$parent_path])
        : FALSE;
  } // end of get_default_category()

  // Instance Methods
  
  // public function save()
  // {
  //   $this->rationalize_this_helper();
  //   return parent::save();
  // } // end of save()
  
  public function children()
  {
    $tmp_ar = $this->dbaccess->select_from_table($this->tablename, 'path', array('parent' => $this->path));
    $ar = array();
    foreach ($tmp_ar as $row) {
      $ar[] = $tmp = new Category($this->dbaccess, $row['path']);
    }
    return $ar;
  } // end of children()
  
  public function descendents($include_self = FALSE)
  {
    $ar = $children = $this->children();
    foreach ($children as $child) {
      $ar = array_merge($ar, $child->descendents());
    }
    if ($include_self) {
      $ar[] = $this;
    }
    return $ar;
  } // end of descendents()

  public function antecedents($include_self = FALSE)
  {
    $path_ar = explode('_', $include_self ? $this->path : $this->parent);
    $ar = array(($path = array_shift($path_ar)));
    while ($path_ar) {
      $ar[] = ($path .= '_' . array_shift($path_ar));
    }
    return $ar;
  } // end of antecedents()
  
  public function table_of_categories($parent = NULL)
  {
    $where = $parent ? array('parent' => $parent) : NULL;
    // $str = "<table frame=\"box\" rules=\"rows\">\n";
    $str = "<table class=\"box\">\n";
    $str .= "  <thead>\n";
    $str .= "  <tr><th colspan=\"6\">Currently Defined Categories</th></tr>\n";
    $str .= "  <tr class=\"category_title\"><th>Sibling Idx</th>"
      . "    <th>Parent</th><th>Name</th><th>Title</th></tr>\n";
    $str .= "  <tbody>\n";
    foreach ($this->get_objects_where($where, 'order by parent, sibling_ordinal') as $obj_tmp) {
      $str .= "  <tr class=\"category_row\">"
        . "<td class=\"center\">{$obj_tmp->sibling_ordinal}</td>"
        . "<td>$obj_tmp->parent</td>"
        . "<td>$obj_tmp->name</td>"
        . "<td>$obj_tmp->title</td>"
        . "</tr>\n";
    }
    $str .= "</table>\n";
    return $str;
  } // end of table_of_categories()
  
  protected function parent_form_func($parent = NULL)
  {
    $tmp_ar = $this->get_objects_where($parent ? NULL : array('parent' => $parent), 'order by parent,name');
    $str = "<select name=\"parent\" class=\"{form_classes}\" {form_attributes}>\n";
    $str .= "  <option value=\"_\">Top Level [_]</option>\n";
    foreach ($tmp_ar as $obj) {
      $str .= "  <option value=\"{$obj->path}_\">$obj->title [{$obj->path}_]</option>\n";
    }
    return $str . "</select>\n";
  } // end of parent_form_func()
  
  public function process_form($rc)
  {
    parent::process_form($rc);

    // initialize sibling_ordinal if necessary
    if (!isset($this->sibling_ordinal)) {
      $this_parent = $this->parent ? $this->parent : '_root_';
      if (!isset(Category::$parameters->$this_parent)) {
        if ($this_parent != '_root_') {
          $parent_obj = new Category($this->dbaccess, $this_parent);
          $parent_obj->save();
        }
        Category::$parameters->$this_parent = 0;
      }
      Category::$parameters->$this_parent += 1;
      $this->sibling_ordinal = Category::$parameters->$this_parent;
    }
    if (!isset($this->title) || !$this->title) {
      $this->title = preg_replace('/ /', '->', ucwords(preg_replace('/_/', ' ', $this->path)));
    }

    $this->save();
  } // end of process_form()
  
  public function delete()
  {
    // delete all references to $this->path in all categorized AnInstance classes
    // first pass is a test which should generate an exception if there is a problem
    try {
      if (($this_ajoin_list = AJoin::get_ajoins_for($this))) {
        foreach ($this_ajoin_list as $ajoin) {
          foreach ($ajoin->select_joined_objects($this) as $obj) {
            $obj->delete_category_references_test($this->path);
          }
        }
      }
    } catch (Exception $e) {
      throw new CategoryException("Category::delete(): failed to delete Category '$this'\n  Original Message:\n" . $e->getMessage());
    }

    // second pass is for real
    if ($this_ajoin_list) {
      foreach ($this_ajoin_list as $ajoin) {
        foreach ($ajoin->select_joined_objects($this) as $obj) {
          $obj->delete_category_references($this->path);
        }
      }
    }

    // delete recursively all children
    foreach ($this->descendents() as $obj) {
      $obj->delete();
    }
    
    // adjust sibling ordinal for all siblings greater in sort order
    $list = $this->get_objects_where("parent = '$this->parent' and sibling_ordinal > $this->sibling_ordinal");
    foreach ($list as $obj) {
      $obj->sibling_ordinal -= 1;
      $obj->save();
    }

    // decrement child count of parent
    $this_parent = $this->parent ? $this->parent : '_root_';
    Category::$parameters->$this_parent -= 1;

    parent::delete();
    return TRUE;
  } // end of delete()
}


class CategoryManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Category', 'path');
  } // end of __construct()
  
  public function render_form($rc, $form_top = NULL, $form_bottom = NULL, $actions = NULL)
  {
    $obj = new Category($this->dbaccess);
    return parent::render_form($rc, array($obj, 'table_of_categories'), $form_bottom, $actions);
  } // end of render_form()
}
?>
