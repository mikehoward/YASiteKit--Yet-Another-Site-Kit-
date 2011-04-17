<?php
/*
#doc-start
h1.  Page.php - The Basic HTML Page object.

Created by Mike Howard on 2010-07-17.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved. Licensed under the terms of the GNU Lesser
GNUL License, version 3.  See http://www.gnu.org/licenses/ for details.

bq. THIS SOFTWARE HAS NO WARRANTEE OR REPRESENTATION FOR FITNESS OF PURPOSE.
USE AT YOUR OWN RISK.

The *Page* object extends the PageBase object. It only exists so to allow
page structure specialization for web sites.

Web pages are constructed of parts and pieces which appear to be scattered around.
Take a look in the _page_structure_ directories - both site and system - and look
over the fragments.

Each of these fragments is a mixture of HTML, PHP, and _tokens_. HTML and PHP
you already know. _tokens_ are specific to YASiteKit. They are the names of
YASiteKit object attributes wrapped in braces - such as _{page_title}_.

The final rendering of a page includes replacing all _tokens_ which contain
valid Page object attributes with their current values. This allows each
page to specialize itself. "see Page Content Files":#page_content, below.

h2. Page class definition pattern

The basic pattern for a Page object is:

pre. class Page extends PageBase {
&nbsp;  public function __construct($page_name)
&nbsp;  {
&nbsp;    parent::__construct($page_name);
&nbsp;    #page layout definition
&nbsp;    $this->prepare($name-of-content = 'content');
&nbsp;  } // end of __construct()
}

* "Instantiation":#instantiation
* "Attributes":#attributes
* "Class Methods":#class_methods
* "Instance Methods":#instance_methods

h2(#instantiation). Instantiation

The Page object is created in "includes.php":/doc.d/system-includes/includes.html, so
you normally don't have to worry about it. If you want to, do it like this:

pre. $page_obj = new Page($page_name);

where _$page_name_ is the name of a file containing the page content.

h2(#attributes). Attributes

See "PageBase.php":/doc.d/system-objects/PageBase.html

h2(#class_methods). Class Methods

See "PageBase.php":/doc.d/system-objects/PageBase.html


h2(#instance_methods). Instance Methods

See "PageBase.php":/doc.d/system-objects/PageBase.html

h2(#page_content). Page Content Files

h3. Access Control et al

Access Control is managed by setting the _required_authority_ attribute of the
global Page object. It must be either NULL or a list of authority values [X A C, etc]
- as a string of comma separated values or as an array of separate strings.
See the 'has_authority()' instance method in "Account.php":/doc.d/system-objects/Account.html
for more detail.

* Globals::$page_obj->required_authority = NULL;  // unrestricted access
* Globals::$page_obj->required_authority = 'X';   // administrative only
* Globals::$page_obj->required_authority = 'A,S,X'; // Artist, Staff, and Admin access

h3. Meta Values

You can add metadata as using the _add_meta(name, content)_ method. This will add
OR replace metadata values of the the same name.

For convenience, the _protected_ static variable _$default_meta_ contains:

* content-type: "text/html; charset=utf-8"
* imagetoolbar: "no"
* robots: "nofollow, noindex"

You will probably want to override the _robots_ entry for pages you want accessed
by search engines.

h3. Style Sheets.

Add style sheets using the _add_style_sheet($name, path, media, $ie = FALSE)_ instance method.
This ensures the style sheet PageSeg object has a unique name, by prefixing it with *style_sheet_*.

If the argument '$ie' is not false, then the stylesheet link is wrapped in an IE conditional
comment and the value of '$ie' is inserted as the condition. Thus if '$ie' is 'LE 6',
the conditional comment will start with &l

For convenience, the _protected_ static variable _$default_stylesheets contains:
pre. array(
&nbsp;    array("stylesheets_screen", "/css/screen.css", "screen", NULL),
&nbsp;    array("stylesheets_print", "/css/print.css", "print", NULL),
&nbsp;    array("stylesheets_ie", "/css/ie.css", "screen", 'LT 6'),
&nbsp;    array('stylesheets_handheld', '/css/handheld.css', 'handheld', NULL),);

h3. Modifying PageSeg elements

Every PageSeg has a unique name. Get the actual object using _Globals::$page_obj->get_by_name(name)_,
where _name_ is the name of the segment.

At that point you can modify the content using the objects methods. See
"PageSeg.php":/doc.d/system-objects/PageSeg.html for details.

h3. Templating

NOTE: the attributes described here are those used in the YASiteKit site
and in the supplied page fragments in the *site-framework* kits. You are
free to replace any and all of the fragments with your own and then change
the names of the attributes you use. The principal will still be the same.

Page content files must define some page attributes in order to use the supplied
page fragments / page structure:

* Globals::$page_obj->page_title - string - required - the title at the top of the page
* Globals::$page_obj->page_header - string - required - the h1 header content at the top of
content portion of the page.

You should also set the _required_authority_ header for all pages which do not allow
general, unrestricted access and the _robots_ metadata (see metadata above) for pages
you want to have indexed by search engines.

#end-doc
*/

// global variables
require_once('PageBase.php');

// end global variables

// class definitions
class Page extends PageBase {
  public function __construct($page_name)
  {
    parent::__construct($page_name);
    // well known page parts
     $this->content = new PageSegElt('content', 'div', 'class=box',
         new PageSegFile('content_file', $page_name));
     if (Globals::$account_obj instanceof Account && Globals::$account_obj->logged_in()) {
       $this->content->append(new PageSegFile('account-nav', 'account_nav.php'));
     }

     $secondary_menu = new PageSegElt('secondary-menu', 'div',
       new PageSegFile('secondary-nav', 'secondary_nav.php')
       // ,  new PageSegFile('product-menu', 'product_menu.php')
       );

     $content_container = new PageSegElt('content-container', 'div',
       $this->content,
       // this defines the secondry content menus physically after the content (for SEO), so
       //  it must be moved into position using CSS
       $secondary_menu
       );

     // manage the following two sgements using add_meta() and add_style_sheet()
     $this->meta = new PageSegList('meta');
     foreach (PageBase::$default_meta as $key => $meta_content) {
       $this->add_meta($key, $meta_content);
     }
     $this->style_sheets = new PageSegList('stylesheets');
     foreach (PageBase::$default_stylesheets as $row) {
       list($name, $path, $media, $ie) = $row;
       $this->add_style_sheet($name, $path, $media, $ie);
     }

     // define structure of page
     $this->head = new PageSegElt('head', 'head',
       new PageSegText('title', '<title>{page_title}</title>'),
       $this->meta,
       $this->style_sheets);

     $this->body = new PageSegElt('body', 'body',
       new PageSegFile('header', 'header.php'),
       $content_container,
       new PageSegFile('main-nav', 'main_nav.php'),
       new PageSegFile('footer', 'footer.php'));
     $this->javascript = new PageSegList('javascript', new PageSegFile('jquery', 'javascript.php'),
       new PageSegFile('local_javascript', 'local_javascript.php'));
     // add analytics if this is the production version
     if (Globals::$site_installation == 'production') {
       // NOTE: missing_file_ok is TRUE here, so no warning is generated
       $this->javascript->append(new PageSegFile('analytics', 'analytics.php', TRUE));
     }
     $this->body->append($this->javascript);

     $this->page_root = new PageSegList('html_page', new PageSegText('doctype', PageBase::DOCTYPE_HTML401TRANS),
         new PageSegElt('html', 'html', $this->head, $this->body));

     // the next two statements MUST be executed in this order because the 'content' is the
     //  only segment which is allowed to modify the structure and content of the page,
     //  so it must be rendered first and must have access to the globabl page object.

    $this->prepare();
  } // end of __construct()
}

// end class definitions

// function definitions

// end function definitions

// initial processing of POST data

// dispatch actions

?>
