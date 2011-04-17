<?php
/*
#doc-start
h1.  ManageCategory.php - pity summary of what this is doing

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

Copy and hack.

The file should go in _private_data_root / pages_.

It should be named ManageYourCategory.php - that way _account_menu.php_ will
automatically find it and add it to the administrative menu.

#end-doc
*/

// global variables

Globals::$page_obj->page_header = Globals::$site_name . " - Category Management";
Globals::$page_obj->page_title = "Category Management";
// Globals::$page_obj->required_authority = 'C';
// Globals::$page_obj->required_authority = 'M';
// Globals::$page_obj->required_authority = 'W';
// Globals::$page_obj->required_authority = 'A';
// Globals::$page_obj->required_authority = 'S';
// Globals::$page_obj->required_authority = 'X';
// Globals::$page_obj->required_authority = 'M,W,A';
Globals::$page_obj->required_authority = 'S,X';

Globals::$page_obj->form_action = 'ManageCategory.php';

// ajax stuff
$javascript = <<<EOT
  <script type="text/javascript" charset="utf-8">
     // end of rewrite_table()
  ;(function($) {
    $(document).ready(function() {
      var max_sort_index = {};
      var parse_row = function (elt) {
        // alert(elt.children[1].innerHTML);
        parent_val = elt.children[1].innerHTML ? elt.children[1].innerHTML : '_root_';
        path_val = elt.children[1].innerHTML ? elt.children[1].innerHTML + '_' + elt.children[2].innerHTML : elt.children[2].innerHTML;
        return { sort_index: elt.children[0].innerHTML,
           path: path_val,
           parent: parent_val,
           name:elt.children[2].innerHTML };
      }
      // do things
      $(".category_title").append('<th colspan="2">Move Buttons</th>');
      $(".category_row")
        .map(
          // compute max hash sort_index values for each row
          function (idx, elt) {
            var row_hash = parse_row(elt);
            if (max_sort_index[row_hash.parent] === undefined) {
              max_sort_index[row_hash.parent] = row_hash.sort_index;
            } else if (max_sort_index[row_hash.parent] < row_hash.sort_index) {
              max_sort_index[row_hash.parent] = row_hash.sort_index;
            }
          }
        );
      $(".category_row")
        .each(function (idx, elt) {
          var row_hash = parse_row(elt);
          $(elt)
            .append('<td class="up_button"' + (row_hash.sort_index == 1 ? ' sort_position="top"' : '')
              + '"><button type="button" name="button_' 
              + row_hash.path + '" value="up">Up</button></td>')
            .append('<td class="down_button"'
                + (row_hash.sort_index == max_sort_index[row_hash.parent] ? 'sort_position="bottom"' : '')
                + '"><button type="button" name="button' + '_' + row_hash.path
                + '" value="down">Down</button></td>')
          });
      $(".obj-edit-form button")
        .each(
          function (idx, elt) {
            var this_elt = $(elt);
            var fix_table = function (data) {
              var row = this_elt.parent().parent();
              // str = '';
              // for (x in data) {
              //   str += x + ': ' + data[x] + '; ';
              // }
              // alert(str);
              switch (data.direction) {
                case 'up':
                  var sort_index = parseInt(row.children(':first').html());
                  if ($('.down_button', row).attr('sort_position') == 'bottom') {
                    $('.down_button', row).removeAttr('sort_position').css('visibility', 'visible');
                    $('.down_button', row.prev()).attr('sort_position', 'bottom').css('visibility', 'hidden');
                  }
                  if ($('.up_button', row.prev()).attr('sort_position') == 'top') {
                    $('.up_button', row).attr('sort_position', 'top').css('visibility', 'hidden');
                    $('.up_button', row.prev()).removeAttr('sort_position').css('visibility', 'visible');
                  }
                  row.children(':first').html(sort_index - 1 + '');
                  row.prev().children(':first').html(sort_index + '');
                  row.after(row.prev());
                  break;
                case 'down':
                  var sort_index = parseInt(row.children(':first').html());
                  if ($('.down_button', row.next()).attr('sort_position') == 'bottom') {
                    $('.down_button', row.next()).removeAttr('sort_position').css('visibility', 'visible');
                    $('.down_button', row).attr('sort_position', 'bottom').css('visibility', 'hidden');
                  }
                  if ($('.up_button', row).attr('sort_position') == 'top') {
                    $('.up_button', row.next()).attr('sort_position', 'top').css('visibility', 'hidden');
                    $('.up_button', row).removeAttr('sort_position').css('visibility', 'visible');
                  }
                  row.children(':first').html(sort_index + 1 + '');
                  row.next().children(':first').html(sort_index + '');
                  row.before(row.next());
                  break;
                default:
                  alert('Illegal data[direction] value: ' + data[direction]);
                  break;
              }
            }
            this_elt.click(function () {
              // alert(idx + ' clicked ' + this_elt.attr('name') + ' ' + this_elt.val());
              $.ajax(
              {
                url:'/ajax/json/category_ajax.php',
                  dataType:'json',
                  data:{ command:'move', button_name:this_elt.attr('name'), direction:this_elt.val()},
                  type:'POST',
                  // async: false,
                success: fix_table
                }
                );
            });
          }
         );
      $(".up_button[sort_position=top]").css('visibility', 'hidden');
      $(".down_button[sort_position=bottom]").css('visibility', 'hidden');
    })
  })(jQuery);
  </script>
EOT;

$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegText('category_javascript', $javascript));
// echo "<div class=\"dump-output\">\n";
// echo $javascript_seg->dump();
// echo "</div>\n";

require_once('Category.php');

$obj = new CategoryManager(Globals::$dbaccess, Globals::$account_obj);
$obj->render_form(Globals::$rc);

?>
