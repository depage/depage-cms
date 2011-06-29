/**
 * @file    depage-growl.js
 *
 * depage growl notification jquery-plugin
 *
 *
 * copyright (c) 2009-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.extend($.depage, {
        /* {{{ growl */
        /**
         * @function growl()
         *
         * shows a growl-like notification 
         *
         * @param title     title of the notification
         * @param message   bodytext of the notification (optional)
         * @param icons     icon of the notification (optional)
         */
        growl: function (title, message, icon) {
            // get or add notification-area
            var $notificationArea = $("#depageGrowlArea");
            if ($notificationArea.length == 0) {
                $notificationArea = $("<div id=\"depageGrowlArea\"></div>").appendTo("body");
            }

            title = title || "";
            message = message || "";
            icon = icon || "";


            var $notification = $("<div><h3>" + title + "</h3><p>" + message + "</p></div>")
                .appendTo($notificationArea)
                .click( function() {
                    $(this).remove();
                })
                .hide()
                .fadeIn(400)
                .animate( { opacity: 1 }, 3500)
                .fadeOut(800, function() { 
                    $(this).remove();
                });
        }
        /* }}} */
    })
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
