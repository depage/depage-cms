/**
 * @require framework/shared/jquery-1.4.4.js
 *
 * @file    depage-textbuttons.js
 *
 * replaces buttons with links to apply css-styles more easily
 *
 *
 * copyright (c) 2009-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.extend($.depage.fnMethods, {
        /* {{{ replaceTextButtons */
        /**
         * @function replaceTextButtons()
         *
         * replaces normal buttons with links
         *
         * @param selector  buttons to replace
         */
        replaceTextButtons: function(selector) {
            return this.each( function() {
                var button = this;

                // hide button
                $(button).css({
                    position: "absolute",
                    left: "-10000px"
                });

                // add link and click event to it
                $("<a href=\"#" + this.value + "\" class=\"textbutton\">" + this.value + "</a>").insertAfter(this).click( function() {
                    if ($(button).filter(":submit").length == 0) {
                        $(button).click();
                    } else {
                        $(button).parents("form").submit();
                    }

                    return false;
                });
            });
        }
        /* }}} */
    });
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
