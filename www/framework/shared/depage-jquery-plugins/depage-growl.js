/**
 * @file    depage-growl.js
 *
 * depage growl notification jquery-plugin
 *
 *
 * copyright (c) 2009-2015 Frank Hellenkamp [jonas@depage.net]
 *
 * @todo add different behaviour depending on the page being visible:
 * https://stackoverflow.com/questions/1060008/is-there-a-way-to-detect-if-a-browser-window-is-not-currently-active
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

    // {{{ htmlEncode
    var htmlEncode = function(value) {
        return $('<div/>').text(value).html();
    };
    // }}}


    var InternalNotification = function(title, options) {
        this.title = title;
        this.options = options;
    };

    // {{{ handleEvent
    InternalNotification.prototype.handleEvent = function(e) {
        switch (e.type) {
        case 'click':
            this.onClick(e);
            break;
        case 'error':
            this.onError(e);
            break;
        }
    };
    // }}}
    // {{{ onClick
    InternalNotification.prototype.onClick = function(e) {
        if (typeof this.options.onClick === "function") {
            this.options.onClick(e);
        }
    };
    // }}}
    // {{{ onError
    InternalNotification.prototype.onError = function(e) {
        if (typeof this.options.onError === "function") {
            this.options.onError(e);
        }
    };
    // }}}

    // {{{ testSystemSupport
    InternalNotification.prototype.testSystemSupport = function() {
        var isSupported = 'Notification' in window;
        var needsPermission = !(isSupported && Notification.permission === 'granted');
        var permissionLevel = (isSupported ? Notification.permission : null);

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
    // {{{ growl
    InternalNotification.prototype.growl = function() {
        if (this.options.backend != "html" && this.testSystemSupport()) {
            this.growlSystemNotification();
            if (this.options.alwaysShowFallback) {
                this.growlHtmlFallback();
            }
        } else {
            this.growlHtmlFallback();
        }
    };
    // }}}
    // {{{ growlSystemNotification
    InternalNotification.prototype.growlSystemNotification = function() {
        var n = new Notification(this.title, {
            body: this.options.message,
            icon: this.options.icon
        });

        n.addEventListener("error", this, false);
        n.addEventListener("click", this, false);
    };
    // }}}
    // {{{ growlHtmlFallback
    InternalNotification.prototype.growlHtmlFallback = function() {
        // get or add notification-area
        var $notificationArea = $("#depageGrowlArea");
        var $notification;
        var iconImg = "";
        var currentNotification = this;
        var closeNotification;
        var mouseIsOverNotification = false;

        if ($notificationArea.length === 0) {
            $notificationArea = $("<div id=\"depageGrowlArea\"></div>").appendTo("body");
            $notificationArea.css({
                position: "fixed",
                top: 0,
                right: 0,
                zIndex: 30000
            });
        }

        closeNotification = function() {
            if (mouseIsOverNotification) {
                setTimeout(closeNotification, 800);
            } else {
                $notification.clearQueue().fadeOut(800, function() {
                    $(this).remove();
                });
            }
        };

        if (this.options.icon) {
            iconImg = "<img src=\"" + this.options.icon + "\" class=\"depage-growl-icon\">";
        }
        $notification = $("<div class=\"depage-growl-message\">" + iconImg + "<h3>" + htmlEncode(this.title) + "</h3><p>" + htmlEncode(this.options.message) + "</p></div>")
            .appendTo($notificationArea)
            .hide()
            .on("mouseenter", function() {
                mouseIsOverNotification = true;
            })
            .on("mouseleave", function() {
                mouseIsOverNotification = false;
            })
            .on("click", function(e) {
                currentNotification.onClick(e);

                closeNotification();
            })
            .fadeIn(400)
            .animate({
                opacity: 1
            },{
                duration: this.options.duration,
                complete: function() {
                    closeNotification();
                }
            });
    };
    // }}}

    var base = {};

    // {{{ growl()
    base.growl = function(title, options) {
        options = $.extend({}, base.defaultOptions, options);

        var n = new InternalNotification(title, options);
        n.growl();
    };
    // }}}

    // {{{ defaultOptions
    base.defaultOptions = {
        message: "",
        icon: "",
        backend: "auto",
        duration: 3500,
        alwaysShowFallback: false,
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
