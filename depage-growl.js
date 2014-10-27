/**
 * @file    depage-growl.js
 *
 * depage growl notification jquery-plugin
 *
 *
 * copyright (c) 2009-2014 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @todo use html-fallback and use system notifications based on new api
 * see here: https://github.com/alexgibson/notify.js/blob/master/notify.js
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    if(!$.depage){
        $.depage = {};
    }

    var base = {};

    base.growlFallback = function(title, message, icon) {
        // get or add notification-area
        var $notificationArea = $("#depageGrowlArea");

        if ($notificationArea.length === 0) {
            $notificationArea = $("<div id=\"depageGrowlArea\"></div>").appendTo("body");
            $notificationArea.css({
                position: "absolute",
                top: 0,
                right: 0
            });
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
    };

    // {{{ growl()
    /**
        * @function growl()
        *
        * shows a growl-like notification
        *
        * @param title     title of the notification
        * @param message   bodytext of the notification (optional)
        * @param icons     icon of the notification (optional)
        */
    $.depage.growl = function(title, message, icon) {
        console.log(title, message, icon);
        base.growlFallback(title, message, icon);
    };
    // }}}
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
