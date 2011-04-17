<?php
/*
#doc-start
h1.  PageYASiteKit.php - The YASiteKit Management HTML Page object.

Created by Mike Howard on 2010-07-17.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved. Licensed under the terms of the GNU Lesser
GNUL License, version 3.  See http://www.gnu.org/licenses/ for details.

bq. THIS SOFTWARE HAS NO WARRANTEE OR REPRESENTATION FOR FITNESS OF PURPOSE.
USE AT YOUR OWN RISK.

PageYASiteKit is identical to the Page object distributed in the system/objects
folder of YASiteKit. It is used by for management objects - so that they
will work in a known page structure. This allows other Page objects to be
defined on a site by site basis. See Page-simple.php in the site-framework
for an example.

#end-doc
*/

// global variables
require_once('PageBase.php');

// end global variables

// class definitions
class PageYASiteKit extends PageBase {
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
      // , new PageSegFile('product-menu', 'product_menu.php')
     );

    // this defines the secondry content menus physically after the content (for SEO), so
    //  it must be moved into position using CSS
    $content_container = new PageSegElt('content-container', 'div', $this->content, $secondary_menu);

    // manage the following two sgements using add_meta() and add_style_sheet()
    $this->meta = new PageSegList('meta');
    foreach (PageBase::$default_meta as $key => $meta_content) {
      $this->add_meta($key, $meta_content);
     // $this->meta->append(new PageSegText("meta_{$key}", $this->format_meta($key, $meta_content)));
    }
    $this->style_sheets = new PageSegList('stylesheets');
    foreach (PageBase::$default_stylesheets as $row) {
      list($name, $path, $media, $ie) = $row;
      $this->add_style_sheet($name, $path, $media, $ie);
    }

    // define structure of page
    $this->head = new PageSegElt('head', 'head', new PageSegText('title', '<title>{page_title}</title>'),
      $this->meta, $this->style_sheets);

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

    $this->page_root = new PageSegList('html_page',
      new PageSegText('doctype', PageBase::DOCTYPE_HTML401TRANS),
      new PageSegElt('html', 'html', $this->head, $this->body));

    $this->prepare();
  } // end of __construct()
}

// end class definitions

// function definitions

// end function definitions

// initial processing of POST data

// dispatch actions

?>
