<?php
/*
#doc-start
h1.  Article.php - Individual Articles

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

h2. Article Fields

* name - varchar(255) - Perma Link - name of article. Used as a permanent link in URL
* article_group - join(ArticleGroup.title) - Article Group - Article Group
* follow_index - enum(Y,N) - Follow/Index - Set Y to have internet robots index and follow links
* article_date - date - Article Date - Date article created
* title - varchar(255) - Headline - Headline, aka title
* description - varchar(255) - Description - short description
* article_body - text - Body - actual body of article


#end-doc
*/
// global variables
require_once('aclass.php');

$test_article_values = array(
  'name' => 'sample-article',
  'title' => 'Sample Article',
  'article_date' => '2010-2-10',
  'description' => 'a sample article',
  'article_body' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
  incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation
  ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in
  voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
  proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
  );
AClass::define_class('Article', 'name', array(
  array('name', 'varchar(255)', 'Perma Link'),
  array('article_group', 'link(ArticleGroup.title)', 'Article Group'),
  array('follow_index', 'enum(Y,N)', 'Follow/Index'),
  array('article_date', 'date', 'Article Date'),
  array('title', 'varchar(255)', 'Headline'),
  array('description', 'varchar(255)', 'Description'),
  array('article_body', 'text', 'Body'),
  ),
  array(
    'name' => 'public',
    'title' => array('public', 'required'),
    'article_date' => array('readonly'),
    'description' => 'public',
    'article_body' => 'public',
    ));
// end global variables

// class definitions
class Article extends AnInstance {
  public function __construct($dbaccess, $attribute_values = array())
  {
    parent::__construct('Article', $dbaccess, $attribute_values);
    if (!isset($this->article_date)) {
      $this->article_date = new DateTime('now');
    }
  } // end of __construct()
  
  public function render()
  {
    return $this->article_body;
  } // end of render()
}

class ArticleManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'Article', 'article_group,title');
  } // end of __construct()
}

/// end class definitions
?>
