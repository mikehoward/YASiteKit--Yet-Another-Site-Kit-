<?php
/*
#doc-start
h1.  ManageProduct.php - Product Object management

Created by  on 2010-02-10.
 
bq. Copyright Mike Howard and Clove Technologies, Inc, 2008-2010.
All Rights Reserved.

#end-doc
*/

// global variables
Globals::$page_obj->page_header = Globals::$site_name . " - Product Management";
Globals::$page_obj->page_title = "Product Management";
Globals::$page_obj->form_action = '/ManageProduct.php';
Globals::$page_obj->required_authority = 'S';

$paypal_api_javascript = "<script type=\"text/javascript\" charset=\"utf-8\">
;(function($) {
  $(document).ready(function() {
    // initialization code goes here
    $('.do-paypal-api')
    .bind('click', function(){
      var paypal_button_ajax = $(this).val();
      var id_values = ['create_buy_now', 'update_buy_now', 'delete_buy_now',
          'create_add_to_cart', 'update_add_to_cart', 'delete_add_to_cart'];
      $.ajax({
        url: 'ajax/json/product_manage_paypal_buttons.php',
        type: 'POST',
        dataType: 'json',
        data: {'paypal_button_ajax': paypal_button_ajax},
        success: function(data,textStatus){
          for (x in id_values) {
            $('#' + id_values[x]).css('display', data[id_values[x]]);
          }
          // alert('textStatus from $.ajax(): ' + textStatus);
          // for (x in data) { alert(x + ': ' + data[x]);}
        }
      });
    }); // end of $('.do-paypal-api') chain
  });
})(jQuery);
</script>\n";

$javascript_seg = Globals::$page_obj->get_by_name('javascript');
$javascript_seg->append(new PageSegFile('tinymce', 'tinymce.html'));
$javascript_seg->append(new PageSegText('paypal-api', $paypal_api_javascript));

ObjectInfo::do_require_once('Product.php');
$obj = new ProductManager(Globals::$dbaccess);
$obj->render_form(Globals::$rc);
?>
