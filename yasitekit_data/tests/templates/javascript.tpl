<?php
/*
#doc-start
h1. javascript.php - Page Structure element initializing jQuery

.bq (c) Copyright 2010 Mike Howard. All Rights Reserved.
Licenced under terms of Lesser GNU Public License, Version 3.
See "here":http://www.gnu.org/licenses/lgpl.html for details.

This file is usually encorporated into a page by defining a
PageSegFile segment which references it. See "PageSeg.php":/doc.d/system-objects/PageSeg.html
and "PageYASiteKit.php":/doc.d/system-objects/PageYASiteKit.html for more information.

It also includes jQuery code specific to YASiteKit which implement certain 'features'
based on HTML class and id attributes:

* class 'hover-reveal' - [THIS ISN'T WORKING PROPERLY YET]
used to change the CSS overlapping elements hack (using z-index
changing when the hover state changes) to 'click to reveal' 'click to close' functioning
so that they work touch devices.
(see the side-bar menu on the yasitekit.org web site). 
This works by varying the z-index value of the element
between 10 and 30, depending on the hover state. This means that the element must be
absolutely positioned in a relatively positioned container. The 'normal content'
must be in the same container and should be relatively positioned with a z-index value
between 11 and 29. The message which says something like "(mouse over to reveal)" 
should have the class 'click-message'. Then the HTML will be replaced by Click
to Open and Click to Close messsages.
* class 'click-display' - put the class 'click-display' in a container element which
has some content on the screen. Include another element with the class 'display-target'.
Clicking the outer container toggles the _display_ property of the the 'display-target'
element between _none_ and _block_.
* class 'click-offset' - works similarly to 'click-display' except that it changes
the _left_ property of 'display-target- between -100,000 px and 0 - thus moving it
on and off the screen.
* class 'filtered' - mates with the 'filter' property of AnInstance objects to implement
real-time input validation. It's meant to be used with input _text_ elements. There must
be an attribute named 'filter' which has a regular expression as a body. This code assumes
that the regular expression does NOT have beginning and ending delimiters or anchors: they
are added automatically. This code checks the content against the regular expression
when the _change_ event fires. If it matches, all is well; if it fails to match, then
the background is colored red and focus goes back to the element. [It's not fancy, but
it's also not cpu intensive and it does work]
* class 'first-focus' - the first element with class 'first-focus' needs to be a form input,
textarea, etc element. It will be given focus as soon as the page loads.
This makes forms easier to use. For AClass objects, you will need to assign this class
to the propery 'form_classes' in the object's class definition.
NOTE: It's probably not a good idea to have more than
one element with the class 'first-focus', but it won't break the page.

#doc-end
*/
?>
<script type="text/javascript" src="/javascript/jquery-1.4.2.min.js" charset="utf-8"></script>
<!-- <script type="text/javascript" src="/javascript/jquery-1.4.2.js" charset="utf-8"></script> -->
<script type="text/javascript" charset="utf-8">
;(function($) {
  $(document).ready(function() {
    // initialization code goes here
    $('.hover-reveal')
      .hover(
        function(){$('.click-message',this).html('Click to Close')}, 
        function(){$('.click-message',this).html('Click to Open');})
      .click(
        function() {
          var new_z_index = 30;
          var is_this = $(this);
          return function() {
            $(this).css('z-index', new_z_index);
            new_z_index= new_z_index == 10 ? 30 : 10;
          }
        }()
      );
    $('.click-display').click(
      function() {
        var new_display = 'block';
        return function () {
          $(this).find('.display-target').css('display', new_display);
          new_display = new_display == 'block' ? 'none' : 'block';
        }
      }());
    $('.click-offset').click(
      (function() {
        var new_offset = '-100000px';
        return function () {
          $(this).find('.display-target').css('left', new_offset);
          new_offset = new_offset == 0 ? '-100000px' : 0;
        }
      })());
    $('.filtered').each(
      function (idx, elt) {
        var this_elt = $(elt);
        var regx = new RegExp('^' + this_elt.attr('filter') + '$');
        var old_background = this_elt.css('background');
        this_elt.blur( function (evt) {
          var elt = $(evt.target);
          var val = elt.val();
          // alert('blur detected: val is ' + val);
          if (!regx.test(val)) {
            // alert("Value Error: " + val + ' does not match ' + regx);
            elt.css('background', 'red').focus();
          } else {
            elt.css('background', old_background);
          }
          // alert($(evt.target).val());
        });
      }
      );
    $('.first-focus').focus();
<?php if (!isset(Globals::$rc->raw_cookie_user_cookie_value) && Globals::$rc->raw_cookie_user_cookie_value): ?>
    if (document.cookie) {
      $.ajax()
    }
<?php endif; // raw_user_cookie_name ?>
  })
})(jQuery);
</script>
