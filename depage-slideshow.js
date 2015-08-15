/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-slideshow.js
 *
 * adds a custom slideshow
 *
 *
 * copyright (c) 2006-2013 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 **/

// {{{ documentation
/**
 * @mainpage
 *
 * @intro
 * @image html icon_depage-forms.png
 * @htmlinclude main-intro.html
 * @endintro
 *
 * @section Usage
 *
 * depage-jquery-slideshow
 *
 * @endsection
 *
 * @subpage developer
 *
 * @htmlinclude main-extended.html
 **/

/**
 * @page usage Usage
 *
 **/
// }}}

;(function($){
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    if(!$.depage){
        $.depage = {};
    }

    $.depage.slideshow = function(el, options){
        /* {{{ variables */
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        if (base.$el.data("depage.slideshow") !== undefined) {
            // test if this is already a slideshow object
            // @todo remove and re-add slideshow when called with different options
            return;
        }

        // Add a reverse reference to the DOM object
        base.$el.data("depage.slideshow", base);

        var divs;
        var timer;

        base.activeSlide = 0;
        base.playing = false;
        base.num = 0;

        // {{{ whichTransitionEvent - test for css transition ability
        var whichTransitionEvent = (function (){
            var t;
            var el = document.createElement('fakeelement');
            var transitions = {
                'transition'       :'transitionEnd transitionend',
                'OTransition'      :'oTransitionEnd',
                'MSTransition'     :'msTransitionEnd',
                'MozTransition'    :'transitionend',
                'WebkitTransition' :'webkitTransitionEnd'
            };

            for(t in transitions){
                if( el.style[t] !== undefined ){
                    return transitions[t];
                }
            }
            return false;
        }());
        // }}}
        /* }}} */

        /* {{{ init() */
        base.init = function(){
            base.options = $.extend({},$.depage.slideshow.defaultOptions, options);
            base.options.speed = Number(base.$el.attr("data-slideshow-speed")) || base.options.speed;
            base.options.pause = Number(base.$el.attr("data-slideshow-pause")) || base.options.pause;
            base.options.startPos = Number(base.options.startPos) || 0;

            divs = base.$el.children(base.options.elements);
            base.num = divs.length;

            if ($.browser !== undefined && $.browser.iphone) {
                // disable fading on the iPhone > just skip to next image
                base.options.pause = base.options.speed + base.options.pause;
                base.options.speed = 0;
            }

            // make parent "not static"
            if (base.$el.css("position") == "static") {
                base.$el.css({
                    position: "relative"
                });
            }

            var wasAbsolute = divs.eq(0).css("position") == "absolute";

            // make children "absolute"
            divs.css({
                position: "absolute",
                left: 0,
                top: 0
            });
            // make first child "static" to have an automatic height
            if (!wasAbsolute) {
                divs.eq(0).css({
                    position: "static"
                });
            }
            for (var i = 0; i < divs.length; i++) {
                if (i != base.options.startPos) {
                    $(divs[i]).css({
                        visibility: "hidden",
                        opacity: 0
                    });
                }
            }

            if (divs.length > 1) {
                base.activeSlide = base.options.startPos;
                base.playing = true;
                base.waitForNext();

                setTimeout(function() {
                    base.$el.triggerHandler("depage.slideshow.show", [base.options.startPos, divs.length]);
                }, 10);
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

            if (base.options.pause != -1) {
                timer = setTimeout( function() {
                    if (base.playing) {
                        base.next();
                    }
                }, base.options.pause);
            }
        };
        /* }}} */
        /* {{{ play() */
        base.play = function() {
            base.$el.triggerHandler("depage.slideshow.play");

            base.playing = true;
            base.next();
        };
        /* }}} */
        /* {{{ resume() */
        base.resume = function() {
            base.playing = true;
            base.waitForNext();
        };
        /* }}} */
        /* {{{ pause() */
        base.pause = function() {
            base.$el.triggerHandler("depage.slideshow.pause");

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
        base.show = function(n, waitForImagesToLoad, customSpeed) {
            waitForImagesToLoad = (typeof force === "undefined") ? !base.options.waitForImagesToLoad : waitForImagesToLoad;
            if (waitForImagesToLoad && !base.imagesReadyFor(n)) {
                setTimeout( function() { base.show(n); }, 100);
                return false;
            }
            customSpeed = (typeof customSpeed === "undefined") ? base.options.speed : customSpeed;

            base.$el.triggerHandler("depage.slideshow.show", [n, divs.length]);

            if (n == base.activeSlide) {
                // slide n is already active
                return;
            }
            base.clearQueue();

            divs.each(function(i) {
                if (i != n && i != base.activeSlide) {
                    $(this).css({visibility: "hidden"});
                }
            });
            if (whichTransitionEvent) {
                divs.css({
                    transition: "opacity " + customSpeed + "ms linear"
                });
            }

            // fadout active slide
            $(divs[base.activeSlide]).each( function() {
                var $div = $(this);

                if (whichTransitionEvent) {
                    // css3 animation
                    $div.css({
                        opacity: 1
                    }).one(whichTransitionEvent, function(e) {
                        $div.css({visibility: "hidden"});
                    }).css({
                        opacity: 0
                    });
                } else {
                    // javascript animation
                    $div.css({
                        opacity: 1
                    }).animate({
                        opacity: 0
                    }, customSpeed, function() {
                        $div.css({visibility: "hidden"});
                    });
                }
            });

            base.activeSlide = n;

            // fadein next slide
            if (whichTransitionEvent) {
                // css3 animation
                $(divs[n]).css({
                    visibility: "visible",
                    opacity: 0
                }).one(whichTransitionEvent, function(e) {
                    base.waitForNext();
                }).css({
                    opacity: 1
                });
            } else {
                // javascript animation
                $(divs[n]).css({
                    visibility: "visible",
                    opacity: 0
                }).animate({
                    opacity: 1
                }, base.options.speed, function() {
                    base.waitForNext();
                });
            }
        };
        /* }}} */
        /* {{{ next() */
        base.next = function() {
            if (base.activeSlide < divs.length - 1) {
                // fade in next image
                base.show(base.activeSlide + 1);
            } else {
                // fade in first image
                base.show(0);
            }
        };
        /* }}} */
        /* {{{ prev() */
        base.prev = function() {
            if (base.activeSlide > 0) {
                // fade in previous image
                base.show(base.activeSlide - 1);
            } else {
                // fade in first image
                base.show(divs.length - 1);
            }
        };
        /* }}} */

        // Run initializer
        base.init();
    };

    /* {{{ defaultOptions() */
    $.depage.slideshow.defaultOptions = {
        elements: "div, span, img",
        speed: 3000,
        pause: 3000,
        startPos: 0,
        waitForImagesToLoad: true
    };
    /* }}} */

    /* {{{ $.fn.depageSlideshow() */
    $.fn.depageSlideshow = function(options){
        return this.each(function(){
            (new $.depage.slideshow(this, options));
        });
    };
    /* }}} */
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
