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

var depageCms = (function() {
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    var lang = $('html').attr('lang');

    // local Project instance that holds all variables and function
    var localJS = {
        // {{{ ready
        ready: function() {
            $("html").addClass("javascript");

            localJS.setup();

            // setup global events
            $(window).on("statechangecomplete", localJS.setup);
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
        // {{{ logout
        logout: function() {
            var baseUrl = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "");
            var logoutUrl = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "logout/";

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
    depageCms.ready();
});
// }}}
    
// vim:set ft=javascript sw=4 sts=4 fdm=marker : 
