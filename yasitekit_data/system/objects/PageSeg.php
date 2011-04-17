<?php
/*
#doc-start
h1.  PageSeg.php - Page Segment objects

Created by  on 2010-02-28.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

This module defines a bunch of classes used in page construction:

# PageSeg - basic building block. Used as a base class for everything else. "Basic Segment":#page_seg
# PageSegText - container for text segments. "programatic text":#page_seg_text
# PageSegFile - container for content which comes from a file. "including files":#page_seg_file
# PageSegList - container for a list of PageSeg objects which are rendered
as a concatenated string. "Segment list":#page_seg_list
# PageSegElt - wraps a PageSegList in an HTML, XHTML, XML, etc element, optionally with
attributes. "Element ":#page_seg_elt
# Vars - supplies a container for variables and template substitution. "Vars":#vars

All objects have an instance method called _dump(indent = 0)_ which may be called to
return a diagnostic string. _dump()_ is called recursively by all container objects.

h2(#page_seg). PageSeg Class

All PageSeg instances are uniquely named.

The PageSeg class provides basic services and ensures that a _render()_ method
exists. All errors which are discovered cause throw exceptions.

h3. Instantiation

Don't do it because it won't render

pre. $foo = new PageSeg('name');

h3. Class Attributes

* file_not_found_function = Array('PageSeg', 'file_not_found') - the _callback_ which
will be executed when a page cannot be loaded. This must be a function callback of two variables:
** $page_seg - the PageSeg object which invoked the call
** $fname - the name of the file which could not be loaded

h3. Instance Attributes

* name - string - the name of this segment
* content - string - content of the object. Implicitly calls _render()_ method.

h3. Class Methods

* flush_cache() - empties the record of all defined names. Used to re-initialize
the PageSeg list in cases where the default page won't do.
* get_by_name(name) - looks for a PageSeg named _name_ in the cache and returns
it or FALSE if not found.
* forget_all() - flushes PageSeg cache of PageSeg objects
* file_not_found_function($fname) - default 'page not found' function

h3. Instance Methods

Various magic methods: __toString(), __get(name), __set(name, value), __isset(name),
and __unset(name) ensure that these things make sense and throw reasonable errors
if detected.

* del_self() - removes _this_ PageSeg object from the cache of PageSeg objects
* render() - throws an exception to announce that the extension failed to define
this method.

h2(#page_seg_text). PageSegText Class extends PageSeg

The PageSegText class is a container for programmatically generated text

h3. Instantiation

pre. $foo = new PageSegText(name, optional_initial_content);

Where:

* name - is the unique name of the segment
* optional_initial_content can be used to define the content of the segment

h3. Attributes

All attributes are inherited from PageSeg. So they are _name_ and _content_.

h3. Class Methods

* close_all_open() - closes all open PageSetText segments.

h3. Instance Methods

* append(string) - appends the supplied string to the segment's content. This is
faster than _open()_ / _close()_ sequences.
* open() - opens the segment so that all output is captured by the segment. Does
this by calling _ob_start()_
* close() - closes the segment and captures output generated from previous
corresponding open. Uses _ob_get_clean()_. NOTE: output buffer methods can be
nested and so can PageSetText object open's and closes. NOTE: Improper nesting
of opens and closes causes all open segments to close and throws an exception.

h2(#page_seg_file). PageSegFile Class extends PageSeg

PageSegFile objects encapsulate the content of a single file.

PageSegFile objects are special in that they are rendered using the PHP 'include'
mechanism, so they can execute PHP code and modify the content and structure of
the page. This makes it a very bad idea to include them more than once. It turns
out the there are pitfalls in using the 'include_once' directive, so the PageSegFile
class simply includes them once and caches the result. Any attempt to modify
a PageSegFile object after rendering throws an exception.

h3. Instantiation

pre. $foo = new PageSegFile(name, file_name, $missing_file_ok = FALSE);

where:

* name - string - required - is the unique name of the segment
* file_name - string - required - is the name of the include file
* missing_file_ok - boolean - optional - set to TRUE if it's OK for the file to
be missing. The default action causes _render()_ to return an error message
if the file is not found in the include path; if OK, then returns an empty string.

h3. Attributes

* name - inherited from PageSeg - the unique name of the segment
* content - inherited from PageSeg - the content of the segment. Implicitly calls _render()_
* file_name - the name of the included file
* missing_file_ok - flag which controls rendering result if _file_name_ is not found.

h3. Class Methods

None

h3. Instance Methods

* render() - includes the file and returns it's unaltered content. Throws exception
if file inclusion failed

h2(#page_seg_list). PageSegList Class extends PageSeg

A PageSegList contains a list of PageSeg objects. It provides means to
manipulate the list and renders all components as a single string

h3. Instantiation

pre. $foo = new PageSegList(name[, PageSeg object, PageSeg object, ...]);

Creates a new list and optionally initializes it to the PageSeg objects listed as arguments.

h3. Attributes

Inherits the _name_ and _content_ attributes from PageSeg

h3. Class Methods

None

h3. Instance Methods

* list mainuplation: each of the following five methods takes a variable number of arguments
which may be either the name of a
PageSeg or a PageSeg object and adds them to or removes them from the list. The objects
are not affected in any way - in particular, they do not disappear because they
are referred to by an internal cache in the class PageSeg.
** append(name or PageSeg) - appends to the end of the list of segments
** prepend(name or PageSeg) - puts at the head of the list of segments
** insert_before(index, name or PageSeg) - inserts it ahead of the supplied
index. index == 0 is the same as _prepend()_; index larger than the number of segments
is the same as _append()_ _index_ may be:
*** an integer - the numerical index of the list, starting from 0
*** a string - taken as the name of a PageSeg object. Insersion is before the _first_
occurance of the named object.
*** a Page Seg instance - same action as if it is a string.
** insert_after(index, ...) - same as insert_before(), but inserts before index instead
of after. The index can be a integer, string or PageSeg instances - as in _insert_before()_
** del(name or PageSeg) - removes from list
* find_by_name_or_false($seg_name) - returns the segment corresponding to _$seg_name_
or FALSE;
* get_index_of(string or PageSeg or integer) - returns the integer index of the argument
in the list. Throws exception of not found or argument isn't one of the correct types.
* render() - returns the string made by concatenating the renderings of all segments in
the list.

h2(#page_seg_elt). PageSegElt Class extends PageSegList

Notice that this extends PageSegList rather than Page Seg, so it inherits all
of the list manipulation methods of pageSegList.

h3. Instantiation

pre. $foo = new PageSegElt(name, elt_tag[, args ...]);

Where:

* name - string - required - unique name of the segment
* elt_tag - string - required - is the element start tag. As in SGML, HTML, XML, XHTML,
etc start tag.
* args - string or PageSeg instance - optional - an arbitrary of attribute definitions
and PageSeg objects. These arguments are parsed as follows:
** if an _arg_ is a PageSeg object, it is appended to the list of objects
** if it is a string, it is assumed to be an attribute:
*** if _arg_ contains an equals sign (=) it is broken apart and an 'attr="value"'
clause is included in the element start tag
*** if _arg_ does NOT contain an equals sign (=), then it is taken as a boolean
attribute - as common in HTML - and an 'attr' clause is included in the element
start tag.

NOTE: attribute definitions may be repeated. If they are, then their values will be
combined into a white-space separated string. If they are boolean attributes -
that is, having no value - they will simply be defined.

NOTE: conflicting definitions between _boolean_ attributes and _value assigned_
attributes throws an exception.

h3. Attributes

In addition to _name_ and _content_, which are inherited from PageSeg via PageSegList,
PageSegElt has:

* elt - string - element tag for this element
* attributes - string - the attributes string suitable for enclosing inside the
element start tag
* _attribute-name_ - string - the value of any assigned attribute.

h3. Class Methods

None

h3. Instance Methods

* add_attribute(attr_name, value = NULL) - adds the value or boolean attribute
* del_attribute(attr_name) - deletes the specified attribute if it exists. Fails
silently if not.
* render() - returns a string consisting of the rendering of the contained
PageSeg segments wrapped in an element start and end tag.

h2(#vars). Vars Class

The Vars class implements a Singleton pattern which holds variables and knows
how to implement token substitution on a supplied.

h3. How to Use Vars

Step 1: create an interesting collection of segments using the various extensions
of PageSeg. Within the PageSegText and PageSegFile segments, include tokens
of the form _{variablename}_. Also, include code segments of the form:

pre. $var_instance = Vars::getVars();
$var_instance->foo = 'value for foo';
$var_instance->page_title = 'title for page';

Step 2: Render the page

pre. $vars = Vars::getVars();
echo $vars->render($top_level_page_seg);

This will call _$top_level_page_seg->render()_ and apply the template
substitution logic to the output.

h3. Instantiation

pre. $foo = Vars::getVars();

Vars is a Singleton, so we don't use the normal 'new' operator. As in The
Imortal - there can be _only one_.

h3. Attributes

Attributes are created on the fly by assigning them values. If _$foo_ is a
Vars instance, then

pre. $foo->bar = some-stuff;

Will assign 'some-stuff' to the attribute _bar_, creating or re-assigning the value
as required.

h3. Class Methods

* getVars() - use instead of 'new Vars()' to get the single instance of Vars.

h3. Instance Methods

* render($seg) - returns a string created by calling $seg->render() and then
substituting every token of the form '{foo}' with the corresponding value
of the attributes of Vars. In other words, if 'foo' is defined to have the
value 'bar', then '{foo}' will be replaced by 'bar'. If 'foo' is not defined,
then '{foo}' will not be modified.


#end-doc
*/

// global variables

// end global variables

// class definitions

class PageSegException extends Exception {}

class PageSeg {
  public static $file_not_found_function = array('PageSeg', 'file_not_found');
  static private $defined_names = array();
  protected $name = NULL;
  protected $rendered = FALSE;
  public function __construct($name)
  {
    if (!$name)
      throw new PageSegException("PageSeg::__construct(name): name required");
    $this->name = $name;
    if (array_key_exists($name, PageSeg::$defined_names)) {
      $cls = get_class($this);
      throw new PageSegException("$cls::__construct(name, ...): $cls named $name already defined");
    }
    PageSeg::$defined_names[$name] = $this;
  } // end of __construct()
  
  // public function __destruct()
  // {
  //   echo "<div class=\"dump-output\">Ahhhh!!!!! " . get_class($this) . "($this->name) Died!!!!!!!!!!</div>\n";
  // } // end of __destruct()
  
  public static function flush_cache()
  {
    ob_start();
    debug_print_backtrace();
    error_log(ob_get_clean());
    PageSeg::$defined_names = array();
  } // end of flush_cache()

  public static function get_by_name($name)
  {
    return array_key_exists($name, PageSeg::$defined_names) ? PageSeg::$defined_names[$name] : FALSE;
  } // end of get_page_seg()
  
  public static function file_not_found($page_seg, $fname)
  {
    return "<h1>Error: 404 - Page Not Found</h1>\n<p>Unable to find page '$fname'</p>\n";
  } // end of file_not_found()
  
  public static function forget_all()
  {
    PageSeg::$defined_names = array();
  } // end of forget_all()
  
  public function del_self()
  {
    // echo "in PageSeg::del_self() for {$this->name}\n";
    if (array_key_exists($this->name, PageSeg::$defined_names)) {
      unset(PageSeg::$defined_names[$this->name]);
    }
  } // end of del_self()
  
  public function __toString()
  {
    try {
      return $this->render();
    } catch (Exception $e) {
      return "Unable to Render Segment $this->name: $e";
    }
    // return $this->content;
  } // end of __toString()
  
  public function __get($name)
  {
    // error_log("PageSeg::$this->name::__get($name): {$this->dump()}");
    switch ($name) {
      // case 'rendered':
      case 'name':
        return $this->$name;
      default:
        throw new PageSegException(get_class($this) . "::__get($name): undefined attribute '$name'");
    }
  } // end of __get()
  
  public function __set($name, $val)
  {
    switch ($name) {
      // case 'rendered':
      //   $this->$name = $val ? TRUE : FALSE;
      //   break;
      default:
        throw new PageSegException(get_class($this) . "::__set($name, value): attempt to set undefined attribute '$name'");
    }
  } // end of __set()
  
  public function __isset($name)
  {
    switch ($name) {
      case 'content':
        return TRUE;
      default:
      throw new PageSegException(get_class($this) . "::__isset($name): test of undefined attribute '$name'");
    }
  } // end of __isset()
  
  public function __unset($name)
  {
    throw new PageSegException(get_class($this) . "::__unset($name): attempt to unset attribute '$name'");
  } // end of __unset()
  
  protected static function render_helper($x)
  {
    // echo "<div class=\"dump-output\">\n";
    // echo "render_helper($x->name)\n";
    // var_dump($x);
    // echo "</div>\n";
    $s = $x->render();
    if ($s && $s[strlen($s)-1] != "\n")
      $s .= "\n";
    return $s;
  } // end of render_helper()

  public function render()
  {
    // error_log("PageSeg::{$this->name}->render(): {$this->dump()}");
    // error_log(substr($this->content, 0, 40) . "\n");
    $cls = get_class($this);
    throw new PageSegException("PageSeg::render(): $cls::render() not defined");
  } // end of render()
  
  public function dump($indent = 0)
  {
    return str_repeat(' ', $indent) . __LINE__ . ': ' . "PageSeg: '$this->name' - is an empty segment\n";
  } // end of dump()
}

class PageSegTextException extends Exception {}

class PageSegText extends PageSeg {
  private static $open_segs = array();
  private $content;
  private $open_flag = FALSE;
  public function __construct($name, $initial_text = NULL)
  {
    parent::__construct($name);
    $this->content = $initial_text ? $initial_text : '';
  } // end of __construct()

  public static function close_all_open()
  {
    while (($seg = array_pop(PageSegText::$open_segs))) {
      $seg->close();
    }
  } // end of close_all_open()
  
  public function append($str)
  {
    if ($this->rendered) {
      throw new PageSegTextException("PageSegText::append(): called after rendring");
    }
    if (is_string($str)) $this->content .= $str;
  } // end of append()

  public function open()
  {
    if ($this->rendered) {
      throw new PageSegTextException("PageSegText::open(): called after rendring");
    }
    if ($this->open_flag) {
      throw new PageSegTextException("PageSegText::open(): attempt to open already open segment '$this->name'");
    }
    $this->open_flag = TRUE;
    array_push(PageSegText::$open_segs, $this);
    ob_start();
  } // end of open()
  
  public function close()
  {
    if ($this->rendered) {
      throw new PageSegTextException("PageSegText::close(): called after rendring");
    }
    if (!$this->open_flag) {
      PageSegText::close_all_open();
      throw new PageSegTextException("PageSegText::close(): attempt to close already closed segment '$this->name'");
    }
    $cur_open = array_pop(PageSegText::$open_segs);
    if ($cur_open !== $this) {
      array_push(PageSegText::$open_segs, $cur_open);
      PageSegText::close_all_open();
      throw new PageSegTextException("PageSegText::close(): nesting error: attempt to close segment $this->name when $cur_open->name was open");
    }
    $this->content .= ob_get_clean();
    $this->open_flag = FALSE;
  } // end of close()
  
  public function render()
  {
    // error_log("PageSegText::{$this->name}->render(): {$this->dump()}");
    // error_log(substr($this->content, 0, 40) . "\n");
    if ($this->open_flag) {
      $this->close();
      // throw new PageSegTextException("PageSegText::render(): render called while segment $this->name is open");
    }
    if ($this->rendered) {
      return $this->content;
    }
    $this->rendered = TRUE;
    return $this->content;
  } // end of render()
  
  public function dump($indent = 0)
  {
    $str = str_repeat(' ', $indent) . __LINE__ . ': ' . "PageSegText: '$this->name'\n";
    foreach (preg_split("/[\r\n]+/", substr($this->content, 0, 60) . " . . .\n") as $tmp) {
      $str .= str_repeat(' ', $indent + 4) . $tmp . "\n";
    }
    return $str;
  } // end of dump()
}

class PageSegFileException extends Exception {}

class PageSegFile  extends PageSeg {
  private $file_name;
  private $content;
  private $missing_file_ok;
  public function __construct($name, $file_name, $missing_file_ok = FALSE)
  {
    parent::__construct($name);
    $this->file_name = $file_name;
    $this->content = FALSE;
    $this->missing_file_ok = $missing_file_ok;
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'file_name':
      case 'missing_file_ok':
        return $this->$name;
      default:
        return parent::__get($name);
    }
  } // end of __get()

  public function render()
  {
    if ($this->rendered) {
      return $this->content;
    }
    // error_log("PageSegFile::{$this->name}->render(): {$this->dump()}");
    // error_log(substr($this->content, 0, 40) . "\n");
    $this->rendered = TRUE;
    // error_log("{$this->name}->rendered: '$this->rendered'");
    ob_start();
    // error_log("stop 1");
    try {
      $include_result = include($this->file_name);
    } catch (Exception $e) {
      Globals::add_message("<div class=\"dump-output\">\n" . $e->getMessage() . "\n</div>\n");
      return $this->content = "<div class=\"dump-output\">\nUnable to include $this->file_name\n"
        . ob_get_clean() ."</div>\n";
    }
    
    // error_log("stop 2");
    $this->content = ob_get_clean();
    // error_log("stop 3");
    // error_log("{$this->name}->render(): include_result - '$include_result'\n");
    if ($include_result === FALSE) {
      // error_log("{$this->name}: file not found\n");
      // error_log($this->content);
      if (!$this->missing_file_ok) {
        $this->content = call_user_func(PageSeg::$file_not_found_function, $this, $this->file_name);
      } else {
        // $this->content = '';
      }
    }
    return $this->content;
  } // end of render()
  
  public function dump($indent = 0)
  {
    return str_repeat(' ', $indent) . __LINE__ . ': ' . "PageSegFile: '{$this->name}({$this->file_name})': "
      . ($this->rendered ? "Rendered\n" : "Not Rendered\n");
  } // end of dump()
}

class PageSegListException extends Exception {}

class PageSegList extends PageSeg {
  protected $list = array();
  private $content;
  public function __construct()
  {
    $args = func_get_args();
    if (!$args) {
      throw new PageSegListException("PageSegList::__construct(name, ...): missing 'name' parameter");
    }
    $name = array_shift($args);
    parent::__construct($name);
    while (($seg = array_shift($args))) {
      if (!($seg instanceof PageSeg)) {
        throw new PageSegListException("PageSegList::__construct($name, ...): parameter $idx is NOT a PageSeg extension");
      }
      $this->list[] = $seg;
    }
  } // end of __construct()
  
  public function __get($name)
  {
    if ($name == 'content') {
      return $this->render();
    }
    return parent::__get($name);
  } // end of __get()
  
  public function del_self()
  {
    echo "in PageSegList::del_self() for {$this->name}\n";
    foreach ($this->list as $seg) {
      $seg->del_self();
    }
    parent::del_self();
  } // end of del_self()
  
  private function resolve_seg_arg($arg)
  {
    if ($arg instanceof PageSeg)
      return $arg;
    elseif (is_string($arg) && ($seg = PageSeg::get_by_name($arg)))
      return $seg;
    else
      return FALSE;
      // throw new PageSegListException("PageSegList::resolve_seg_arg(arg): arg is neither a PageSeg or the name of an existing one");
  } // end of resolve_seg_arg()

  public function append($seg)
  {
    foreach (func_get_args() as $seg) {
      if (($seg = $this->resolve_seg_arg($seg))) {
        array_push($this->list, $seg);
      }
    }
  } // end of append()
  
  public function prepend($seg)
  {
    $ar = array();
    foreach (func_get_args() as $seg) {
      if (($seg = $this->resolve_seg_arg($seg)))
        $ar[] = $seg;
    }
    $this->list = array_merge($ar, $this->list);
  } // end of prepend()
  
  public function del()
  {
    foreach (func_get_args() as $seg) {
      if (($seg = $this->resolve_seg_arg($seg)) === FALSE) {
        continue;
      }
      if (($idx = array_search($seg, $this->list)) !== FALSE) {
        // this removes the reference to the indicated segment in this list
        $this->list = array_splice($this->list, $idx, 1);
        // this removes the reference to the indicated segment from the PageSeg cache
        $seg->del_self();
        // at this point, the indicated segment should be garbage collected
      }
    }
  } // end of del()

  public function find_by_name_or_false($seg_name)
  {
    foreach ($this->list as $seg) {
      if ($seg->name == $seg_name)
        return $seg;
    }
    return FALSE;
  } // end of find_by_name_or_false()

  public function get_index_of($tmp)
  {
    if (is_string($tmp)) {
      if (($seg = $this->resolve_seg_arg($tmp)) === FALSE)
        throw new PageSegListException("PageSegList::get_index_of(): $idx does not resolve to a PageSeg");
      if (($idx = array_search($seg, $this->list)) === FALSE)
        throw new PageSegListException("PageSegList::get_index_of(): PageSeg $idx is not in list");
    } elseif ($tmp instanceof PageSeg) {
      if (($idx = array_search($tmp, $this->list)) === FALSE)
        throw new PageSegListException("PageSegList::get_index_of(): PageSeg $tmp->name is not in list");
    } elseif (is_int($tmp)) {
      $idx = $tmp <= 0 ? 0 : ($tmp >= count($this->list) ? count($this->list) - 1 : $tmp);
    } else {
      throw new PageSegListException("PageSegList::get_index_of(): argument error");
    }
    return $idx;
  } // end of get_index_of()
  
  public function insert_before(/* indx, seg, ...*/)
  {
    $args = func_get_args();
    $tmp = array_shift($args);
    $idx = $this->get_index_of($tmp);

    $ar = array();
    foreach ($args as $seg) {
      if (($seg = $this->resolve_seg_arg($seg)) !== FALSE) {
        $ar[] = $seg;
      }
    }
    if ($idx <= 0) {
      $this->list = array_merge($ar, $this->list);
    } else {
      $this->list = array_merge(array_slice($this->list, 0, $idx), $ar, array_slice($this->list, $idx));
    }
  } // end of insert_before()
  
  public function insert_after(/* indx, seg, ...*/)
  {
    $args = func_get_args();
    $tmp = array_shift($args);
    $idx = $this->get_index_of($tmp) + 1;

    $ar = array();
    foreach ($args as $seg) {
      if (($seg = $this->resolve_seg_arg($seg)) !== FALSE) {
        $ar[] = $seg;
      }
    }
    if ($idx >= count($this->list)) {
      $this->list = array_merge($this->list, $ar);
    } else {
      $this->list = array_merge(array_slice($this->list, 0, $idx), $ar, array_slice($this->list, $idx));
    }
  } // end of insert_after()

  public function render()
  {
    static $error_found_flag = FALSE;
    // error_log("PageSegList::{$this->name}->render(): {$this->dump()}");
    // error_log(substr($this->content, 0, 40) . "\n");
    // $ar = array_map(array('PageSeg', 'render_helper'), $this->list);
    $ar = array();
    foreach ($this->list as $x) {
      $ar[] = PageSeg::render_helper($x);

      // this catches all include warnings - plus, probably, some more stuff
      // FIXME: send this output someplace more sane.
      if (Globals::$site_installation == 'development' && !$error_found_flag) {
        if (($error_tmp = error_get_last()) && ($error_tmp['type'] &  E_WARNING)) {
          $error_found_flag = TRUE;
          echo "<div class=\"dump-output\">\n";
          echo "Warning Error rendering PageSegList '{$this->name}': rendering segment '{$x->name}'\n";
          echo " Error message: {$error_tmp['message']}\n";
          print_r($error_tmp);

          $path = $error_tmp['file'];
          if (strpos($path, Globals::$document_root) !== FALSE) {
            $path = substr($path, strlen(Globals::$document_root));
          } elseif (strpos($path, Globals::$private_data_root) !== FALSE) {
            $path = substr($path, strlen(Globals::$private_data_root));
          }
          echo " Error in File: $path\n";
          echo " Error at Line: {$error_tmp['line']}\n\n";
          echo " Error occurred while rendering Segment:\n";
          echo $x->dump() . "\n";

          echo $this->dump();
          echo "\nBacktrace\n";
          debug_print_backtrace();
          echo "</div>\n";
          break;
        }
      }
    }

    $this->content = implode('', $ar);
    $this->rendered = TRUE;
    return $this->content;
  } // end of render()
  
  public function dump($indent = 0)
  {
    $str = str_repeat(' ', $indent) . __LINE__ . ': ' . "PageSegList: '$this->name'\n";
    foreach ($this->list as $item)
      $str .= $item->dump($indent + 4);
    return $str;
  } // end of dump()
}

class PageSegEltException extends Exception {}

class PageSegElt extends PageSegList {
  private $elt = NULL;
  private $attributes = array();
  private $content;
  public function __construct($name, $elt)
  {
    $args = func_get_args();
    if (count($args) < 2) {
      throw new PageSegListException("PageSegElt::__construct(name, elt-tag...): missing 'name' parameter");
    }
    $name = array_shift($args);
    $this->elt = array_shift($args);
    parent::__construct($name);
    $this->attributes = array('id' => $name);
    while (count($args) > 0) {
      $seg = array_shift($args);
      if ($seg instanceof PageSeg) {
        $this->append($seg);
      } elseif (is_string($seg)) {
        @list($attr, $val) = explode('=', $seg);
        $this->add_attribute($attr, $val);
      } else {
        throw new PageSegListException("PageSegList::__construct($name, ...): parameter $idx is NOT a PageSeg extension or element attribute");
      }
    }
  } // end of __construct()
  
  public function __get($name)
  {
    switch ($name) {
      case 'elt':
        return $this->$name;
      case 'attributes':
        return $this->render_attributes();
      default:
        if (array_key_exists($name, $this->attributes)) {
          return $this->attributes[$name];
        } else {
          return parent::__get($name);
        }
    }
  } // end of __get()
  
  private function render_attributes()
  {
    $ar = array();
    foreach ($this->attributes as $attr => $value) {
      if (is_string($value)) {
        $ar[] = "$attr=\"{$value}\"";
      } elseif (is_bool($value)) {
        $ar[] = "$attr";
      } elseif (is_array($value)) {
        $ar[] = "$attr=\"" . implode(' ', $value) . "\"";
      } else {
        throw new PageSegEltException("PageSegElt::render_attributes(): internal error for attribute '$attr'");
      }
    }
    return implode(' ', $ar);
  } // end of render_attributes()
  
  public function add_attribute($attr, $value = NULL)
  {
    $attr = trim($attr);
    $value = trim($value);
    if ($value) {
      if (array_key_exists($attr, $this->attributes)) {
        if (is_string($tmp = $this->attributes[$attr])) $this->attributes[$attr] = array($tmp);
        $this->attributes[$attr][] = $value;
      } else {
        $this->attributes[$attr] = $value;
      }
    } else {
      if (array_key_exists($attr, $this->attributes)) {
        if (!is_bool($this->attributes[$attr])) {
          throw new PageSegEltException("PageSegElt::__construct($name, ...): attribute '$attr' specified both with and w/o values");
        }
      } else {
        $this->attributes[$attr] = TRUE;
      }
    }
  } // end of add_attribute()
  
  public function del_attribute($attr)
  {
    if (array_key_exists($attr, $this->attributes))
      unset($this->attributes[$attr]);
  } // end of del_attribute()

  public function render()
  {
    // error_log("PageSegElt::{$this->name}->render(): {$this->dump()}");
    // error_log(substr($this->content, 0, 40) . "\n");
    $elt_tail = ($attributes = $this->render_attributes()) ? " $attributes>\n" : ">\n";
    $this->content = "<{$this->elt}{$elt_tail}" . parent::render() . "</{$this->elt}> <!-- $this->name -->\n";
    // $this->rendered = TRUE;
    return $this->content;
  } // end of render()
  
  public function dump($indent = 0)
  {
    $str = str_repeat(' ', $indent) . __LINE__ . ': ' . "PageSegElt: '{$this->name}({$this->elt})'\n";
    $str .= str_repeat(' ', $indent+2) . "Attributes: " . $this->render_attributes() . "\n";
    return $str . parent::dump($indent + 4);
  } // end of dump()
}

class VarsException extends Exception {}

class Vars {
  static private $instance = NULL;
  private $page_seg_list = NULL;
  private $values = array();
  private $keys = array();
  private $replacements = array();
  
  private function __construct()
  {
    if (Vars::$instance) {
      throw new VarsException("Vars::__construct(): Vars constructor called twice - illegal for singleton object");
    }
    Vars::$instance = $this;
    $this->page_seg_list = new PageSegList('_page');
  } // end of __construct()
  
  public function __get($name)
  {
    return array_key_exists($name, $this->values) ? $this->values[$name] : FALSE;
  } // end of __get()
  
  public function __set($name, $value)
  {
    if (preg_match('/^[a-z]\w*$/', $name) == 0)
      throw new VarsException("Vars::__set($name, value): illegal attribute name: '$name'");

    $this->values[$name] = $value;
  } // end of __set()

  public static function getVars()
  {
    if (!Vars::$instance) new Vars();
    return Vars::$instance;
  } // end of getVars()
  
  private function initialize_preg_arrays()
  {
    $this->keys = array();
    $this->replacements = array();
    foreach ($this->values as $key => $value) {
      $this->keys[] = "/{{$key}}/";
      $this->replacements[] = "$value";
    }
  } // end of initialize_preg_arrays()

  public function render($seg)
  {
    // error_log("Vars::{$this->name}->render(): {$this->dump()}");
    // error_log(" \$seg: {$seg->dump()}");
    if (!($seg instanceof PageSeg)) {
      throw new VarsException("Vars::render(seg): seg is not derived from PageSeg");
    }
    $str = $seg->render();
    $this->initialize_preg_arrays();
    $result = preg_replace($this->keys, $this->replacements, $str);
    if (!$result) {
      throw new VarsException("Vars::render(segment): Error in performing template replacement");
    }
    return $result;
  } // end of render()
  
  public function dump($msg = '')
  {
    $str = __LINE__ . ': ' . "Vars: $msg\n";
    foreach ($this->values as $name => $val) {
      // echo $name . "\n";
      if ($val instanceof PageSeg) echo $val->dump();
      // echo "$val\n";
      try {
      $str .= "   $name => '$val'\n";
      } catch (Exception $e) {
        // echo "$e\n";
      }
    }
    return $str;
  } // end of dump()
}


?>
