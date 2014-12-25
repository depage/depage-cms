/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-carrousel.js
 *
 * adds a custom carrousel
 *
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    }

    $.depage.carrousel = function(el, options){
        /* {{{ variables */
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.carrousel", base);

        var divs = base.$el.children("div, span");
        var timer;
        var duplicate;

        base.activeSlide = 0;
        base.playing = false;
        base.num = divs.length;
        /* }}} */

        /* {{{ init() */
        base.init = function(){
            base.options = $.extend({},$.depage.carrousel.defaultOptions, options);
            base.options.speed = Number(base.$el.attr("data-carrousel-speed")) || base.options.speed;
            base.options.pause = Number(base.$el.attr("data-carrousel-pause")) || base.options.pause;

            if ($.browser.iphone) {
                // disable fading on the iPhone > just skip to next image
                base.options.pause = base.options.speed + base.options.pause;
                base.options.speed = 0;
            }

            base.width = base.$el.width();
            base.height = base.$el.height();

            // duplicate first div
            duplicate = divs.eq(0).clone().appendTo(base.$el);

            base.$el.wrapInner("<div class=\"passepartout\"></div>");

            base.$el.css({
                overflow: "hidden"
            });
            base.$passepartout = base.$el.find(".passepartout").css({
                position: "absolute"
            });

            if (base.options.direction == "vertical") {
                // position divs under each other
                divs.each( function(i) {
                    $(this).css({
                        position: "absolute",
                        top: i * base.height,
                        left: 0
                    });
                });
                duplicate.css({
                    position: "absolute",
                    top: divs.length * base.height,
                    left: 0
                });
            } else {
                // position divs next to each other
                divs.each( function(i) {
                    $(this).css({
                        position: "absolute",
                        left: i * base.width,
                        top: 0
                    });
                });
                duplicate.css({
                    position: "absolute",
                    left: divs.length * base.width,
                    top: 0
                });
            }

            if (divs.length > 1) {
                base.playing = true;
                base.waitForNext();
            }
        };
        /* }}} */

        /* {{{ clearQueue() */
        base.clearQueue = function() {
            clearTimeout(timer);
            divs.stop(true);
        };
        /* }}} */
        /* {{{ waitForNext() */
        base.waitForNext = function(){
            base.clearQueue();

            timer = setTimeout( function() {
                if (base.playing) {
                    base.next();
                }
            }, base.options.pause);
        };
        /* }}} */
        /* {{{ play() */
        base.play = function() {
            base.$el.triggerHandler("depage.carrousel.play");

            base.playing = true;
            base.next();
        };
        /* }}} */
        /* {{{ pause() */
        base.pause = function() {
            base.$el.triggerHandler("depage.carrousel.pause");

            base.playing = false;
        };
        /* }}} */
        /* {{{ imagesReadyFor() */
        base.imagesReadyFor = function(n) {
            var $images = $("img", divs[n]);
            var allLoaded = true;
            $images.each(function() {
                allLoaded = allLoaded && this.complete;
            });

            return allLoaded;
        };
        /* }}} */
        /* {{{ show() */
        base.show = function(n, waitForImagesToLoad) {
            waitForImagesToLoad = (typeof force === "undefined") ? !base.options.waitForImagesToLoad : waitForImagesToLoad;
            if (waitForImagesToLoad && !base.imagesReadyFor(n)) {
                setTimeout( function() { base.show(n); }, 100);
                return false;
            }

            base.$el.triggerHandler("depage.carrousel.show", [n]);

            if (n == base.activeSlide) {
                // slide n is already active
                return;
            }
            base.clearQueue();

            if (base.options.direction == "vertical") {
                var newMarginTop = -n * base.height;
                if (n === 0 && base.activeSlide != 1 ) {
                    // animate to duplicate
                    newMarginTop = -divs.length * base.height;
                } else if (n === 1 && base.activeSlide === 0) {
                    // reset to first position
                    base.$passepartout.css({
                        marginTop: 0
                    });
                } else if (base.activeSlide === 0 && n === divs.length - 1) {
                    // reset to last position
                    base.$passepartout.css({
                        marginTop: -divs.length * base.height
                    });
                }

                // scroll to active slide
                base.$passepartout.animate({
                    marginTop: newMarginTop
                }, base.options.speed, function() {
                    base.waitForNext();
                });
            } else {
                var newMarginLeft = -n * base.width;
                if (n === 0 && base.activeSlide !== 1 ) {
                    // animate to duplicate
                    newMarginLeft = -divs.length * base.width;
                } else if (n === 1 && base.activeSlide === 0) {
                    // reset to first position
                    base.$passepartout.css({
                        marginLeft: 0
                    });
                } else if (base.activeSlide === 0 && n === divs.length - 1) {
                    // reset to last position
                    base.$passepartout.css({
                        marginLeft: -divs.length * base.width
                    });
                }

                // scroll to active slide
                base.$passepartout.animate({
                    marginLeft: newMarginLeft
                }, base.options.speed, function() {
                    base.waitForNext();
                });
            }

            base.activeSlide = n;
        };
        /* }}} */
        /* {{{ next() */
        base.next = function() {
            if (base.activeSlide < divs.length - 1) {
                // show next slide
                base.show(base.activeSlide + 1);
            } else {
                // show first slide
                base.show(0);
            }
        };
        /* }}} */
        /* {{{ prev() */
        base.prev = function() {
            if (base.activeSlide > 0) {
                // show in previous slide
                base.show(base.activeSlide - 1);
            } else {
                // show last slide
                base.show(divs.length - 1);
            }
        };
        /* }}} */

        // Run initializer
        base.init();
    };

    /* {{{ defaultOptions() */
    $.depage.carrousel.defaultOptions = {
        speed: 3000,
        pause: 3000,
        waitForImagesToLoad: true,
        direction: "horizontal"
    };
    /* }}} */

    /* {{{ $.fn.depageCarrousel() */
    $.fn.depageCarrousel = function(options){
        return this.each(function(){
            (new $.depage.carrousel(this, options));
        });
    };
    /* }}} */
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
