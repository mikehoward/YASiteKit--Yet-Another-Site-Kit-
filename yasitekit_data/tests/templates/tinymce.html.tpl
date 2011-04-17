<!-- (c) Copyright 2010 Mike Howard. All Rights Reserved.  -->
<script type="text/javascript" src="/javascript/tinymce/jscripts/tiny_mce/jquery.tinymce.js" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
<?php
  $ar = array();
  foreach (scandir(Globals::$document_root . DIRECTORY_SEPARATOR . 'img') as $fname) {
    if (preg_match("/.*\.(jpg|png|gif|jpeg)/", $fname)) {
      $ar[] = "[\"$fname\", \"/img/$fname\"]";
    }
  }
  echo "var tinyMCEProductList = new Array(" . implode(',', $ar) . ");\n";
?>
</script>
<script type="text/javascript" charset="utf-8">
;(function($) {
  $(document).ready(function() {
    // alert(tinyMCEProductList[0]);
    $('.rte')
    .tinymce({
        // Location of TinyMCE script
        script_url : '/javascript/tinymce/jscripts/tiny_mce/tiny_mce_src.js',

        /*
        theme: "simple",
        /* */

        /* */ 
        // General options
        theme : "advanced",
        relative_urls: false,   // this causes url's to be absolute. default or 'true' strips leading '/'
        
        // Theme options
        // these are the ones blogspot.com uses
        /* */
        plugins : "save,advlink,advimage,imgupload,iespell,inlinepopups,insertdatetime,preview,print,contextmenu,paste,fullscreen,visualchars,nonbreaking,xhtmlxtras,autosave",
        theme_advanced_buttons1 : "fontselect,fontsizeselect,bold,italic,forecolor,link,unlink,anchor,image,imgupload,justifyleft,justifycenter,justifyright,justifyfull,numlist,bullist,blockquote,iespell",
        theme_advanced_buttons2 : "undo,removeformat,code,preview,|,cut,copy,paste,pasttext,pasteword,|,print",
        theme_advanced_buttons3 : "",
        theme_advanced_buttons4 : "",
        // Default Advanced Buttons and stuff
        /*
        plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
        theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
        theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
        /* */
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "",
        /* theme_advanced_statusbar_location : "bottom", */
        theme_advanced_resizing : true,

        // Example content CSS (should be your site CSS)
        // content_css : "css/content.css",
        content_css : "css/screen.css",

        // Drop lists for link/image/media/template dialogs
        // template_external_list_url : "lists/template_list.js",
        // external_link_list_url : "lists/link_list.js",
        external_image_list_url: '/ajax/text/tinymce-available-images.php',
        // external_product_list_url : "lists/product_list.js",
        // media_external_list_url : "lists/media_list.js",
        /* */

        // Replace values for the template plugin
        template_replace_values : {
            username : "<?php echo Globals::$account_obj instanceof Account ? Globals::$account_obj->name : ''; ?>",
            staffid : ''
        }
        /* */
    });
    }
  );
})(jQuery);
</script>
