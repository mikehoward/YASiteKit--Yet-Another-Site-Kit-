<?php
/*
#doc-start
h1.  page_template.php - SUMMARY

Created by  on DATE
 
bq. Copyright NAME, YEAR
All Rights Reserved.
Licensed under the terms of GNU LGPL Version 3

#end-doc
*/

// global variables
require_once('OBJECT.php');
Globals::$page_obj->page_header = Globals::$site_name . " - Page Header";
Globals::$page_obj->page_title = "Page Title";
Globals::$page_obj->form_action = "Login.php";
Globals::$page_obj->required_authority = FALSE;
Globals::$page_obj->required_authority = 'ANY';
// Globals::$page_obj->required_authority = 'X';
// Globals::$page_obj->required_authority = 'S';
// Globals::$page_obj->required_authority = 'A';
// Globals::$page_obj->required_authority = 'M';
// Globals::$page_obj->required_authority = 'W';
// Globals::$page_obj->required_authority = 'C';
// Globals::$page_obj->required_authority = 'C,A,M,W,S,X';

// add jQuery code
// $my_javascript_text =<<<ENDHEREDOC
// <script type="text/javascript" charset="utf-8">
//   ;(function($) {
//     $(document).ready(function() {
//       // initialization code goes here
//       // insert your code
//   })})(jQuery);
// </script>
// ENDHEREDOC;
// $javascript_seg = Globals::$page_obj->get_by_name('javascript');
// $javascript_seg->append(new PageSegText('UNIQUE_PAGESEG_NAME', $my_javascript_text));

// end global variables

// class definitions
class Foo {
  public function __construct($args)
  {
    # code...
  } // end of __construct()
}
// end class definitions

// function definitions
function dynamic_display()
{
  // $foo = Globals::$rc->safe_post_VARIABLE;
  // Globals::$dbaccess->FUNCTION(ARGS);
  // Globals::add_message(SOME MESSAGE);
  // Globals::session_obj->add_message(MESSAGE FOR DIVERTED-TO PAGE)
} // end of dynamic_display()

// wrap HTML in a function to control when and if it's displayed
function a_form()
{
  ob_start();
?>
  <form action="page_template.php" method="post" accept-charset="utf-8">
    <input type="text" name="some_name" value="initial value">

    <p><input type="submit" name="submit" value="Silently Submit"></p>
    <p><input type="submit" name="submit" value="Submit"></p>
  </form>
<?php
  return ob_get_clean();
}

// wrap action in function
function do_something()
{
  ob_start();
  require_once('SomeObject.php');
  // do something which effects presistent data
  
  return ob_get_clean();
} // end of do_something()

// end function definitions

// initial processing of POST data

// dispatch actions
switch (Globals::$rc->safe_post_submit) {
  case 'Silently Submit':
    do_something();
    echo dynamic_display();
    break;
  case 'Submit':
    // display do_something() output
    echo do_something();
    break;
  default:
    echo a_form();
    break;
}

?>
