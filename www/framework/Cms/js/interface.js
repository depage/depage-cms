/*
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.cookie.js
 * @require framework/shared/jquery-sortable.js
 *
 * @require framework/shared/depage-jquery-plugins/depage-details.js
 * @require framework/shared/depage-jquery-plugins/depage-uploader.js
 * @require framework/shared/depage-jquery-plugins/depage-live-filter.js
 * @require framework/shared/depage-jquery-plugins/depage-growl.js
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
    var $upload;
    var $toolbarLeft,
        $toolbarPreview,
        $toolbarRight;
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

            // @todo test/remove
            //localJS.openUpload("depage", "/");

            // setup ajax timers
            setTimeout(localJS.updateAjaxContent, 1000);
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
            $toolbarPreview = $("#toolbarmain menu.preview");
            $toolbarRight = $("#toolbarmain menu.right");

            var layouts = [
                "left-full",
                "split",
                //"tree-split",
                "right-full"
            ];

            // add layout buttons
            var $layoutButtons = $("<li class=\"pills preview-buttons layout-buttons\"></li>").prependTo($toolbarRight);
            for (var i in layouts) {
                var newLayout = layouts[i];
                var $button = $("<a class=\"toggle-button " + newLayout + "\" title=\"switch to " + newLayout + "-layout\">" + newLayout + "</a>")
                    .appendTo($layoutButtons)
                    .on("click", {layout: newLayout}, localJS.switchLayout);
            }

            // add button placeholder
            var $previewButtons = $("<li class=\"preview-buttons\"></li>").prependTo($toolbarPreview);

            // add reload button
            var $reloadButton = $("<a class=\"button\">reload</a>")
                .appendTo($previewButtons)
                .on("click", function() {
                    if ($previewFrame.length > 0) {
                        $previewFrame[0].contentWindow.location.reload();
                    }
                });

            // add zoom select
            var $zoomSelect = $("<span class=\"zoom-select\"><select><option value=\"zoom100\">100%</option><option value=\"zoom75\">75%</option><option value=\"zoom50\">50%</option></select></span>")
                .appendTo($previewButtons)
                .find("select")
                .on("change", function() {
                    this.blur();
                    $preview = $("div.preview")
                        .removeClass("zoom100 zoom75 zoom50")
                        .addClass(this.value);
                });


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
                placeholder: "Filter Projects",
                autofocus: true
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

        // {{{ updateAjaxContent
        updateAjaxContent: function() {
            var url = "overview/";
            var timeout = 5000;

            $.ajax({
                url: baseUrl + url.trim() + "?ajax=true",
                success: function(responseText, textStatus, jqXHR) {
                    if (!responseText) {
                        return;
                    }
                    var $html = $(responseText);

                    // get children with ids and replace content
                    $html.filter("[id]").each( function() {
                        var $el = $(this);
                        var id = this.id;
                        var newTimeout = $el.data("ajax-update-timeout");

                        if (newTimeout && newTimeout < timeout) {
                            timeout = newTimeout;
                        }
                        $("#" + id).empty().append($el.children());
                    });

                    // get script elements
                    $html.filter("script").each( function() {
                        $("body").append(this);
                    });
                    setTimeout(localJS.updateAjaxContent, timeout);
                }
            });
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
                $(".preview-buttons").css({visibility: "hidden"});
            } else {
                $(".preview-buttons").css({visibility: "visible"});
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
            if (parent != window) {
                parent.depageCMS.preview(url);
            } else if ($previewFrame.length == 1) {
                $previewFrame[0].src = unescape(url);

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    console.log(currentLayout);
                    $window.triggerHandler("switchLayout", "split");
                }
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
        openUpload: function(projectName, targetPath) {
            $upload = $("#upload");

            if ($upload.length === 0) {
                $upload = $("<div id=\"upload\" class=\"layout-left\"></div>").appendTo("body");
            }

            var uploadUrl = baseUrl + "project/" + projectName + "/upload" + targetPath;

            $upload.load(uploadUrl, function() {
                var $submitButton = $upload.find('input[type="submit"]');
                var $dropArea = $upload.find('p.input-file').append("<p>Drop files here</p>");
                var $progressArea = $("<div class=\"progressArea\"></div>").appendTo($upload);
                var $finishButton = $("<a class=\"button\">finished uploading/cancel</a>").appendTo($upload);

                $finishButton.on("click", function() {
                    $upload.remove();
                });

                $upload.find('input[type="file"]').depageUploader({
                    //loader_img : scriptPath + '/progress.gif'
                    $drop_area: $dropArea,
                    $progress_container: $progressArea
                }).on('start', function(e, html) {
                    $submitButton.hide();
                    //$uploadIndicator.show();
                }).on('complete', function(e, html) {
                    $submitButton.show();
                });
            });
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
