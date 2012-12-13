/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-compare-images.js
 *
 * adds a custom element to compare images 
 *
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.compareImages = function(el, options){
        /* {{{ variables */
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.compareImages", base);

        var divs;
        var timer;

        base.activeSlide = 0;
        base.playing = false;
        base.num = 0;
        /* }}} */
        
        /* {{{ init() */
        base.init = function(){
            base.options = $.extend({},$.depage.compareImages.defaultOptions, options);

            divs = base.$el.children(base.options.elements);
            base.num = divs.length;

            if ($.browser.iphone) {
                // disable fading on the iPhone > just skip to next image
                base.options.pause = base.options.speed + base.options.pause;
                base.options.speed = 0;
            }
            
            var perc = 100 / divs.length;
            var percZoomed = 40 / (divs.length - 1);

            base.$el.height( $(divs[0]).height() );
            for (var i = 0; i < divs.length; i++) {
                $(divs[i]).css({
                    position: "absolute",
                    left: i * perc + "%",
                    top: 0
                });
            }
            base.$el.mouseover( function(e) {
                var activeDiv = $(e.target).parent()[0];

                if (activeDiv.nodeName == "DIV") {
                    var xpos = 0;

                    for (var i = 0; i < divs.length; i++) {
                        $(divs[i]).dequeue();
                        $(divs[i]).animate({
                            left: xpos + "%"
                        });

                        if (divs[i] == activeDiv) {
                            xpos += 60;
                        } else {
                            xpos += percZoomed;
                        }
                    }
                }
            });
            base.$el.mouseout( function() {
                for (var i = 0; i < divs.length; i++) {
                    $(divs[i]).dequeue();
                    $(divs[i]).animate({
                        left: i * perc + "%"
                    });
                }
            });
        };
        /* }}} */

        // Run initializer
        base.init();
    };
    
    /* {{{ defaultOptions() */
    $.depage.compareImages.defaultOptions = {
        elements: "div"
    };
    /* }}} */
    
    /* {{{ $.fn.depageCompareImages() */
    $.fn.depageCompareImages = function(options){
        return this.each(function(){
            (new $.depage.compareImages(this, options));
        });
    };
    /* }}} */
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
