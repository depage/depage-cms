/*
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.cookie.js
 * @require framework/shared/jquery-sortable.js
 *
 * @require framework/shared/depage-jquery-plugins/depage-details.js
 * @require framework/shared/depage-jquery-plugins/depage-live-filter.js
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
    var $html;
    var $window;
    var $previewFrame;
    var $flashFrame;
    var $toolbarLeft;
    var $toolbarRight;
    var currentLayout;

    // local Project instance that holds all variables and function
    var localJS = {
        // {{{ ready
        ready: function() {
            $window = $(window);

            $html = $("html");
            $html.addClass("javascript");

            localJS.setup();

            // setup global events
            $window.on("statechangecomplete", localJS.setup);
            $window.on("switchLayout", localJS.switchLayout);

            $window.triggerHandler("switchLayout", "split");

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
            localJS.setupToolbar();
            localJS.setupProjectList();
            localJS.setupPreviewLinks();
            localJS.setupSortables();
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
        // {{{ setupToolbar
        setupToolbar: function() {
            $toolbarLeft = $("#toolbarmain menu.left");
            $toolbarRight = $("#toolbarmain menu.right");

            var layouts = [
                "left-full",
                "split",
                //"tree-split",
                "right-full"
            ];

            // add zoom select
            var $zoomButtons = $("<li class=\"layout-buttons\"></li>").prependTo($toolbarRight);
            var $zoomSelect = $("<span class=\"zoom-select\"><select><option value=\"zoom100\">100%</option><option value=\"zoom75\">75%</option><option value=\"zoom50\">50%</option></select></span>")
                .appendTo($zoomButtons)
                .find("select")
                .on("change", function() {
                    this.blur();
                    $preview = $("div.preview")
                        .removeClass("zoom100 zoom75 zoom50")
                        .addClass(this.value);
                });


            // add layout buttons
            var $layoutButtons = $("<li class=\"pills layout-buttons\"></li>").prependTo($toolbarRight);
            for (var i in layouts) {
                var newLayout = layouts[i];
                var $button = $("<a class=\"toggle-button " + newLayout + "\" title=\"switch to " + newLayout + "-layout\">" + newLayout + "</a>")
                    .appendTo($layoutButtons)
                    .on("click", {layout: newLayout}, localJS.switchLayout);
            }
        },
        // }}}
        // {{{ setupProjectList
        setupProjectList: function() {
            var $projects = $(".projectlist").depageDetails();

            $projects.on("depage.detail-opened", function(e, $head, $detail) {
                var changesUrl = baseUrl + "project/" + $head.data("project") + "/details/15/?ajax=true";

                $.get(changesUrl)
                    .done(function(data) {
                        $detail.empty().html(data);

                        localJS.setupPreviewLinks();
                    });
            });

            $projects.depageLiveFilter("dt", "strong", {
                placeholder: "Filter Projects"
            });
        },
        // }}}
        // {{{ setupPreviewLinks
        setupPreviewLinks: function() {
            $("a.preview").on("click", function(e) {
                localJS.preview(this.href);

                return false;
            });
        },
        // }}}
        // {{{ setupSortables
        setupSortables: function() {
            $(".sortable-fieldsets").depageDetails({
                head: "legend",
                //detail: ".detail",
            }).sortable({
                itemSelector: "fieldset",
                nested: false,
                vertical: false,
                handle: "legend",
                pullPlaceholder: false,
                tolerance: 5,
                onDragStart: function($item, container, _super, event) {
                    _super($item, container);
                },
                onDrag: function ($item, position, _super, event) {
                },
                onDrop: function($item, container, _super) {
                    _super($item, container);
                },
                onCancel: function($item, container, _super) {
                    _super($item, container);
                },
                afterMove: function ($placeholder, container, $closestItemOrContainer) {
                }
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

        // {{{ switchLayout
        switchLayout: function(event, layout) {
            currentLayout = layout;

            if (typeof event.data != "undefined" && typeof event.data.layout != "undefined") {
                currentLayout = event.data.layout;
            }

            if ($("div.preview").length === 0) {
                currentLayout = "left-full";
                $(".layout-buttons").css({visibility: "hidden"});
            } else {
                $(".layout-buttons").css({visibility: "visible"});
            }
            $html
                .removeClass("layout-left-full layout-right-full layout-tree-split layout-split")
                .addClass("layout-" + currentLayout);

            var $buttons = $toolbarRight.find(".layout-buttons a")
                .removeClass("active")
                .filter("." + currentLayout).addClass("active");
        },
        // }}}
        // {{{ preview
        preview: function(url) {
            if ($previewFrame.length > 0) {
                $previewFrame[0].src = unescape(url);

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    console.log(currentLayout);
                    $window.triggerHandler("switchLayout", "split");
                }
            } else if (parent != window) {
                parent.depageCMS.preview(url);
            } else {
                // add preview frame
                var projectName = url.match(/project\/(.*)\/preview/)[1];

                $.get(baseUrl + "project/" + projectName + "/edit/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.preview")
                        .appendTo("body");
                    var $header = $result.find("header.info");

                    $previewFrame = $("#previewFrame");
                    $previewFrame[0].src = unescape(url);

                    $window.triggerHandler("switchLayout", "split");
                });
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
