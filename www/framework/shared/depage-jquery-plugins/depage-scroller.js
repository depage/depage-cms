/**
 * @require framework/shared/jquery-1.4.2.js
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
        base.dragging = false;
        base.dragHandleY = 0;

        // {{{ references to new elements
        // new layout wrapper (styled like original)
        base.$scrollOrigin = null;
        
        // new wrapper for scrolling content
        base.$scrollFrame = null;

        // content inside the scroller
        base.$scrollContent = null;

        // scroll bar
        base.$scrollBar = null

        // scroll handle
        base.$scrollHandle = null;
        // }}}
        
        // {{{ init()
        base.init = function() {
            base.options = $.extend({},$.depage.scroller.defaultOptions, options);
            
            base.$scrollContent = base.$el;

            var className = base.$scrollContent[0].className;

            // wrap into additional depage-scroller divs and move classes tp parent
            base.$scrollFrame = base.$scrollContent.wrap("<div class=\"" + className + "\"><div class=\"depage-scroller-frame\"></div></div>").parent();
            base.$scrollOrigin = base.$scrollFrame.parent();

            // move classes to parent
            base.$scrollContent.removeClass(className);
            base.$scrollContent.addClass("depage-scroller-content");

            base.$scrollOrigin.attr("style", base.$scrollContent.attr("style"));
            base.$scrollContent.removeAttr("style");

            // make origin relative or absolute
            if (base.$scrollOrigin.css("position") == "static") {
                base.$scrollOrigin.css("position", "relative");
            }

            // add scrollbar and -handle
            $("<div class=\"scroll-bar\"><div class=\"scroll-handle\"></div></div>").prependTo(base.$scrollOrigin);

            base.$scrollBar = $(".scroll-bar", base.$scrollOrigin);
            base.$scrollHandle = $(".scroll-handle", base.$scrollOrigin);

            // added drag event to handle
            base.$scrollHandle.mousedown( base.startDrag );

            // add scroll events
            base.$scrollFrame.scroll( base.onScroll );
            
            // call scroll event also when window resizes
            $(window).resize( base.onScroll );
            
            // call scroll event for initialization
            base.onScroll();
            
            // add mousewheel events
            base.$scrollFrame.mousewheel( base.onMousewheel );
        };
        // }}}
        
        // {{{ onScroll()
        base.onScroll = function() {
            if (!base.dragging) {
                ratio = base.$scrollContent.height() / base.$scrollFrame.height();
                var h = base.$scrollFrame.height() / ratio;

                if (h >= base.$scrollFrame.height()) {
                    h = base.$scrollFrame.height();

                    base.$scrollBar.hide();
                } else {
                    base.$scrollBar.show();
                }

                var t = base.$scrollFrame.scrollTop() / ratio;

                base.$scrollHandle.css({
                    height: h,
                    top: t
                });
            }
        };
        // }}}

        // {{{ onMousewheel()
        base.onMousewheel = function(e, delta) {
            if (base.$scrollFrame.height() < base.$scrollContent.height()) {
                base.$scrollFrame.scrollTop(base.$scrollFrame.scrollTop() - base.options.distance * delta);

                return false;
            }
        };
        // }}}
        
        // {{{ startDrag()
        base.startDrag = function(e) {
            base.dragHandleY = e.offsetY || e.pageY - $(e.target).offset().top;
            base.dragging = true;

            base.$scrollOrigin.addClass("dragging");

            $(window).bind("mousemove.depageScroller", base.onDrag);
            $(window).bind("mouseup.depageScroller", base.stopDrag);

            base.disableIframes();

            return false;
        };
        // }}}
        
        // {{{ onDrag()
        base.onDrag = function(e) {
            if (base.dragging) {
                var scrollY = e.pageY - base.dragHandleY;
                var offset = base.$scrollOrigin.offset();
                var min = offset.top;
                var max = offset.top + base.$scrollOrigin.height() - base.$scrollHandle.height();

                if (scrollY < min) {
                    scrollY = min;
                } else if (scrollY > max) {
                    scrollY = max;
                }

                base.$scrollHandle.offset({
                    top: scrollY
                });

                base.$scrollFrame.scrollTop((scrollY - offset.top) * ratio);
            }
        };
        // }}}
        
        // {{{ stopDrag()
        base.stopDrag = function() {
            base.$scrollOrigin.removeClass("dragging");
            $(window).unbind(".depageScroller");

            base.enableIframes();

            base.dragging = false;

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
