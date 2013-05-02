/**
 * @require framework/shared/jquery-1.4.2.js
 * @require framework/shared/jquery.hammer.js
 *
 * @file    depage-magaziner.js
 *
 * adds a magazine like navigation to a website
 *
 *
 * copyright (c) 2013 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 **/
;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.magaziner = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.magaziner", base);
        
        var $pages = $(".page");
        var pageWidth = base.$el.width();
        var speed = 300;
        var hammerOptions = {
            drag_lock_to_axis: true
        };
        var scrollTop;

        base.init = function(){
            base.options = $.extend({},$.depage.magaziner.defaultOptions, options);
            
            // Put your initialization code here
            base.$el.on('touchmove', function (e) {
                e.preventDefault();
            });
            base.$el.hammer(hammerOptions).on("dragstart", function(e) {
                // save initial data
            });
            base.$el.hammer(hammerOptions).on("dragleft", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - 1) * pageWidth + e.gesture.deltaX
                    });
                });
            });
            base.$el.hammer(hammerOptions).on("dragright", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - 1) * pageWidth + e.gesture.deltaX
                    });
                });
            });
            base.$el.hammer(hammerOptions).on("dragup", function(e) {
                base.$el.css({
                    top: e.gesture.deltaY
                });
            });
            base.$el.hammer(hammerOptions).on("dragdown", function(e) {
                base.$el.css({
                    top: e.gesture.deltaY
                });
            });
            base.$el.hammer(hammerOptions).on("dragend", function(e) {
                var newXOffset = 0;
                var newYOffset = 0;

                if (e.gesture.deltaX < - pageWidth / 3 || (e.gesture.deltaX < 0 && e.gesture.velocityX > 1)) {
                    newXOffset = -1;
                } else if (e.gesture.deltaX > pageWidth / 3 || (e.gesture.deltaX > 0 && e.gesture.velocityX > 1)) {
                    newXOffset = 1;
                }
                if (e.gesture.deltaY < 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = -1;
                } else if (e.gesture.deltaY > 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = 1;
                }

                // horizontal scrolling between pages
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.animate({
                        left: (i - 1 + newXOffset) * pageWidth
                    }, speed);
                });

                // vertical scrolling on current page
                base.$el.css({
                    top: 0
                });
                var currentPos = $(window).scrollTop() - e.gesture.deltaY;
                var targetPos = $(window).scrollTop() - e.gesture.deltaY - 300 * e.gesture.velocityY * newYOffset;

                window.scrollTo(0, $(window).scrollTop() - e.gesture.deltaY);

                $pages.not(".current-page").css({
                    top: currentPos
                });

                if (newYOffset != 0) {
                    $("html, body").animate({
                        scrollTop: targetPos
                    }, 300 * e.gesture.velocityY);
                }
            });
            $(window).scroll( function() {
                $pages.not(".current-page").css({
                    top: $(window).scrollTop()
                });
            });
        };
        
        // Sample Function, Uncomment to use
        // base.functionName = function(paramaters){
        // 
        // };
        
        // Run initializer
        base.init();
    };
    
    $.depage.magaziner.defaultOptions = {
        option1: "default"
    };
    
    $.fn.depageMagaziner = function(options){
        return this.each(function(){
            (new $.depage.magaziner(this, options));
        });
    };
    
})(jQuery);
