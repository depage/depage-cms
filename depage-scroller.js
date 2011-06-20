/**
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

                // wrap into additional depage-scroller div and move classes tp parent
                var $scrollFrame = $scrollContent.wrap("<div class=\"" + className + "\"></div>").parent();

                $scrollContent.removeClass(className);
                $scrollContent.addClass("depage-scroller-content");

                // add scrollbar and -handle
                $("<div class=\"scroll-bar\"><div class=\"scroll-handle\"></div></div>").prependTo($scrollFrame);

                var $scrollBar = $(".scroll-bar", $scrollFrame);
                var $scrollHandle = $(".scroll-handle", $scrollFrame);

                // add scroll events
                $scrollFrame.scroll( function() {
                    $scrollBar.css({
                        top: $scrollFrame.scrollTop()
                    });

                    var ratio = $scrollContent.height() / $scrollFrame.height();
                    $scrollHandle.css({
                        height: $scrollFrame.height() / ratio,
                        top: $scrollFrame.scrollTop() / ratio
                    });
                });
                // call scroll event for initialization
                $scrollFrame.scroll();

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
