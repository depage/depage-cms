/**
 * @file    depage-growl.js
 *
 * depage growl notification jquery-plugin
 *
 *
 * copyright (c) 2009-2015 Frank Hellenkamp [jonas@depage.net]
 *
 * @todo use html-fallback and use system notifications based on new api
 * see here: https://github.com/alexgibson/notify.js/blob/master/notify.js
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */
;(function($){
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    if(!$.depage){
        $.depage = {};
    }

    var base = {};

    // {{{ htmlEncode
    var htmlEncode = function(value) {
        return $('<div/>').text(value).html();
    };
    // }}}

    // {{{ growl()
    base.growl = function(title, options) {
        base.options = $.extend({}, base.defaultOptions, options);

        if (base.testSystemSupport()) {
            base.growlSystemNotification(title, base.options.message, base.options.icon);
        } else {
            base.growlHtmlFallback(title, base.options.message, base.options.icon);
        }
    };
    // }}}
    // {{{ testSystemSupport
    base.testSystemSupport = function() {
        var isSupported = 'Notification' in window;
        var needsPermission = !(isSupported && Notification.permission === 'granted');
        var permissionLevel = (isSupported ? Notification.permission : null);

        console.log(isSupported);
        console.log(Notification);

        if (isSupported && needsPermission) {
            Notification.requestPermission(function (perm) {
                switch (perm) {
                    case 'granted':
                        console.log("granted");
                        break;
                    case 'denied':
                        console.log("denied");
                        break;
                }
            });
        } else if (isSupported) {
            return true;
        }
        return false;
    };
    // }}}
    // {{{ growlSystemNotification
    base.growlSystemNotification = function(title, message, icon) {
        new Notification(title, {
            body: message,
            icon: icon
        });
    };
    // }}}
    // {{{ growlHtmlFallback
    base.growlHtmlFallback = function(title, message, icon) {
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

        var $notification = $("<div><h3>" + htmlEncode(title) + "</h3><p>" + htmlEncode(message) + "</p></div>")
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
    // }}}

    // {{{ defaultOptions
    base.defaultOptions = {
        message: "",
        icon: "",
        onShow: null,
        onClose: null,
        onClick: null,
        onError: null
    };
    // }}}

    // {{{ $.depage.growl()
    /**
        * @function growl()
        *
        * shows a growl-like notification
        *
        * @param title     title of the notification
        * @param message   bodytext of the notification (optional)
        * @param icons     icon of the notification (optional)
        */
    $.depage.growl = function(title, options) {
        base.growl(title, options);
    };
    // }}}
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
