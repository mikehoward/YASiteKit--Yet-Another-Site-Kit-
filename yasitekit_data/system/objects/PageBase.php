<?php
/*
#doc-start
h1.  PageBase.php - The Base Class for HTML Page Abstraction - a container for PageSeg and 

Created by  on 2010-03-15.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.


h2(#page). PageBase Class

The PageBase is not a complete page. It is designed to be extended by a single
Page class object which defines the layout of a page and then uses the
resources defined PageBase to implement page specialization, access control, and
rendering.

All dynamic YASiteKit pages are constructed as a heirarchic tree of PageSeg
objects. Briefly there are four types of PageSeg elements:

* PageSegText - which contain static text
* PageSegFile - which interpolate and execute an includeable file
* PageSegList - which are lists of PageSeg objects - allowing arrays
* PageSegElt - an extension of PageSegList which wraps a list of PageSeg
objects in an HTML Element - making it convenient to create &lt;div&gt;, etc
elements programatically.

See "PageSeg.php":/doc.d/system-objects/PageSeg.html for details.

h3. Subclassing

pre. class Page extends PageBase {
&nbsp;  public function __construct($page_name)
&nbsp;  {
&nbsp;    parent::__construct($page_name);
&nbsp;    #page layout definition
&nbsp;    $this->prepare('name of content segment' (defaults to 'content'));
&nbsp;  } // end of __construct()
}

where _$page_name_ is the name of a file in the include path which
contains the main content of the page.

Laying out pages can be

The Page object inherits special methods for handling meta and style sheet links.
Using these allows the Page object to define defaults which individual pages
may redefine as necessary.

The default meta tags are named:

* content-type - value is 'text/html; charset=utf-8'
* imagetoobar - value is 'no'
* robots - value is 'noindex, nofollow'

The default style sheets are expected to be:

* stylesheets_screen - path: /css/screen.css - media: screen
* stylesheets_print - path: /css/print.css - media: print
* stylesheets_ie - path: /css/ie.css - media: screen - (default element contains conditional)
comments
* stylesheets_handheld - path: /css/handheld.css - media: handheld 

h3. Attributes

Predefined attributes are:

* page_title - string - optional - defines the _title_ content in the _head_ element of
the page. Defaults to the name of the page content file with underscores (_) translated
into spaces, the extension stripped, and all words in Title case.
* page_heading - string - optional - defines the string used in the {page_heading} token.
Typically used in the content part of the page enclosed in the top level _h1_ element.
Defaults to _page_title_.
* required_authority - special - optional - specifies the minimum required authority
for a logged in account in order to access this page. Default is NULL - which allows
the page to be displayed without a logged in account.
** the value can be either a comma separated list in a string or an array. The values
are legal Account::authority values.

Additional attributes may be created within the page-content-file. These attributes
can be used to fill in 'template' parameters within the page - both in the
page content and all rendered parts of the page. "see page templating":#page_templating

h3. Class Methods

None

h3. Instance Methods

* get_by_name(segment-name) - returns the segment object specified. See
"PageSeg":PageSeg.html for details.
* format_meta($name, $content) - returns a properly formatted a meta element.
The _$name_ part is looked up and generates either a _name_ or _http-equivalent_
attribute, as is appropriate.
* add_meta($name, $content) - appends a meta tag to the _meta_ segment of the
_head_ segment. Overwrites any pre-existing, identically named element.
* format_style_sheet($path, $media) - returns a properly formatted style
sheet length.
* add_style_sheet($name, $path, $media, $ie = NULL) - creates a PathSegText element
named 'style_sheets_{name}' and adds a link element  to the _stylesheets_
segment of the _head_ segment. Uses _format_style_sheet()_ to create the
link element. Overwrites any pre-existing, identically named element.
_$ie_ is the Microsoft IE conditional which will be literally dropped into
the conditional comment for conditional style sheets. 
For example, if _$ie_ is 'LT 6', the link will begin with  &lt;!-- if LT 6>&gt;
* displayableP($accont_obj = FALSE) - returns TRUE if this page is displayable by
the specified account, else FALSE.
* render() - returns the page rendered properly.
* dump(msg='') - returns a diagnostic string describing the Page object.

h3. Default Page Segments as Specialized for YASiteKit.org

The page is made up of a collection of objects derived from the PageSeg class.
They may be accessed by getting the object by name and then using the
API described in the PageSeg documentation.

Defined segments are:

* html_page - PageSegList - basic html element contains three elements
** doctype - PageSegText - defaults to HTML 4.01 Transitional
** head - PageSegElt -
*** title - PageSegText - just contains the title element
*** meta - PageSegText - initialized with defaults, but can be augmented using the
_add_meta(name, content)_ method
*** stylesheets - PageSegText - initialized with defaults, but can be augmented using the
_add_style_sheet(path, media)_ method.
** body - PageSegElt - contains the body
*** header - PageSegFile - interpolates the 'header.php' file
*** content-container - PageSegElt (div) - is a wrapper for the content
**** content - PageSegElt (div) - container for content file
***** content_file - PageSegFile - interpolates file specified in the
Page constructor. Typically the value in Globals::$page_name.
***** account-nav - PageSegFile - interpolates 'account_nav.php'
**** image-menu - PageSegFile - interpolates 'product_menu.php'
*** main-nav - PageSegFile - interpolates 'main_namv.php'
*** footer - PageSegFile - interpolates 'footer.php'
*** javascript - PageSegList - a place to add javascript
**** jquery - PageSegFile - interpolates 'javascript.php'

h2(#page_templating). Page Templating

Pages are constructed by concatenating a number of chunks together - each of which
is encapsulated in a PageSeg object. The final PageSeg rendering may contain
tokens of the form {foo}, where _foo_ is expected to be an attribute name defined
for the current Page object and '{' '}' are literally left and right brace characters.
NOTE: no white space may occur within the attribute name or between the attribute
name and either brace.

After all PageSegs have been rendered and their renderings concatenated,
the Page object then performs a substitution of all sustitution _tokens_ using
the definitions of all currently defined attributes.

#end-doc
*/

// global definitions and requires

require_once('PageSeg.php');

// end global definitions and requires

// class definitions

class PageException extends Exception {}

class PageBase {
  const DOCTYPE_HTML401STRICT = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
  ';
  const DOCTYPE_HTML401TRANS ='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
';
  const DOCTYPE_XHTMLSTRICT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  ';
  const DOCTYPE_XHTMLTRANS = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  ';
  protected static $default_stylesheets = array(
      array("stylesheets_screen", "/css/screen.css", "screen", NULL),
      array("stylesheets_print", "/css/print.css", "print", NULL),
      array("stylesheets_ie", "/css/ie.css", "screen", 'LT 6'),
      array('stylesheets_handheld', '/css/handheld.css', 'handheld', NULL),
    );

  protected static $default_meta = array("content-type" => "text/html; charset=utf-8",
      "imagetoolbar" => "no",
      "robots" => "nofollow, noindex");
  protected $vars;
  protected $page_root;

  protected $head;
  protected $meta;
  protected $links;
  protected $style;
  protected $body;
  protected $javascript;
  // FIXME some More
  
  public function __construct($page_name)
  {

    
    $this->page_name = $page_name;
    $this->vars = Vars::getVars();

    // set up intellegent 404 error page
    PageSeg::$file_not_found_function = array('PageSeg', 'file_not_found');
 
    // point the global page object to 'this'
    Globals::$page_obj = $this;
  }

  protected function prepare($seg_name = 'content')
  {
    // render the content page so that it can modify the page structure prior to doing the top
    // down render which is used in the display.
    try {
      $content = $this->get_by_name($seg_name);
      $content->render();
    } catch (Exception $e) {
      $this->page_header = Globals::$site_name . " - Page Not Found";
      $this->page_title = Globals::$site_name . " - Page Not Found";
      Globals::add_message("$e");
    }
  } // end of __construct()
  
  public function __destruct()
  {
    // echo "PageBase::__destruct()\n";
    PageSeg::forget_all();
  } // end of __destroy()

  public function __get($name)
  {
    return $this->vars->$name;
  } // end of __get()
  
  public function __set($name, $value)
  {
    $this->vars->$name = $value;
  } // end of __set()
  
  // class methods
  public function file_not_found($page_seg, $fname)
  {
    if ($page_seg->name != 'content_file') {
      IncludeUtilities::report_bad_thing(Globals::$site_name . ' - internal error', "File Not Found Error on non-content segment: {$page_seg->name}");
      return '';
    } else {
      Globals::$rc->safe_get_not_found_page = $fname;
      ob_start();
      $result = include('page_not_found_page.tpl');
      $tmp = ob_get_clean();
      return $result ? $tmp : PageBase::file_not_found($page_seg, $fname);
    }
  } // end of file_not_found()
  
  // instance methods
  
  public function get_by_name($seg_name)
  {
    return PageSeg::get_by_name($seg_name);
  } // end of get_by_name()

  public function format_meta($name, $content)
  {
    switch (($name = strtolower($name))) {
      case 'accept':
      case 'accept-charset':
      case 'accept-encoding':
      case 'accept-language':
      case 'accept-ranges':
      case 'age':
      case 'allow':
      case 'authorization':
      case 'cache-control':
      case 'connecting':
      case 'content-encoding':
      case 'content-language':
      case 'content-length':
      case 'content-location':
      case 'content-md5':
      case 'content-range':
      case 'content-type':
      case 'date':
      case 'etag':
      case 'expect':
      case 'expires':
      case 'from':
      case 'host':
      case 'if-match':
      case 'if-modified-since':
      case 'if-none-match':
      case 'if-range':
      case 'if-unmodified-since':
      case 'last-modified':
      case 'location':
      case 'max-forwards':
      case 'pragma':
      case 'proxy-authenticate':
      case 'proxy-authorization':
      case 'range':
      case 'referer':
      case 'retry-after':
      case 'server':
      case 'te':
      case 'trailer':
      case 'transfer-encoding':
      case 'upgrade':
      case 'user-agent':
      case 'vary':
      case 'via':
      case 'warning':
      case 'www-authenticate':
        return "  <meta http-equiv=\"$name\" content=\"$content\">\n";
      default:
        return "  <meta name=\"$name\" content=\"$content\">\n";
    }
  } // end of format_meta()
  
  public function add_meta($name, $content)
  {
    $name = strtolower($name);
    $meta_seg_name = "meta_{$name}";
    $this->meta->del($meta_seg_name);
    $this->meta->append(new PageSegText($meta_seg_name, $this->format_meta($name, $content)));
  } // end of add_meta()
  
  public function format_style_sheet($path, $media, $ie = NULL)
  {
    if ($ie) {
      return "<!--[if {$ie}]>\n"
        . "  <link rel=\"stylesheet\" href=\"{$path}\" type=\"text/css\" media=\"{$media}\" charset=\"utf-8\">\n"
        . "<![endif]-->\n";
    } else {
      return "  <link rel=\"stylesheet\" href=\"{$path}\" type=\"text/css\" media=\"{$media}\" charset=\"utf-8\">\n";
    }
  } // end of add_style_sheet()
  
  public function add_style_sheet($name, $path, $media, $ie = NULL)
  {
    $style_sheet_seg_name = "style_sheets_{$name}";
    // if exists, redefine content, otherwise create
    $this->style_sheets->del($style_sheet_seg_name);
    $this->style_sheets->append(new PageSegText($style_sheet_seg_name, $this->format_style_sheet($path, $media, $ie)));
  } // end of add_style_sheet()

  public function render()
  {
    // insert Globals::$messages at the top of content_container
    if (Globals::$messages) {
      $content = $this->get_by_name('content');
      $content->prepend(new PageSegText('_global_messages', "<p class=\"warning\">"
        . Globals::$messages . "</p>"));
    }

    // diagnostic dump
    // $content = $this->get_by_name('content');
    // $content_container = $this->get_by_name('content-container');
    // $tmp = $content_container->dump();
    // $content->prepend(new PageSegElt('_dump_div', 'div', 'class="pre"', new PageSegText('_dump', $tmp)));

    return $this->vars->render($this->page_root);
  } // end of render()
  
  public function displayableP($act = FALSE)
  {
    $this->page_root->render();
    if (!$this->required_authority) {
      return TRUE;
    }
    return $act instanceof Account && $act->has_authority($this->required_authority);
  } // end of displayable()
  
  public function dump($msg = '')
  {
    echo "<div class=\"dump-output\">\nPage Dump: $msg\n";
    echo htmlentities($this->page_root->dump());
    // echo htmlentities($this->vars->dump());
    echo "</div>\n";
  } // end of dump()
}
?>
