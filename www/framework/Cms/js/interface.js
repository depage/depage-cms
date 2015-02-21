/*
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.cookie.js
 * @require framework/shared/depage-jquery-plugins/depage-details.js
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
    var baseUrl = $("base").attr("href");
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

            // setup ajax timers
            setTimeout(localJS.updateTasks, 1000);
            setTimeout(localJS.updateUsers, 8000);
        },
        // }}}
        // {{{ setup
        setup: function() {
            localJS.setupVarious();
            localJS.setupProjectList();
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
        // {{{ setupProjectList
        setupProjectList: function() {
            var $projects = $(".projectlist").depageDetails();

            $projects.on("depage.detail-opened", function(e, $head, $detail) {
                console.log("open", $head.data("project"), $detail);
            });
        },
        // }}}
        // {{{ updateUsers
        updateUsers: function() {
            localJS.updateBox("#box-users", localJS.updateUsers);
        },
        // }}}
        // {{{ updateTasks
        updateTasks: function() {
            localJS.updateBox("#box-tasks", localJS.updateTasks);
        },
        // }}}
        // {{{ updateBox
        updateBox: function(id, successFunction) {
            var $box = $(id);
            var url;

            if ($box.length > 0) {
                url = $box.attr("data-ajax-update-url");
                var taskUrl = baseUrl + url.trim() + "?ajax=true " + id + " .content";
                var timeout;

                $box.load(taskUrl, function(responseText, textStatus, jqXHR) {
                    var matches = /( data-ajax-update-timeout="(\d+)")/.exec(responseText);
                    if (matches !== null) {
                        timeout = parseInt(matches[2], 10);
                    } else {
                        timeout = 5000;
                    }
                    if (typeof successFunction === "function") {
                        setTimeout(successFunction, timeout);
                    }
                });
            }
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
