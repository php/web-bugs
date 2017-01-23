$(document).ready(function() {
/*{{{ Scroll to top */
    (function() {

      var settings = {
        text: 'To Top',
        min: 200,
        inDelay: 600,
        outDelay: 400,
        containerID: 'toTop',
        containerHoverID: 'toTopHover',
        scrollSpeed: 400,
        easingType: 'linear'
      };
      
      var toTopHidden = true;
      var toTop = $('#' + settings.containerID);
      
      toTop.click(function(e) {
        e.preventDefault();
        $.scrollTo(0, settings.scrollSpeed, {easing: settings.easingType});
      });
      
      $(window).scroll(function() {
        var sd = $(this).scrollTop();
        if (sd > settings.min && toTopHidden)
        {
          toTop.fadeIn(settings.inDelay);
          toTopHidden = false;
        }
        else if(sd <= settings.min && ! toTopHidden)
        {
          toTop.fadeOut(settings.outDelay);
          toTopHidden = true;
        }
      });

      // Comments filtering
      $('.controls.comments span').click(function () {
        $('.controls.comments span.active').removeClass('active');
        $(this).addClass('active');

        type = $(this).data('type');

        if (type == 'all') {
          $('.report.comment:hidden').show('slow');
        } else {
          $('.report.comment').each(function (i) {
            e = $(this);
            e.data('type') == type ? e.show('slow') : e.hide('slow');
          });
        }
      });

    })();

});
