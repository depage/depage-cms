/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-slideshow.js
 *
 * adds a custom slideshow 
 *
 *
 * copyright (c) 2006-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.extend($.depage.fnMethods, {
        /* {{{ slideshow */
        /**
         * @function slideshow()
         *
         * adds a custom slideshow
         *
         * @param selector  elements to which the slideshow will apply
         */
        slideshow: function(selector) {
            return this.each( function() {
                var divs = $(this).children("div, span");
                var speed = Number($(this).attr("data-slideshow-speed")) || 3000;

                var pause = Number($(this).attr("data-slideshow-pause")) || 3000;

                if ($.browser.iphone) {
                    speed = 0;
                    pause = 5000;
                }

                divs.css({
                    position: "absolute",
                    left: 0,
                    top: 0
                });
                for (var i = 1; i < divs.length; i++) {
                    $(divs[i]).hide();
                }

                var last = divs[0];
                var fadeIn = function(n) {
                    // wait
                    $(divs[n]).animate({top: "0"}, pause, function() {
                        if (last) {
                            $(last).fadeOut(speed);
                        }
                        // fade in
                        $(this).fadeIn(speed, function() {
                            last = divs[n];
                            if (n < divs.length - 1) {
                                // fade in next image
                                fadeIn(n + 1);
                            } else {
                                fadeIn(0);
                            }
                        });
                    });
                }
                fadeIn(1);
                
            });
        }
        /* }}} */
    });
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
