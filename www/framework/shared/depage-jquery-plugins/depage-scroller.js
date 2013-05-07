/**
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.mousewheel.min.js
 *
 * @file    depage-scroller.js
 *
 * custom scroller to replace default scrollbars for scrolling elements 
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.scroller = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.scroller", base);

        // ratio between frame and content
        var ratio;

        // dragging related variables
        var dragging = false;
        var dragHandleY = 0;

        // {{{ references to new elements
        // new layout wrapper (styled like original)
        var $scrollOrigin = null;
        
        // new wrapper for scrolling content
        var $scrollFrame = null;

        // content inside the scroller
        var $scrollContent = null;

        // scroll bar
        var $scrollBar = null

        // scroll handle
        var $scrollHandle = null;
        // }}}
        
        // {{{ init()
        base.init = function() {
            base.options = $.extend({},$.depage.scroller.defaultOptions, options);
            
            $scrollContent = base.$el;

            var className = $scrollContent[0].className;
            var id = $scrollContent[0].id;

            // wrap into additional depage-scroller divs and move classes tp parent
            $scrollFrame = $scrollContent.wrap("<div class=\"" + className + "\" id=\"" + id + "\"><div class=\"depage-scroller-frame\"></div></div>").parent();
            $scrollOrigin = $scrollFrame.parent();

            // move classes to parent
            $scrollContent.removeAttr(className);
            $scrollContent.addClass("depage-scroller-content");
            $scrollContent.removeAttr("id");

            $scrollOrigin.attr("style", $scrollContent.attr("style"));
            $scrollContent.removeAttr("style");

            // make origin relative or absolute
            if ($scrollOrigin.css("position") == "static") {
                $scrollOrigin.css("position", "relative");
            }

            // add scrollbar and -handle
            $("<div class=\"scroll-bar\"><div class=\"scroll-handle\"></div></div>").prependTo($scrollOrigin);

            $scrollBar = $(".scroll-bar", $scrollOrigin);
            $scrollHandle = $(".scroll-handle", $scrollOrigin);

            // added drag event to handle
            $scrollHandle.mousedown( base.startDrag );

            // add scroll events
            $scrollFrame.scroll( base.onScroll );
            
            // call scroll event also when window resizes
            $(window).resize( base.onScroll );
            
            // call scroll event for initialization
            base.onScroll();
            
            // add mousewheel events
            $scrollFrame.mousewheel( base.onMousewheel );
        };
        // }}}
        
        // {{{ onScroll()
        base.onScroll = function() {
            if (!dragging) {
                ratio = $scrollContent.height() / $scrollFrame.height();
                var h = $scrollFrame.height() / ratio;

                if (h >= $scrollFrame.height()) {
                    h = $scrollFrame.height();

                    $scrollBar.hide();
                } else {
                    $scrollBar.show();
                }

                var t = $scrollFrame.scrollTop() / ratio;

                $scrollHandle.css({
                    height: h,
                    top: t
                });
            }
        };
        // }}}

        // {{{ onMousewheel()
        base.onMousewheel = function(e, delta) {
            if ($scrollFrame.height() < $scrollContent.height()) {
                $scrollFrame.scrollTop($scrollFrame.scrollTop() - base.options.distance * delta);

                return false;
            }
        };
        // }}}
        
        // {{{ startDrag()
        base.startDrag = function(e) {
            dragHandleY = e.offsetY || e.pageY - $(e.target).offset().top;
            dragging = true;

            $scrollOrigin.addClass("dragging");

            $(window).bind("mousemove.depageScroller", base.onDrag);
            $(window).bind("mouseup.depageScroller", base.stopDrag);

            base.disableIframes();

            return false;
        };
        // }}}
        
        // {{{ onDrag()
        base.onDrag = function(e) {
            if (dragging) {
                var scrollY = e.pageY - dragHandleY;
                var offset = $scrollOrigin.offset();
                var min = offset.top;
                var max = offset.top + $scrollOrigin.height() - $scrollHandle.height();

                if (scrollY < min) {
                    scrollY = min;
                } else if (scrollY > max) {
                    scrollY = max;
                }

                $scrollHandle.offset({
                    top: scrollY
                });

                $scrollFrame.scrollTop((scrollY - offset.top) * ratio);
            }
        };
        // }}}
        
        // {{{ stopDrag()
        base.stopDrag = function() {
            $scrollOrigin.removeClass("dragging");
            $(window).unbind(".depageScroller");

            base.enableIframes();

            dragging = false;

            return false;
        };
        // }}}
        
        // {{{ disableIframes()
        base.disableIframes = function() {
            $("iframe").each( function() {
                var $iframe = $(this);
                var offset = $iframe.offset();
                var $disabler = $("<div class=\"disable-iframe-events\"></div>").appendTo(document.body);

                $disabler.css({
                    position: "absolute",
                    zIndex: 10000,
                    top: offset.top,
                    left: offset.left,
                    width: $iframe.width(),
                    height: $iframe.height()
                });
            });
        } 
        // }}}
        
        // {{{ enableIframes()
        base.enableIframes = function() {
            $(".disable-iframe-events").remove();
        } 
        // }}}
        
        // Run initializer
        base.init();
    };
    
    $.depage.scroller.defaultOptions = {
        distance: 25 // number of pixels to scroll on mouse wheel events
    };
    
    $.fn.depageScroller = function(options){
        return this.each(function(){
            (new $.depage.scroller(this, options));
        });
    };
    
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
