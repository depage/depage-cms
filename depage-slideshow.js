/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-slideshow.js
 *
 * adds a custom slideshow 
 *
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.slideshow = function(el, options){
        /* {{{ variables */
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.slideshow", base);

        var divs = base.$el.children("div, span");
        var timer;

        base.activeSlide = 0;
        base.playing = false;
        base.num = divs.length;
        /* }}} */
        
        /* {{{ init() */
        base.init = function(){
            base.options = $.extend({},$.depage.slideshow.defaultOptions, options);
            base.options.speed = Number(base.$el.attr("data-slideshow-speed")) || base.options.speed;
            base.options.pause = Number(base.$el.attr("data-slideshow-pause")) || base.options.pause;

            if ($.browser.iphone) {
                // disable fading on the iPhone > just skip to next image
                base.options.pause = base.options.speed + base.options.pause;
                base.options.speed = 0;
            }
            
            divs.css({
                position: "absolute",
                left: 0,
                top: 0
            });
            for (var i = 1; i < divs.length; i++) {
                $(divs[i]).hide();
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
            base.$el.triggerHandler("depage.slideshow.play");

            base.playing = true;
            base.next();
        };
        /* }}} */
        /* {{{ pause() */
        base.pause = function() {
            base.$el.triggerHandler("depage.slideshow.pause");

            base.playing = false;
        };
        /* }}} */
        /* {{{ show() */
        base.show = function(n) {
            base.$el.triggerHandler("depage.slideshow.show", [n]);

            if (n == base.activeSlide) {
                // slide n is already active
                return;
            }
            base.clearQueue();
            
            // fadout active slide
            $(divs[base.activeSlide]).css({
                opacity: 1
            }).animate({
                opacity: 0
            });
            
            base.activeSlide = n;

            // fadein next slide
            $(divs[n]).css({
                opacity: 0
            }).show().animate({
                opacity: 1
            }, base.options.speed, function() {
                // hide all others completely
                divs.hide();
                $(this).show();
                
                base.waitForNext();
            });
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
        }
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
        }
        /* }}} */
        
        // Run initializer
        base.init();
    };
    
    /* {{{ defaultOptions() */
    $.depage.slideshow.defaultOptions = {
        speed: 3000,
        pause: 3000
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
