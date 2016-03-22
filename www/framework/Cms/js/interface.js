/*
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/jquery.cookie.js
 * @require framework/shared/jquery-sortable.js
 *
 * @require framework/shared/depage-jquery-plugins/depage-details.js
 * @require framework/shared/depage-jquery-plugins/depage-growl.js
 * @require framework/shared/depage-jquery-plugins/depage-live-filter.js
 * @require framework/shared/depage-jquery-plugins/depage-live-help.js
 * @require framework/shared/depage-jquery-plugins/depage-shy-dialogue.js
 * @require framework/shared/depage-jquery-plugins/depage-uploader.js
 * @require framework/Cms/js/xmldb.js
 *
 *
 * @file    js/global.js
 *
 * copyright (c) 2006-2015 Frank Hellenkamp [jonas@depage.net]
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
    var $body;
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
            $body = $("body");

            localJS.setup();

            // setup global events
            $window.on("statechangecomplete", localJS.setup);
            $window.on("switchLayout", localJS.switchLayout);

            $window.triggerHandler("switchLayout", "split");

            $previewFrame = $("#previewFrame");
            $flashFrame = $("#flashFrame");
            // @todo add event to page, when clicking outside of edit interface to save current fields

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
            localJS.setupForms();
            localJS.setupHelp();
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
            $toolbarLeft = $("#toolbarmain > menu.left");
            $toolbarPreview = $("#toolbarmain > menu.preview");
            $toolbarRight = $("#toolbarmain > menu.right");

            var layouts = [
                "left-full",
                "split",
                //"tree-split",
                "right-full"
            ];

            // add layout buttons
            var $layoutButtons = $("<li class=\"pills preview-buttons layout-buttons\" data-live-help=\"Switch layout to: Edit-only, Split-view and Preview-only\"></li>").prependTo($toolbarRight);
            for (var i in layouts) {
                var newLayout = layouts[i];
                var $button = $("<a class=\"toggle-button " + newLayout + "\" title=\"switch to " + newLayout + "-layout\">" + newLayout + "</a>")
                    .appendTo($layoutButtons)
                    .on("click", {layout: newLayout}, localJS.switchLayout);
            }

            // add button placeholder
            var $previewButtons = $("<li class=\"preview-buttons\"></li>").prependTo($toolbarPreview);

            // add reload button
            var $reloadButton = $("<a class=\"button\" data-live-help=\"Reload preview\">reload</a>")
                .appendTo($previewButtons)
                .on("click", function() {
                    if ($previewFrame.length > 0) {
                        $previewFrame[0].contentWindow.location.reload();
                    }
                });

            // add edit button
            var $editButton = $("<a class=\"button\" data-live-help=\"Edit current page in edit interface on the left ←.\">edit</a>")
                .appendTo($previewButtons)
                .on("click", function() {
                    var url = $previewFrame[0].contentWindow.location.href;
                    var matches = url.match(/project\/([^\/]*)\/preview\/[^\/]*\/[^\/]*\/[^\/]*(\/.*)/);

                    if (matches) {
                        var project = matches[1];
                        var page = matches[2];

                        localJS.edit(project, page);
                    }
                });

            // add zoom select
            var zooms = [100, 75, 50];
            var $zoomMenu = $("<li><a data-live-help=\"Change zoom level of preview.\">" + zooms[0] + "%</a><menu class=\"popup\"></menu></li>").appendTo($toolbarPreview).find("menu");
            var $zoomMenuLabel = $zoomMenu.siblings("a");

            $(zooms).each(function() {
                var zoom = this;
                var $zoomButton = $("<li><a>" + zoom + "%</a></li>").appendTo($zoomMenu).find("a");
                $zoomButton.on("click", function() {
                    $("div.preview").removeClass("zoom100 zoom75 zoom50")
                        .addClass("zoom" + zoom);
                    $zoomMenuLabel.text(zoom + "%");
                });
            });

            // add live filter to projects menu
            $("menu.projects").depageLiveFilter("li", "a", {
                placeholder: "Filter Projects",
                attachInputInside: true,
                onSelect: function($item) {
                    var $link = $item.find("a").first();
                    if ($link.click()) {
                        window.location = $link[0].href;
                    }
                }
            });

            // add menu navigation
            var $menus = $("#toolbarmain > menu > li");
            var menuOpen = false;

            $menus.each(function() {
                var $entry = $(this);
                var $sub = $entry.find("menu");

                if ($sub.length > 0) {
                    $entry.children("a").on("click", function(e) {
                        var $input = $entry.find("input");
                        if (!menuOpen) {
                            // open submenu if there is one
                            $menus.removeClass("open");
                            $entry.addClass("open");

                            $input.focus();
                        } else {
                            // close opened submenu
                            $menus.removeClass("open");
                            $input.blur();
                        }
                        menuOpen = !menuOpen;

                        return false;
                    });
                    $entry.children("a").on("hover", function(e) {
                        // open submenu on hover if a menu is already open
                        if (menuOpen) {
                            $menus.removeClass("open");
                            $entry.addClass("open");
                        }
                    });
                    $sub.on("click", function(e) {
                        e.stopPropagation();
                    });
                    $sub.find("a").on("click", function(e) {
                        if (menuOpen) {
                            $menus.removeClass("open");
                            menuOpen = false;
                        }
                    });
                }
            });

            $html.on("click", function() {
                // close menu when clicking outside
                $menus.removeClass("open");
                menuOpen = false;
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
            // @todo get project name correctly
            var xmldb = new DepageXmldb(baseUrl, "depage", "settings");
            var currentPos, newPos;

            $(".sortable-forms .sortable").each(function() {
                var $container = $(this);
                var $head = $(this).find("h1");
                var $form = $(this).find("form");

                $head.on("click", function() {
                    if ($container.hasClass("active")) {
                        $container.removeClass("active");
                    } else {
                        $(".sortable.active").removeClass("active");
                        $container.addClass("active");
                    }
                });

                if (!$container.hasClass("new")) {
                    // @todo make last element undeletable
                    var $deleteButton = $("<a class=\"button delete\">Delete</a>");

                    $deleteButton.appendTo($form.find("p.submit"));
                    $deleteButton.depageShyDialogue({
                        ok: {
                            title: 'delete',
                            classes: 'default',
                            click: function(e) {
                                var $input = $form.find("p.node-name");

                                // @todo add dialog to ask if sure
                                xmldb.deleteNode($input.data("nodeid"));

                                $container.remove();

                                return true;
                            }
                        },
                        cancel: {
                            title: 'cancel'
                        }
                    },{
                        title: "delete",
                        message : "delete now?"
                    });
                }
            });
            $(".sortable-forms").sortable({
                itemSelector: ".sortable:not(.new)",
                containerSelector: ".sortable-forms",
                nested: false,
                handle: "h1",
                pullPlaceholder: false,
                placeholder: '<div class="placeholder"></div>',
                tolerance: 10,
                onDragStart: function($item, container, _super, event) {
                    currentPos = $item.index();

                    _super($item, container);
                },
                onDrag: function ($item, position, _super, event) {
                    position.left = 5;
                    position.top -= 10;

                    $item.css(position);
                    $(".placeholder").text($item.find("h1").text());
                },
                onDrop: function($item, container, _super, event) {
                    var $input = $item.find("p.node-name");

                    console.log("onDrop", $input.data("nodeid"),  $input.data("parentid"), newPos);

                    xmldb.moveNode($input.data("nodeid"), $input.data("parentid"), newPos);

                    _super($item, container);
                },
                afterMove: function ($placeholder, container, $closestItemOrContainer) {
                    newPos = $placeholder.index();
                }
            });
        },
        // }}}
        // {{{ setupForms
        setupForms: function() {
            // clear password inputs on user edit page to reset autofill
            setTimeout(function() {
                $(".depage-form.edit-user input[type=password]").each(function() {
                    this.value = "";
                });
            }, 300);
        },
        // }}}
        // {{{ setupHelp
        setupHelp: function() {
            $("#help").depageLivehelp({});
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
                        var newTimeout = $el.find("*[data-ajax-update-timeout]").data("ajax-update-timeout");

                        if (newTimeout && newTimeout < timeout) {
                            timeout = newTimeout;
                        }
                        $("#" + id).empty().append($el.children());
                    });

                    // get script elements
                    $html.filter("script").each( function() {
                        $body.append(this);
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
                $(".preview-buttons").hide();
            } else {
                $(".preview-buttons").show();
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
                var oldUrl = $previewFrame[0].contentWindow.location.href;
                var newUrl = unescape(url);

                if (newUrl.substring(0, baseUrl.length) != baseUrl) {
                    newUrl = baseUrl + newUrl;
                }

                if (oldUrl == newUrl) {
                    $previewFrame[0].contentWindow.location.reload();
                } else {
                    $previewFrame[0].src = newUrl;
                }

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    $window.triggerHandler("switchLayout", "split");
                }
            } else {
                // add preview frame
                var projectName = url.match(/project\/(.*)\/preview/)[1];

                $.get(baseUrl + "project/" + projectName + "/edit/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.preview")
                        .appendTo($body);
                    var $header = $result.find("header.info");

                    $previewFrame = $("#previewFrame");
                    $previewFrame[0].src = unescape(url);

                    $window.triggerHandler("switchLayout", "split");
                });
            }
        },
        // }}}
        // {{{ edit
        edit: function(projectName, page) {
            if (parent != window) {
                parent.depageCMS.edit(projectName, page);
            } else if ($flashFrame.length == 1) {
                var flash = $flashFrame.contents().find("#flash")[0];
                flash.SetVariable("/:gotopage",page);
                flash.Play();

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    $window.triggerHandler("switchLayout", "split");
                }
            } else {
                $.get(baseUrl + "project/" + projectName + "/edit/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.edit")
                        .appendTo($body);
                    var $header = $result.find("header.info");

                    $flashFrame = $("#flashFrame");
                    $flashFrame[0].src = unescape("project/" + projectName + "/flash/flash/false");
                    //@todo add page to flash url

                    $window.triggerHandler("switchLayout", "split");
                });
            }
        },
        // }}}
        // {{{ openUpload
        openUpload: function(projectName, targetPath) {
            $upload = $("#upload");

            if ($upload.length === 0) {
                $upload = $("<div id=\"upload\" class=\"layout-left\"></div>").appendTo($body);
                var $box = $("<div class=\"box\"></div>").appendTo($upload);
            }

            var uploadUrl = baseUrl + "project/" + projectName + "/upload" + targetPath;

            $(document).bind('keyup.uploader', function(e){
                var key = e.which || e.keyCode;
                if (key == 27) {
                    localJS.closeUpload();
                }
            });

            $box.load(uploadUrl, function() {
                var $submitButton = $box.find('input[type="submit"]');
                var $dropArea = $box.find('.dropArea');
                var $progressArea = $("<div class=\"progressArea\"></div>").appendTo($box);
                var $finishButton = $("<a class=\"button\">finished uploading/cancel</a>").appendTo($box);

                $finishButton.on("click", function() {
                    localJS.closeUpload();
                });

                $box.find('input[type="file"]').depageUploader({
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
        // {{{ closeUpload
        closeUpload: function() {
            $upload = $("#upload");

            $upload.remove();

            $(document).unbind('keyup.uploader');
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
        // {{{ flashLayoutChanged
        flashLayoutChanged: function(layout) {
            if (parent != window) {
                parent.depageCMS.flashLayoutChanged(layout);
            } else {
                layout = layout.replace(/ /, "-");
                $(".live-help-mock")
                    .hide()
                    .filter(".layout-" + layout)
                    .show();
            }
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
