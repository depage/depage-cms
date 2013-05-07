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
    }
    
    $.depage.magaziner = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.magaziner", base);
        
        var $pages = base.$el.children(".page");
        var pageWidth = base.$el.width();
        var speed = 300;
        var hammerOptions = {
            drag_lock_to_axis: true
        };
        var scrollTop;
        base.currentPage = $pages.index(".current-page");
        if (base.currentPage == -1) {
            base.currentPage = 0;
        }
        // @todo delete/do not commit
        //base.currentPage = 9;

        base.init = function(){
            base.options = $.extend({},$.depage.magaziner.defaultOptions, options);
            
            // initialize Events
            base.$el.on('touchmove', function (e) {
                e.preventDefault();
            });
            base.$el.hammer(hammerOptions).on("dragleft", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - base.currentPage) * pageWidth + e.gesture.deltaX
                    });
                });
            });
            base.$el.hammer(hammerOptions).on("dragright", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - base.currentPage) * pageWidth + e.gesture.deltaX
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
                    base.next();
                } else if (e.gesture.deltaX > pageWidth / 3 || (e.gesture.deltaX > 0 && e.gesture.velocityX > 1)) {
                    base.prev();
                } else {
                    base.show(base.currentPage);
                }
                if (e.gesture.deltaY < 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = -1;
                } else if (e.gesture.deltaY > 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = 1;
                }

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

                if (newYOffset !== 0) {
                    $("html, body").animate({
                        scrollTop: targetPos
                    }, 300 * e.gesture.velocityY);
                }
            });
            $(document).on("keypress, keyup", function(e) {
                if ($(document.activeElement).is(':input')){
                    // continue only if an input is not the focus
                    return true;
                }
                if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) {
                    return true;
                }
                switch (parseInt(e.which || e.keyCode, 10)) {
                    case 39 : // cursor right
                    case 76 : // vim nav: l
                        base.next();
                        e.preventDefault();
                        break;
                    case 37 : // cursor left
                    case 72 : // vim nav: h
                        base.prev();
                        e.preventDefault();
                        break;
                    case 74 : // vim nav: j
                        window.scrollTo(0, $(window).scrollTop() + 50);
                        e.preventDefault();
                        break;
                    case 75 : // vim nav: k
                        window.scrollTo(0, $(window).scrollTop() - 50);
                        e.preventDefault();
                        break;
                }
            });
            $(window).scroll( function() {
                $pages.not(".current-page").css({
                    top: $(window).scrollTop()
                });
            });
            $(window).resize( function() {
                pageWidth = base.$el.width();
                base.show(base.currentPage);
            });

            base.show(base.currentPage);
        };
        
        // {{{ showPagesAround(n)
        base.showPagesAround = function(n) {
            $pages.eq(n - 1).show();
            $pages.eq(n).show();
            $pages.eq(n + 1).show();
        };
        // }}}
        // {{{ show()
        base.show = function(n) {
            var resetScroll = base.currentPage != n;

            base.currentPage = n;
            base.showPagesAround(base.currentPage);

            // horizontal scrolling between pages
            $pages.each( function(i) {
                var $page = $(this);
                $page.stop().animate({
                    left: (i - base.currentPage) * pageWidth
                }, speed);
            });
            $pages.last().queue( function() {
                if (resetScroll) {
                    window.scrollTo(0, 0);

                    $pages.css({
                        top: 0
                    });
                    $pages.hide();
                    base.showPagesAround(base.currentPage);
                }
            });

            $pages.removeClass("current-page");
            $pages.eq(n).addClass("current-page");

            base.$el.triggerHandler("depage.magaziner.show", [n]);
        };
        // }}}
        // {{{ next()
        base.next = function() {
            if (base.currentPage < $pages.length - 1) {
                // scroll to next page
                base.show(base.currentPage + 1);
                base.$el.triggerHandler("depage.magaziner.next");

            } else {
                base.show(base.currentPage);
            }
        };
        // }}}
        // {{{ prev()
        base.prev = function() {
            if (base.currentPage > 0) {
                // scroll to previous page
                base.show(base.currentPage - 1);
                base.$el.triggerHandler("depage.magaziner.prev");
            } else {
                base.show(base.currentPage);
            }
        };
        // }}}
        
        // Run initializer
        setTimeout(base.init, 50);
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
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
