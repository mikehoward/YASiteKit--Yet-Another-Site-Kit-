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
// <?php if (!isset(Globals::$rc->raw_cookie_user_cookie_value) && Globals::$rc->raw_cookie_user_cookie_value): ?>
//     if (document.cookie) {
//       $.ajax()
//     }
// <?php endif; // raw_user_cookie_name ?>
  })
})(jQuery);
