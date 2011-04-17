<?php
/*
#doc-start
h1.  ArticleGroup.php - ArticleGroups of Articles

Created by  on 2010-02-13.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Article Groups are just categories for articles. Articles don't use the more
general Category object because they predated Category. That's the only
reason.

h2. Instantiation

pre. $agrp = new ArticleGroup($dbaccess, $name);

where _$name_ is the name of an existing article group.

h2. Attributes

* name - string - name of group. Used as the key to the _articlegroup_ table.
Best practics is to follow rules for a PHP identifier
* title - string - short pity title. Limit is 255 characters. Free text
* description - text - longer description of article group.

h2. Class Methods

None

h2. Instance Methods

* select_article_group($element_name, $selected, $classes = NULL, $attributes = NULL) - 
returns an HTML _select_ element named _$element_name_. If _$selected_ occurs as
one of the ArticleGroup instance _name_ attributes, that option is selected.
_$classes_, if not NULL, are stuffed into a 'class="..."' attribute.
_$attrbutes_, if not NULL, are inserted into the _select_ element directly.
* articles() - returns array of all Article objects in this group.

#end-doc
*/

// global variables
require_once('aclass.php');

$keys_list = array('name');
$attribute_defs = array(
  array('name', 'varchar(40)', 'ArticleGroup Name'),
  array('title', 'varchar(255)', 'Group Title'),
  array('description', 'text', 'Description'),
  );

$test_article_group_values = array(
  'name' => 'Group Name',
  'title' => 'Group Title',
  'description' => 'Group Description',
  );

$article_groups_class = AClass::define_class('ArticleGroup', $keys_list, $attribute_defs,
    array(
      'name' => 'public',
      'title' => 'public',
      'description' => 'public',
      ));
// end global variables

// class definitions
class ArticleGroup extends AnInstance {
  public function __construct($dbaccess, $attribute_values = NULL)
  {
    parent::__construct('ArticleGroup', $dbaccess, $attribute_values);
  } // end of __construct()
  
  public function select_article_group($element_name, $selected, $classes = NULL, $attributes = NULL)
  {
    $article_group_list = ArticleGroup::get_objects_where(NULL, 'order by title');
    $ar = array("<select name=\"$element_name\""
      . ($classes ? " class=\"$classes\"" : '')
      . ($attributes ? " $attributes" : '')
      . ">");
    foreach ($article_group_list as $article_group) {
      $selected_attribute = $article_group->name == $selected ? 'selected' : '';
      $ar[] = "<option value=\"$article_group->name\" $selected_attribute>$article_group->title</option>";
    }
    $ar[] = "</select>";
    return implode("    " . "\n", $ar);
  } // end of select_article_group()


  public function articles()
  {
    $article_class_obj = AClass::get_class_instance('Article');
    // $article_group_key_value = $this->encode_key_values();
    $article_obj = new Article($this->dbaccess);
    return $article_obj->get_objects_where(array('article_group' => $this->name));
    // return $article_obj->get_objects_where(array('article_group' => $article_group_key_value));
  } // end of articles()
}

class ArticleGroupManager extends AManager {
  public function __construct($dbaccess)
  {
    parent::__construct($dbaccess, 'ArticleGroup', 'title');
  } // end of __construct()
}
// end class definitions
?>
