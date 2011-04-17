<?php
/*
#doc-start
h1.  Home.php - Home Page

Created by  on 2010-09-28
 
bq. (c) Copyright 2010 Mike. All Rights Reserved. 
All Rights Reserved.
Licensed under the terms of GNU LGPL Version 3

*Home.php* is a reconfigurable Home Page for YASiteKit sites.

Edit this file to create a home page specific to your site.

The default setup displays an article named _home_ using the
_DisplayArticle.php_ page from the YASiteKit system.

This allows easier maintenance of the site because the site owner
can edit the _home_ article using ManageArticle.php.

Alternatively, one could simply replace the content of this page
by raw HTML. NOTE: if you do that, remember that the page will still
be rendered by Page.php, so the HTML will still
be included in the _content_ _div_ of the page and all the headings,
footers, etc will be rendered.  So, don't put in a complete HTML page.
Just put in the markup you need.

As a Second alternative, you might have a specialized page that you
have written and included in the site's _pages_ directory. In that
case you would probably replace the content with:

#end-doc
*/
?>
{:php-setup:}
// Home.tpl setup
Globals::$router_obj = new RequestRouter(Globals::$dbaccess, 'article');
Globals::$router_obj->map_pathinfo('home');
Globals::$page_name = Globals::$router_obj->script;
Globals::$page_ext = preg_replace('/^.*\./', '', Globals::$router_obj->script);
{:end-php-setup:}
{:include DisplayArticle.tpl:}
