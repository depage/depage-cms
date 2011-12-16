/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-details.js
 *
 * adds details handler to definition-lists
 *
 *
 * copyright (c) 2011 Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.extend($.depage.fnMethods, {
        /* {{{ details */
        /**
         * @function details()
         *
         * adds detail handler to definition lists
         *
         * @param selector  elements to which the handler will be attached
         */
        details: function(selector) {
            return this.each( function() {
                $details = this;

                $("dt", this).each(function() {
                    var $head = $(this).css({
                        cursor: "pointer"
                    });
                    var $detail = $head.nextAll("dd:first");

                    $(this).prepend("<span class=\"opener\"></span>");

                    $detail.hide();
                    $head.click(function() {
                        if (!$head.hasClass("active")) {
                            $("dt", $details).removeClass("active");
                            $head.addClass("active")
                            $head.siblings("dd").slideUp();
                            $detail.addClass("active")
                            $detail.slideDown();
                        } else {
                            $detail.removeClass("active")
                            $detail.slideUp("normal", function() {
                                $head.removeClass("active");
                            });
                        }
                    });
                });
            });
        }
        /* }}} */
    });
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
