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
                var divs = $("div, span", this);
                var speed = Number($(this).attr("data-slideshow-speed"));
                if (!speed) {
                    var speed = 3000;
                }

                var pause = Number($(this).attr("data-slideshow-pause"));
                if (!pause) {
                    var pause = 3000;
                }
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

                var fadeIn = function(n) {
                    // wait
                    $(divs[n]).animate({top: "0"}, pause, function() {
                        // fade in
                        $(this).fadeIn(speed, function() {
                            if (n < divs.length - 1) {
                                // fade in next image
                                fadeIn(n + 1);
                            } else {
                                // hide all images, fade out last
                                for (var i = 1; i < divs.length - 1; i++) {
                                    $(divs[i]).hide();
                                }
                                $(divs[n]).animate({top: 0}, pause, function() {
                                    $(divs[n]).fadeOut(speed, function() {
                                        fadeIn(1);
                                    });
                                });
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
