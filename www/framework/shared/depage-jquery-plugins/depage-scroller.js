/**
 * @require framework/shared/jquery-1.4.2.js
 * @require framework/shared/jquery.mousewheel.min.js
 *
 * @file    depage-scroller.js
 *
 * custom scroller to replace default scrollbars for scrolling elements 
 *
 *
 * copyright (c) 2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.extend($.depage.fnMethods, {
        /* {{{ customScrollBar */
        /**
         * @function customScrollBar()
         *
         * adds a custom scrollbar instead of the default system scrollbars
         *
         * @param selector  elements to which the custom scrollbar will be added
         */
        customScrollBar: function(selector) {
            return this.each( function() {
                var distance = 25;
                var $scrollContent = $(this);

                var className = $scrollContent[0].className;

                // wrap into additional depage-scroller divs and move classes tp parent
                var $scrollFrame = $scrollContent.wrap("<div class=\"" + className + "\"><div class=\"depage-scroller-frame\"></div></div>").parent();
                var $scrollOrigin = $scrollFrame.parent();

                // move classes to parent
                $scrollContent.removeClass(className);
                $scrollContent.addClass("depage-scroller-content");

                $scrollOrigin.attr("style", $scrollContent.attr("style"));
                $scrollContent.removeAttr("style");

                // make origin relative or absolute
                if ($scrollOrigin.css("position") == "static") {
                    $scrollOrigin.css("position", "relative");
                }

                // add scrollbar and -handle
                $("<div class=\"scroll-bar\"><div class=\"scroll-handle\"></div></div>").prependTo($scrollOrigin);

                var $scrollBar = $(".scroll-bar", $scrollOrigin);
                var $scrollHandle = $(".scroll-handle", $scrollOrigin);

                // add scroll events
                $scrollFrame.scroll( function() {
                    var ratio = $scrollContent.height() / $scrollFrame.height();
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
                });
                // call scroll event for initialization
                $scrollFrame.scroll();
                
                // call scroll event also when window resizes
                $(window).resize( function() {
                    $scrollFrame.scroll();
                });

                // add mousewheel events
                $scrollFrame.mousewheel( function(e, delta) {
                    if ($scrollFrame.height() < $scrollContent.height()) {
                        $scrollFrame.scrollTop($scrollFrame.scrollTop() - distance * delta);

                        return false;
                    }
                });
            });
        }
        /* }}} */
    });
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
