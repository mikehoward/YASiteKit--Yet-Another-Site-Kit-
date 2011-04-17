<?php
/*
#doc-start
h1. Link.php - the link management object.

Created by Mike on 2011-02-20
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2011.
All Rights Reserved.

*Link* objects are used to manage site links.

Features:

* the _link()_ method returns an anchor link - complete with attributes which
can be assigned programatically or via the management interface.
* the _site_map_entry()_ method returns the properly formatted XML url element
for inclusion of this link in the site map - if appropriate.
* menus can be constructed dynamically by examining the Category _links_
"see Constructing Menus":#construct_menu

h2. Attributes

*Link* instances have two types of attributes: _intrinsic_ and _additional_

The _intrinsic_ attributes are:

* link_id - int - Link Number - automatically assigned and automatically
incremented. This is the key field and is used to join Link instances
to LinkGroup instances
* title - varchar(255) - Title - the title used in the link
* uri - varchar(255) - Link URI, w/o protocol or domain - required
* id - varchar(255) - Link id attribute - optional
* name - varchar(255) - Link name attribute - optional
* classes - varchar(255) - Link Class Attribute(s) - bundled into the _class_ attribute
* follow - enum(Y,N) - Google & friends (& competition) should follow this link and
it should go into the site map.
* link_attributes - pile - anchor linke attributes - arbitrary
* url_param_names - pile - key-values for URL parameter names
* blocks - join(LinkGroup.link_group_name) - Link Blocks - the LinkGroups this Link
is joined to. Note this is a 1 to N join, so an item may be part of many Link Blocks

The _additional_ attrbutes are created by assignment - as in

pre. $foo->title = 'This title text for the menu link'

and are interpolated as attributes of the menu link returned by the _link()_
method. Nothing is checked.

They may be manually managed via the Attributes &lt;textarea&gt; element
at /manage/Link.

h2. Class Methods

* create_site_maps($dbaccess) - creates an array of XML Sitemaps for the site.
Each map is guaranteed to be less than 10 Meg in size and hold fewer than 50,000
entries. Everything is done 'in memory' so this probably won't work for large sites.
A better technique will be to open a file and write the things out. This is a FIXME LATER!!!!

h2. Instance Methods

* link() - returns an HTML Anchor element pointing to this link
* site_map_entry() - if _follow_ is Y, returns the XML url element for the site map for this link.
If _follow_ is N, returns ''

h2(#constructing_menus). Constructing Menus

The _links_ category forms a tree of Link objects. The most specific Category
a Link is in consists of the Link itself and is named by replacing all the
slashes (/) in the _uri_ with underscores and appending the result to the token
_links_.

Thus the Category links_foo_bar_baz holds the Link to /foo/bar/baz.

To get all the 'foo' links, use the Category static method:

pre. $list = Category::get_instances_for_catgory('links_foo', Globals::$dbaccess, 'Link');

This will return all the Link instances which begin with '/foo'.

See "Category.php":/doc.d/system-objects/Category.html for gory details and alternatives.
#end-doc
*/

// global variables
require_once('aclass.php');
require_once('Category.php');

AClass::define_class('Link', 'link_id', 
  array( // field definitions
    array('link_id', 'int', 'Link Number'),
    array('title', 'varchar(255)', 'Title'),
    array('uri', 'varchar(255)', 'URI - w/o http://foo.com'),
    array('id', 'varchar(255)', 'Link id attribute'),
    array('classes', 'varchar(255)', 'Link Class Attribute(s)'),
    array('follow', 'enum(Y,N)', 'Google should follow link'),
    array('link_attributes', 'pile', 'Additional Link Attributes'),

    // Sitemap Data
    array('lastmod', 'date', 'Last Modification of Link'),
    array('changefreq', 'enum(always,hourly,daily,weekly,monthly,yearly,never)', 'Changfreq'),
    array('priority', 'char(3)', 'Priority - 0.0 thru 1.0'),
    array('link_category', 'category(links)', 'Link Category'),
  ),
  array(// attribute definitions
    'title' => 'required',
    'uri' => 'required',
    'priority' => array('filter' => '\d\.\d'),
      ));
// end global variables

// class definitions
class LinkException extends Exception {}

class Link extends AnInstance {
  static $parameters = FALSE;
  public function __construct($dbaccess, $attribute_values = array())
  {
    if (!Link::$parameters) {
      require_once('Parameters.php');
      Link::$parameters = new Parameters($dbaccess, 'Link');
      if (!isset(Link::$parameters->next_link_id)) {
        Link::$parameters->next_link_id = 1;
      }
    }
    parent::__construct('Link', $dbaccess, $attribute_values);

    if (isset($this->link_id) && ($this->link_id < 1 || $this->link_id >= Link::$parameters->next_link_id)) {
      throw new LinkException("Link::__construct(): Illegal link_id: $this->link_id");
    }
  } // end of __construct()
  
  public function __set($name, $value) {
    switch ($name) {
      case 'uri':
        $this->link_category = 'links' . preg_replace('|/|', '_', strtolower($value));
        // Intentional FALL THRU
      default:
        parent::__set($name, $value);
        break;
    }
  } // end of __set()
  
  public function form($form_action = NULL, $top_half = '', $bottom_half = '', $actions = '') {
    foreach ($this->pile_keys('link_attributes') as $key) {
      $ar[] = "$key = ${$this->pile_get('link_attributes', $key)}";
    }
    $bottom_half .= "<li>"
      . "<label for=\"attribute_pile\">Attributes - one per line, as in 'attr = value'</label>"
      . "<textarea name=\"attribute_pile\" class=\"float-right\" cols=\"40\" rows=\"10\">\n"
      . implode("\n", $ar)
      . "\n</textarea></li>\n";
    return parent::form($form_action, $top_half, $bottom_half, $actions);
  } // end of form()
  
  public function process_form($rc) {
    parent::process_form($rc);
    $new_pile = explode("\n", $rc->safe_post_attribute_pile);
    foreach ($this->pile_attributes() as $key => $val) {
      unset($this->$key);
    }
    foreach ($new_pile as $line) {
      list($key, $val) = preg_split('/\s*=\s*/', $line);
      $this->{trim($key)} = trim($val, " \t\n\r\"'");
    }
    $this->save();
  } // end of process_form()

  public function save() {
    if (!$this->link_id) {
      $this->link_id = Link::$parameters->next_link_id;
      Link::$parameters->next_link_id += 1;
    }
    return parent::save();
  } // end of save()

  // FIXME: if request router takes parameters, then do something bright
  //  like return a form
  public function link($attributes = '') {
    $str = "<a href=\"$this->uri\"";
    if ($this->id) {
      $str .= " id=\"$this->id\"";
    }
    if ($this->classes) {
      $str .= " class=\"$this->classes\"";
    }
    if ($this->follow == 'N') {
      $str .= " rel=\"nofollow\"";
    }
    if ($attributes) {
      if (is_string($attributes)) {
        $tmp = preg_split('/\s*,\s*/', $attributes);
        foreach ($tmp as $line) {
          list($attr, $val) = preg_split('/\s*=\s*/', $line);
          $str .= " " . trim($attr) . "=\"" . trim($val, " \t\n\r'\"") . "\"";
        }
      } elseif (is_array($attributes)) {
        foreach ($attributes as $attr => $val) {
          $str .= " $attr=\"$val\"";
        }
      }
    }
    foreach ($this->pile_keys('link_attributes') as $attr => $val) {
      $str .= " $attr=\"$val\"";
    }
    $str .= ">$this->title</a>";
    return $str;
  } // end of link()
  
  public function site_map_entry() {
    if ($this->follow == 'N') {
      return '';
    }
    
    // build & return site map entry
    $str = "  <url>\n";
    // create url encoded & entity escaped URL
    $ar = array();
    foreach (explode('/', $this->uri) as $fragment) {
      $ar[] = preg_replace(array('/%26/', '/%22/', '/%3E/', '/%3C/', '/%27/'),
                           array('&amp;', '&quot', '&gt;', '&lt;', '&apos'), urlencode($fragment));
    }
    $uri = implode('/', $ar);
    if ($uri != '/') {
      $encoded_url = Globals::$site_url . "/$uri/";
    } else {
      $encoded_url = Globals::$site_url . "$uri/";
    }
    $str .= "    <loc>$encoded_url</loc>\n";
    // add optionals
    if (isset($this->lastmod)) {
      $str .= "    <lastmod>{$this->lastmod->format('Y-m-d')}</lastmod>\n";
    }
    if (isset($this->changefreq)) {
      $str .= "    <changefreq>$this->changefreq</changefreq>\n";
    }
    if (isset($this->priority)) {
      $str .= "    <priority>$this->priority</priority>\n";
    }
    $str .= "  </url>\n";
    
    return $str;
  } // end of site_map_entry()
}


class LinkManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Link', 'link_id');
  } // end of __construct()
}
?>
