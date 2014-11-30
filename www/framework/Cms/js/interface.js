/*
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.cookie.js
 *
 *
 * @file    js/global.js
 *
 * copyright (c) 2006-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

var depageCMS = (function() {
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    var lang = $('html').attr('lang');
    var $previewFrame;
    var $flashFrame;

    // local Project instance that holds all variables and function
    var localJS = {
        // {{{ ready
        ready: function() {
            $("html").addClass("javascript");

            localJS.setup();

            // setup global events
            $(window).on("statechangecomplete", localJS.setup);

            $previewFrame = $("#previewFrame");
            $flashFrame = $("#flashFrame")[0];
        },
        // }}}
        // {{{ setup
        setup: function() {
            localJS.setupVarious();
        },
        // }}}
        // {{{ setupVarious
        setupVarious: function() {
            $("#logout").click( function() {
                localJS.logout();
            });

            // add click event for teaser
            $(".teaser").click( function() {
                document.location = $("a", this)[0].href;
            });
        },
        // }}}

        // {{{ preview
        preview: function(url) {
            if ($previewFrame.length > 0) {
                $previewFrame[0].src = unescape(url);
            } else {
                parent.depageCMS.preview(url);
            }
        },
        // }}}
        // {{{ openUpload
        openUpload: function() {
        },
        // }}}
        // {{{ setStatus
        setStatus: function(message) {
            console.log(unescape(message));
            window.status = unescape(message);
        },
        // }}}
        // {{{ msg
        msg: function(newmsg) {
            newmsg = unescape(newmsg);
            newmsg = newmsg.replace(/<br>/g, "\n");
            newmsg = newmsg.replace(/&apos;/g, "'");
            newmsg = newmsg.replace(/&quot;/g, "\"");
            newmsg = newmsg.replace(/&auml;/g, "ä");
            newmsg = newmsg.replace(/&Auml;/g, "Ä");
            newmsg = newmsg.replace(/&ouml;/g, "ö");
            newmsg = newmsg.replace(/&Ouml;/g, "Ö");
            newmsg = newmsg.replace(/&uuml;/g, "ü");
            newmsg = newmsg.replace(/&Uuml;/g, "Ü");
            newmsg = newmsg.replace(/&szlig;/g, "ß");
            alert(newmsg);
        },
        // }}}
        // {{{ flashLoaded
        flashLoaded: function() {
        },
        // }}}

        // {{{ logout
        logout: function() {
            var baseUrl = $("base").attr("href");
            var logoutUrl = baseUrl + "logout/";

            $.ajax({
                type: "GET",
                url: logoutUrl,
                cache: false,
                async: true,
                username: "logout",
                password: "logout",
                complete: function(XMLHttpRequest, textStatus) {
                    window.location = baseUrl;
                },
                error: function() {
                    window.location = baseUrl;
                }
            });
        }
        // }}}
    };

    return localJS;
})();

// {{{ registeroevents
$(document).ready(function() {
    depageCMS.ready();
});
// }}}

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
