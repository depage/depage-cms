/*
 * @require framework/shared/jquery-1.12.3.min.js
 * @require framework/shared/jquery.cookie.js
 * @require framework/shared/jquery-sortable.js
 *
 * @require framework/shared/depage-jquery-plugins/depage-details.js
 * @require framework/shared/depage-jquery-plugins/depage-growl.js
 * @require framework/shared/depage-jquery-plugins/depage-live-filter.js
 * @require framework/shared/depage-jquery-plugins/depage-live-help.js
 * @require framework/shared/depage-jquery-plugins/depage-shy-dialogue.js
 * @require framework/shared/depage-jquery-plugins/depage-uploader.js
 *
 * @require framework/HtmlForm/lib/js/lodash.custom.min.js
 * @require framework/HtmlForm/lib/js/effect.js
 * @require framework/Cms/js/xmldb.js
 * @require framework/Cms/js/locale.js
 * @require framework/Cms/js/depage.jstree.js
 * @require framework/Cms/js/spectrum.js
 *
 *
 * @file    js/global.js
 *
 * copyright (c) 2006-2018 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

var depageCMS = (function() {
    "use strict";
    /*jslint browser: true*/
    /*global $:false */

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];
    var baseUrl = $("base").attr("href");
    var projectName;
    var currentPreviewUrl,
        currentDocId,
        currentDocPropertyId,
        currentPreviewLang = "de",
        currentLibPath = "",
        currentLibAccept = "",
        currentLibForceSize = "",
        currentTasksTimeout = null,
        currentTasks = {};
    var $html;
    var $window;
    var $body;
    var $previewFrame,
        $helpFrame;
    var $upload;
    var $toolbarLeft,
        $toolbarPreview,
        $toolbarRight;

    var $pageTreeContainer,
        $pagedataTreeContainer,
        $docPropertiesContainer;

    var jstreePages;
    var jstreePagedata;

    var currentLayout;

    jQuery.fn.scrollParent = function() {
        var position = this.css( "position" ),
        excludeStaticParent = position === "absolute",
        scrollParent = this.parents().filter( function() {
            var parent = $( this );
            if ( excludeStaticParent && parent.css( "position" ) === "static" ) {
            return false;
            }
            return (/(auto|scroll)/).test( parent.css( "overflow" ) + parent.css( "overflow-y" ) + parent.css( "overflow-x" ) );
        }).eq( 0 );

        return position === "fixed" || !scrollParent.length ? $( this[ 0 ].ownerDocument || document ) : scrollParent;
    };

    // Cross browser, backward compatible solution
    (function( window, Date ) {
        // feature testing
        var raf = window.requestAnimationFrame     ||
                window.mozRequestAnimationFrame    ||
                window.webkitRequestAnimationFrame ||
                window.msRequestAnimationFrame     ||
                window.oRequestAnimationFrame;

        window.animLoop = function(render) {
            var running, lastFrame = +(new Date());
            function loop( now ) {
                if (running !== false) {
                    raf ?
                        raf(loop) :
                        // fallback to setTimeout
                        setTimeout(loop, 16);

                    // Make sure to use a valid time, since:
                    // - Chrome 10 doesn't return it at all
                    // - setTimeout returns the actual timeout
                    now = +(new Date());
                    var deltaT = now - lastFrame;

                    // do not render frame when deltaT is too high
                    if (deltaT < 160) {
                        running = render(deltaT, now);
                    }
                    lastFrame = now;
                }
            }
            loop();
        };
    })(window, Date);

    function lerp(min, max, fraction) {
        return (max - min) * fraction + min;
    }

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

            $previewFrame = $("#previewFrame");
            $helpFrame = $("#helpFrame");

            $window.triggerHandler("switchLayout", "split");

            // @todo add event to page, when clicking outside of edit interface to save current fields

            // @todo test/remove
            //localJS.openUpload("depage", "/");

            // setup ajax timers
            setTimeout(localJS.updateAjaxContent, 1000);
        },
        // }}}
        // {{{ setup
        setup: function() {
            var matches = window.location.href.match(/project\/([^\/]*)\/.*/);
            if (matches !== null)  {
                projectName = matches[1];
            }

            localJS.setupVarious();
            localJS.setupToolbar();
            localJS.setupPreviewLinks();
            localJS.setupProjectList();
            localJS.setupNewsletterList();
            localJS.setupSortables();
            localJS.setupForms();
            localJS.setupHelp();
            localJS.setupTrees();
            localJS.setupLibrary();
            localJS.setupColorSchemes();
            localJS.setupDropTargets();
            localJS.setupNotifications();
        },
        // }}}
        // {{{ setupAjaxContent
        setupAjaxContent: function() {
            localJS.setupPreviewLinks();
            localJS.setupNewsletterList();
        },
        // }}}
        // {{{ setupVarious
        setupVarious: function() {
            $("#logout").click( function() {
                localJS.logout();

                return false;
            });

            // add click event for teaser
            $(".teaser").click( function() {
                document.location = $("a", this)[0].href;
            });
        },
        // }}}
        // {{{ setupNotifications
        setupNotifications: function() {
            if (!window.WebSocket) {
                return;

            }

            var ws = null;
            var webSocketUrl = baseUrl.replace(/^(http)/, "ws") + "notifications";

            ws = new WebSocket(webSocketUrl);
            ws.onmessage = function(e) {
                try {
                    var data = JSON.parse(e.data);

                    if (data.type == "notification") {
                        localJS.handleNotifications(data);
                    } else {
                        localJS.handleTaskMessage(data);
                    }
                } catch (exeption) {
                    return true;
                }
            };
            ws.onerror = function(e) {
                console.log("websocket error");
            };
            ws.onclose = function(e) {
                setTimeout(localJS.setupNotifications, 1000);
            };
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

            // add tree actions
            $toolbarLeft.append("<li class=\"tree-actions\"></li>");

            // add layout buttons
            var $layoutButtons = $("<li class=\"pills preview-buttons layout-buttons\" data-live-help=\"" + locale.layoutSwitchHelp + "\"></li>").prependTo($toolbarRight);
            for (var i in layouts) {
                var newLayout = layouts[i];
                var $button = $("<a class=\"toggle-button " + newLayout + "\" title=\"switch to " + newLayout + "-layout\">" + newLayout + "</a>")
                    .appendTo($layoutButtons)
                    .on("click", {layout: newLayout}, localJS.switchLayout);
            }

            // add button placeholder
            var $previewButtons = $("<li class=\"preview-buttons\"></li>").prependTo($toolbarPreview);

            // add reload button
            var $reloadButton = $("<a class=\"button\" data-live-help=\"" + locale.reloadHelp + "\">" + locale.reload + "</a>")
                .appendTo($previewButtons)
                .on("click", function() {
                    if ($previewFrame.length > 0) {
                        $previewFrame[0].contentWindow.location.reload();
                    }
                });

            // add edit button
            var $editButton = $("<a class=\"button\" data-live-help=\"" + locale.editHelp + "\">" + locale.edit + "</a>")
                .appendTo($previewButtons)
                .on("click", function() {
                    var url = "";
                    try {
                        url = $previewFrame[0].contentWindow.location.href;
                    } catch(error) {
                    }
                    var matches = url.match(/project\/([^\/]*)\/preview\/[^\/]*\/[^\/]*\/[^\/]*(\/.*)/);

                    if (matches) {
                        var project = matches[1];
                        var page = matches[2];

                        localJS.edit(project, page);
                    }
                });

            // add zoom select
            var zooms = [100, 75, 50];
            var $zoomMenu = $("<li><a data-live-help=\"" + locale.zoomHelp + "\">" + zooms[0] + "%</a><menu class=\"popup\"></menu></li>").appendTo($toolbarPreview).find("menu");
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
            $("menu .projects").depageLiveFilter("li", "a", {
                placeholder: locale.projectFilter,
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
                    $entry.children("a").on("mouseenter", function(e) {
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
            var $projects = $(".projectlist");
            var $projectGroups = $projects.children(".projectgroup");

            $projects.depageDetails();

            $projects.find(".buttons .button").on("click", function(e) {
                e.stopPropagation();
            });
            $projects.find(".buttons .button.shortcuts").on("click", function(e) {
                var projectName = $(this).parents("dt").data("project");

                $.vakata.context.show($(this), null, {
                    '_add-title': {
                        label: locale.createNew,
                        action: false,
                        _disabled: true,
                        separator_after: true
                    },
                    '_add-post': {
                        label: locale.newPost,
                        action: function() {
                            localJS.addNewPost(projectName);
                        }
                    }
                });
            });

            $projects.on("depage.detail-opened", function(e, $head, $detail) {
                var project = $head.data("project");
                var projectNewsletter = $head.data("project-newsletter");
                var changesUrl;

                if (project) {
                    changesUrl = baseUrl + "project/" + project + "/details/100/?ajax=true";
                } else if (projectNewsletter) {
                    changesUrl = baseUrl + "project/" + projectNewsletter + "/newsletters/?ajax=true";
                }

                if (changesUrl) {
                    $.get(changesUrl)
                        .done(function(data) {
                            $detail.empty().html(data);

                            localJS.setupAjaxContent();
                        });
                }
            });

            $projects.depageLiveFilter("dt", "strong", {
                placeholder: locale.projectFilter,
                autofocus: true
            });
            $projects.on("depage.filter-shown depage.filter-hidden", function(e, $item) {
                // show and hide headlines for project-groups
                $projectGroups.each(function() {
                    var $group = $(this);
                    var $headline = $group.children("h2");

                    if ($group.find("dt:visible").length > 0) {
                        $headline.show();
                    } else {
                        $headline.hide();
                    }
                });
            });
            $projects.on("depage.filter-hidden", function(e, $item) {
                // close details for hidden items
                $projects.data("depage.details").hideDetail($item);
            });
        },
        // }}}
        // {{{ setupNewsletterList
        setupNewsletterList: function() {
            var $newsletters = $(".newsletter.recent-changes tr:has(td.url)").each(function() {
                var $row = $(this);
                var projectName = $row.data("project");
                var newsletterName = $row.data("newsletter");
                var xmldb = new DepageXmldb(baseUrl, projectName, newsletterName);

                var $deleteButton = $("<a class=\"button\">" + locale.delete + "</a>")
                    .appendTo($row.find(".buttons"))
                    .depageShyDialogue({
                        ok: {
                            title: locale.delete,
                            classes: 'default',
                            click: function(e) {
                                xmldb.deleteDocument();

                                // @todo remove only if operation was successful
                                $row.remove();

                                return true;
                            }
                        },
                        cancel: {
                            title: locale.cancel
                        }
                    },{
                        title: locale.delete,
                        message : locale.deleteQuestion,
                        direction: 'LC',
                        directionMarker: true
                    });
            });
        },
        // }}}
        // {{{ setupPreviewLinks
        setupPreviewLinks: function() {
            $("a.preview").on("click", function(e) {
                if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey) return;

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    $window.triggerHandler("switchLayout", "split");
                }
                localJS.preview(this.href);

                return false;
            });
        },
        // }}}
        // {{{ setupSortables
        setupSortables: function() {
            $(".sortable-forms").each(function() {
                var currentPos, newPos;
                var $sortable = $(this);

                var $form = $sortable.find("form");
                var xmldb = new DepageXmldb(baseUrl, $form.attr("data-project"), $form.attr("data-document"));

                $sortable.find(".sortable").each( function() {
                    var $container = $(this);
                    var $head = $(this).find("h1");

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
                        var $deleteButton = $("<a class=\"button delete\">" + locale.delete + "</a>");

                        $deleteButton.appendTo($container.find("p.submit"));
                        $deleteButton.depageShyDialogue({
                            ok: {
                                title: locale.delete,
                                classes: 'default',
                                click: function(e) {
                                    var $input = $form.find("p.node-name");

                                    xmldb.deleteNode($input.data("nodeid"));

                                    // @todo remove only if operation was successful
                                    $container.remove();

                                    return true;
                                }
                            },
                            cancel: {
                                title: locale.cancel
                            }
                        },{
                            title: locale.delete,
                            message : locale.deleteQuestion,
                            direction: 'LC',
                            directionMarker: true
                        });
                    }
                });
                $sortable.sortable({
                    itemSelector: ".sortable:not(.new)",
                    containerSelector: ".sortable-forms",
                    nested: false,
                    handle: "h1",
                    pullPlaceholder: false,
                    placeholder: '<div class="placeholder"></div>',
                    tolerance: 40,
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

                        xmldb.moveNode($input.data("nodeid"), $input.data("parentid"), newPos);

                        _super($item, container);
                    },
                    afterMove: function ($placeholder, container, $closestItemOrContainer) {
                        newPos = $placeholder.index();
                    }
                });
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

            // add select-all button
            $("form fieldset.select-all").each(function() {
                var $boolean = $(this).find(".input-boolean");
                var $button = $("<p class=\"select-all\"><button>" + locale.selectAll + "</button></p>").insertAfter($(this).find("legend").next()).find("button");
                var allSelected = false;

                $button.on("click", function() {
                    if (!allSelected) {
                        $boolean.find("input").val(["true"]);
                        $button.html(locale.deselectAll);
                    } else {
                        $boolean.find("input").val(["false"]);
                        $button.html(locale.selectAll);
                    }

                    allSelected = !allSelected;

                    return false;
                });
            });

            // add autosaved event to newsletter form
            $("form.newsletter.edit").each(function() {
                var $form = $(this);

                $form.on("depageForm.autosaved", function() {
                    var matches = window.location.href.match(/project\/([^\/]*)\/newsletter\/([^\/]*)\//);
                    var url = baseUrl + "project/" + matches[1] + "/preview/newsletter/pre/" + currentPreviewLang + "/" + matches[2] + ".html";

                    localJS.preview(url);
                });
            });

            if (typeof Squire !== 'undefined') {
                // {{{ Squire.showLinkDialog()
                Squire.prototype.showLinkDialog = function(href, callback) {
                    var pos = this.getPosBySelection();
                    var editor = this;

                    $body.depageShyDialogue({
                        ok: {
                            title: locale.ok,
                            classes: "default",
                            click: function(e) {
                                callback($("#depage-shy-dialogue input")[0].value);
                            }
                        },
                        cancel: {
                            title: locale.cancel,
                            click: function(e) {
                                editor.focus();
                            }
                        }
                    },{
                        bind_el: false,
                        direction: "BC",
                        directionMarker: true,
                        inputs: {
                            href: {
                                placeholder: 'http://domain.com',
                                classes: 'edit-href',
                                value: href
                            }
                        }
                    });

                    $body.data("depage.shyDialogue").showDialogue(pos.left, pos.top);
                };
                // }}}
            }
        },
        // }}}
        // {{{ setupHelp
        setupHelp: function() {
            $("#help").depageLivehelp({});

            /*
            $body.on("click", "a.help", function(e) {
                if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey) return;

                if (currentLayout != "split" && currentLayout != "tree-split") {
                    $window.triggerHandler("switchLayout", "split");
                }
                localJS.help(this.href);

                return false;
            });
            */
        },
        // }}}
        // {{{ setupTrees
        setupTrees: function() {
            $pageTreeContainer = $(".tree.pages");
            $pagedataTreeContainer = $(".tree.pagedata");
            $docPropertiesContainer = $(".doc-properties");

            localJS.loadPageTree();
        },
        // }}}
        // {{{ setupDropTargets
        setupDropTargets: function() {
            $(document)
                .on("dnd_move.vakata.jstree", function(e, data) {
                    var $target = $(data.event.target);
                    var $parent = $target.parent().parent();

                    if (($target.hasClass("edit-href") || $parent.hasClass("edit-href")) && data.element.href.indexOf("pageref://") === 0) {
                        $target.addClass("dnd-hover");
                    } else {
                        $("input.dnd-hover").removeClass("dnd-hover");
                    }
                })
                .on('dnd_stop.vakata.jstree', function (e, data) {
                    var $target = $(data.event.target);
                    var $parent = $target.parent().parent();

                    if (($target.hasClass("edit-href") || $parent.hasClass("edit-href")) && data.element.href.indexOf("pageref://") === 0) {
                        $target[0].value = data.element.href;
                        $target.removeClass("dnd-hover");
                        $target.trigger("change");
                    }
                });
        },
        // }}}
        // {{{ setupLibrary
        setupLibrary: function() {
            var $libraryTreeContainer = $(".tree.library .jstree-container");
            var $fileContainer = $(".files .file-list");
            var $toolbar = $("<span class=\"toolbar-filelist\"></span>").appendTo("#toolbarmain .tree-actions");
            var $deleteButton = localJS.addToolbarButton($toolbar, locale.delete, "icon-delete", localJS.deleteSelectedFiles);
            var last = false;

            $libraryTreeContainer
                .on("activate_node.jstree", function(e, data) {
                    var path = data.node.a_attr.href.replace(/libref:\/\//, "");

                    if (currentLibPath != path) {
                        currentLibPath = path;

                        localJS.loadLibraryFiles(path);
                    }
                })
                .on("refresh.jstree", function(e, data) {
                    var selected = data.instance.get_selected(true);

                    if (typeof selected[0] == 'undefined') return;
                    var path = selected[0].a_attr.href.replace(/libref:\/\//, "");

                    if (currentLibPath != path) {
                        currentLibPath = path;

                        localJS.loadLibraryFiles(path);
                    }
                })
                .on("ready.jstree", function(e, data) {
                    $fileContainer.click();
                })
                .on("focus.jstree", function(e, data) {
                    $fileContainer.removeClass("focus");
                    $toolbar.removeClass("visible");
                })
                .depageTree();

            $fileContainer
                .on("selectionChange.depage", function() {
                    localJS.checkSelectedFiles();

                    if ($fileContainer.find(".selected").length > 0) {
                        $deleteButton.removeClass("disabled");
                    } else {
                        $deleteButton.addClass("disabled");
                    }
                })
                .on("click", function(e) {
                    $fileContainer.addClass("focus");
                    $toolbar.addClass("visible");
                    // @todo create toolbar for selected files
                    $libraryTreeContainer.jstree(true).looseFocus();
                })
                .on("click", "figure", function(e) {
                    var $thumbs = $fileContainer.find("figure");
                    var current = $thumbs.index(this);

                    // allow multiple select with ctrl and shift
                    if (!e.metaKey && !e.ctrlKey && !e.shiftKey) {
                        $fileContainer.find(".selected").removeClass("selected");
                        last = false;
                    }
                    // allow multiple select in row with shift
                    if (e.shiftKey) {
                        if (last !== false) {
                            var start = last;
                            var end = current;
                            if (last > current) {
                                start = current;
                                end = last;
                            }
                            for (var i = start; i <= end; i++) {
                                $thumbs.eq(i).addClass("selected");
                            }
                        }
                    } else {
                        $(this).toggleClass("selected");
                    }
                    last = current;
                    $thumbs.blur();

                    $fileContainer.trigger("selectionChange.depage");
                })
                .on("contextmenu", "figure", function(e) {
                    var $thumb = $(this);
                    if (!$thumb.hasClass("selected")) {
                        $thumb.addClass("selected");
                        $fileContainer.trigger("selectionChange.depage");
                    }

                    $.vakata.context.show($(this), {x: e.pageX, y:e.pageY}, {
                        _delete: {
                            label: locale.delete,
                            action: function() {
                                localJS.deleteSelectedFiles();
                            }
                        }
                    });

                    return false;
                })
                .on("dblclick", "figure", function(e) {
                    var $ok = $(".dialog-full .dialog-bar .button.default");
                    if ($ok.length == 1) {
                        $ok.click();
                    }
                });

            localJS.setupFileList();
        },
        // }}}
        // {{{ setupFileList
        setupFileList: function() {
            var $form = $("#upload-to-lib");
            var $dropArea = $form.parents('.files');
            var $progressArea = $("<div class=\"progressArea\"></div>").appendTo($form);
            var $fileContainer = $(".files .file-list");

            $form.find('input[type="submit"]').remove();
            $form.find('input[type="file"]').depageUploader({
                $drop_area: $dropArea,
                $progress_container: $progressArea
            }).on('complete', function(e, html) {
                localJS.loadLibraryFiles($form.find("p.input-file").attr("data-path"));
            });

            $(".file-list ul").depageLiveFilter("li", "figcaption");
            $(".file-list li").on("depage.filter-hidden", function() {
                $("figure", this).removeClass("selected");
                $fileContainer.trigger("selectionChange.depage");
            });

            $fileContainer.trigger("selectionChange.depage");
        },
        // }}}
        // {{{ setupColorSchemes
        setupColorSchemes: function() {
            var $colorTreeContainer = $(".tree.colors .jstree-container");
            var $colorContainer = $(".colorscheme .color-list");
            var $colorProps = $(".color-property");
            var docref = $colorTreeContainer.attr("data-doc-id");

            var $toolbar = $("<span class=\"toolbar-colors\"></span>").appendTo("#toolbarmain .tree-actions");
            var $addButton = localJS.addToolbarButton($toolbar, locale.create, "icon-create", localJS.addColor);
            var $deleteButton = localJS.addToolbarButton($toolbar, locale.delete, "icon-delete", localJS.deleteSelectedColor);

            var xmldb = new DepageXmldb(baseUrl, projectName, "colors");

            $colorTreeContainer
                .on("activate_node.jstree", function(e, data) {
                    var nodeId = null;
                    if (typeof data.node.data !== 'undefined') {
                        nodeId = data.node.data.nodeId;
                    }
                    if (data.node.li_attr.rel != "proj:colorscheme") {
                        $colorContainer.empty();
                        $colorProps.empty();
                        return;
                    }

                    var url = baseUrl + "project/" + projectName + "/colors/edit/" + nodeId + "/";

                    $colorContainer.removeClass("loaded").load(url + "?ajax=true", function() {
                        $colorContainer.find("figure[data-name='unnamed_color']").addClass("selected");
                        $colorContainer.trigger("selectionChange.depage");
                    });
                })
                .on("ready.jstree", function(e, data) {
                    $colorTreeContainer.jstree(true).activate_node($colorTreeContainer.find("ul:first li:first")[0]);
                    $colorContainer.click();
                })
                .on("focus.jstree", function(e, data) {
                    $colorContainer.removeClass("focus");
                    $toolbar.removeClass("visible");
                })
                .depageTree();

            $colorContainer
                .on("selectionChange.depage", function() {
                    var $color = $colorContainer.find(".selected");
                    if ($color.length > 0) {
                        $deleteButton.removeClass("disabled");
                    } else {
                        $deleteButton.addClass("disabled");
                    }
                    localJS.setupColorProperties($color);
                })
                .on("click", function(e) {
                    $colorContainer.addClass("focus");
                    $toolbar.addClass("visible");

                    $colorTreeContainer.jstree(true).looseFocus();
                })
                .on("click", "figure", function(e) {
                    var $thumbs = $colorContainer.find("figure");
                    var current = $thumbs.index(this);

                    $colorContainer.find(".selected").removeClass("selected");

                    $(this).addClass("selected");

                    $thumbs.blur();

                    $colorContainer.trigger("selectionChange.depage");
                })
                .on("changeColorValue.spectrum", "figure", function(e) {
                    var $color = $(this);
                    var nodeId = $color.attr("data-nodeid");
                    var value = $color.attr("data-value");

                    $color.children(".preview")
                        .css("backgroundColor", value);

                    localJS.saveColor(nodeId, value);
                })
                .on("changeColorName.spectrum", "figure", function(e) {
                    var $color = $(this);
                    var nodeId = $color.attr("data-nodeid");
                    var value = $color.attr("data-name");

                    $color.children("figcaption")
                        .text(value);

                    localJS.renameColor(nodeId, value);
                })
                .on("contextmenu", "figure", function(e) {
                    var $thumb = $(this);
                    if (!$thumb.hasClass("selected")) {
                        $thumb.addClass("selected");
                        $colorContainer.trigger("selectionChange.depage");
                    }

                    $.vakata.context.show($(this), {x: e.pageX, y:e.pageY}, {
                        _delete: {
                            label: locale.delete,
                            action: function() {
                                localJS.deleteSelectedColor();
                            }
                        }
                    });

                    return false;
                });
        },
        // }}}
        // {{{ setupColorProperties
        setupColorProperties: function($color) {
            var $colorProps = $(".color-property").empty();

            if (!$color || $color.length == 0) return;

            var $input,
                $nameInput,
                $r, $g, $b,
                $h, $s, $v;

            $input = $("<input />")
                .attr("value", $color.attr("data-value"))
                .on("move.spectrum", function(e, color) {
                    var hex = color.toHexString();
                    var rgb = color.toRgb();
                    var hsv = color.toHsv();

                    $r[0].value = rgb.r;
                    $g[0].value = rgb.g;
                    $b[0].value = rgb.b;

                    $h[0].value = Math.round(hsv.h);
                    $s[0].value = Math.round(hsv.s * 100);
                    $v[0].value = Math.round(hsv.v * 100);

                    $color.attr("data-value", hex);
                    $color.trigger("changeColorValue.spectrum");
                })
                .appendTo($colorProps);


            var p = JSON.parse($(".color-list ul").attr("data-palette"));
            $input.spectrum({
                flat: true,
                preferredFormat: "hex",
                showButtons: false,
                showInitial: true,
                showInput: true,
                showPalette: true,
                showSelectionPalette: false,
                palette: p
            });

            var rgb = $input.spectrum("get").toRgb();
            var hsv = $input.spectrum("get").toHsv();
            var setFromRgbInputs = function() {
                // use rgb presentation to parse to catch errors
                var c1 = $input.spectrum("get");
                var c2 = tinycolor("rgb(" + $r[0].value + "," + $g[0].value + "," + $b[0].value + ")");

                if (c2.isValid()) {
                    // set new color
                    $input.spectrum("set", c2);
                } else {
                    // reset color
                    c2 = c1;
                }
                if (!tinycolor.equals(c1, c2)) {
                    $input.trigger("move.spectrum", c2);
                }
            };
            var setFromHsvInputs = function() {
                // use hsv presentation to parse to catch errors
                var c1 = $input.spectrum("get");
                var c2 = tinycolor("hsv(" + $h[0].value + "," + ($s[0].value / 100) + "," + ($v[0].value / 100) + ")");

                if (c2.isValid()) {
                    // set new color
                    $input.spectrum("set", c2);
                } else {
                    // reset color
                    c2 = c1;
                }
                if (!tinycolor.equals(c1, c2)) {
                    $input.trigger("move.spectrum", c2);
                }
            };

            $r = $("<input />")
                .attr("placeholder", "Red")
                .attr("value", rgb.r);
            $g = $("<input />")
                .attr("placeholder", "Green")
                .attr("value", rgb.g);
            $b = $("<input />")
                .attr("placeholder", "Blue")
                .attr("value", rgb.b);
            $h = $("<input />")
                .attr("min", 0).attr("max", 360)
                .attr("placeholder", "Hue")
                .attr("value", Math.round(hsv.h));
            $s = $("<input />")
                .attr("placeholder", "Saturation")
                .attr("min", 0).attr("max", 100)
                .attr("value", Math.round(hsv.s * 100));
            $v = $("<input />")
                .attr("placeholder", "Brightness")
                .attr("min", 0).attr("max", 100)
                .attr("value", Math.round(hsv.v * 100));

            $().add($r).add($g).add($b).add($h).add($s).add($v)
                .attr("class", "sp-input sp-color-value")
                .attr("type", "number")
                .appendTo(".sp-input-container");

            $().add($r).add($g).add($b)
                .attr("min", 0).attr("max", 255)
                .on("paste blur change", function(e) {
                    setFromRgbInputs(this);
                })
                .on("keydown", function(e) {
                    if (e.keyCode == 13) {
                        setFromRgbInputs(this);
                    }
                });
            $().add($h).add($s).add($v)
                .on("paste blur change", function(e) {
                    setFromHsvInputs(this);
                })
                .on("keydown", function(e) {
                    if (e.keyCode == 13) {
                        setFromHsvInputs(this);
                    }
                });

            $nameInput = $("<input />")
                .attr("class", "sp-input")
                .attr("type", "text")
                .attr("value", $color.attr("data-name"))
                .on("change", function(e) {
                    $color.attr("data-name", this.value);
                    $color.trigger("changeColorName.spectrum");
                })
                .prependTo(".sp-input-container");

            // @todo add palette based on colors in colorschemes of current project?
        },
        // }}}

        // {{{ loadPageTree
        loadPageTree: function() {
            if ($pageTreeContainer.length === 0) return false;

            var $tree;
            var url = baseUrl + $pageTreeContainer.data("url");

            if (typeof jstreePages != 'undefined') {
                jstreePages.destroy();
            }
            $pageTreeContainer.removeClass("loaded").load(url + "?ajax=true", function() {
                $pageTreeContainer.addClass("loaded");
                $tree = $pageTreeContainer.children(".jstree-container");

                jstreePages = $tree.depageTree()
                    .on("activate_node.jstree", function(e, data) {
                        if (typeof window.history != 'undefined') {
                            window.history.pushState(null, null, baseUrl + "project/" + projectName + "/edit/" + data.node.id + "/");
                        }
                        localJS.loadPagedataTree(data.node.data.docRef);

                        // preview page
                        var url = baseUrl + "project/" + projectName + "/preview/html/pre/" + currentPreviewLang + data.node.data.url;

                        localJS.preview(url);
                    })
                    .on("refresh.jstree refresh_node.jstree", function () {
                        var node = jstreePages.get_selected(true)[0];
                        if (typeof node == 'undefined') return;

                        var url = baseUrl + "project/" + projectName + "/preview/html/pre/" + currentPreviewLang + node.data.url;

                        localJS.preview(url);
                    })
                    .on("ready.jstree", function () {
                        var currentPageId = $pageTreeContainer.attr("data-selected-nodes");
                        if (currentPreviewUrl) {
                            $.ajax({
                                async: true,
                                type: 'POST',
                                url: baseUrl + "api/" + projectName + "/project/pageId/",
                                data: { url: currentPreviewUrl },
                                success: function(data, status) {
                                    var node = jstreePages.get_node(data.pageId);
                                    if (node) {
                                        jstreePages.activate_node(node);
                                        jstreePages.get_node(node, true)[0].scrollIntoView();
                                    } else {
                                        jstreePages.activate_node($tree.find("ul:first li:first")[0]);
                                    }
                                }
                            });

                            return;
                        } else if (currentPageId) {
                            var node = jstreePages.get_node(currentPageId);
                            if (node) {
                                jstreePages.activate_node(node);
                                jstreePages.get_node(node, true)[0].scrollIntoView();

                                return;
                            }
                        }

                        jstreePages.activate_node($tree.find("ul:first li:first")[0]);
                    })
                    .jstree(true);
            });
        },
        // }}}
        // {{{ loadPagedataTree
        loadPagedataTree: function(docref) {
            if ($pagedataTreeContainer.length === 0 || currentDocId == docref) return false;

            $pagedataTreeContainer.empty();
            $docPropertiesContainer.empty();

            currentDocId = docref;

            if (docref == "") return false;

            var $tree;
            var url = baseUrl + "project/" + projectName + "/tree/" + docref + "/";

            if (typeof jstreePagedata != 'undefined') {
                jstreePagedata.destroy();
            }
            $pagedataTreeContainer.removeClass("loaded").load(url + "?ajax=true", function() {
                $pagedataTreeContainer.addClass("loaded");
                $tree = $pagedataTreeContainer.children(".jstree-container");

                jstreePagedata = $tree.depageTree()
                    .on("activate_node.jstree", function(e, data) {
                        var nodeId = null;
                        if (typeof data.node.data !== 'undefined') {
                            nodeId = data.node.data.nodeId;
                        }
                        localJS.loadDocProperties(docref, nodeId);
                    })
                    .on("ready.jstree", function () {
                        $tree.find("ul:first li").each(function() {
                            //jstreePagedata.open_node(this, false, false);
                            jstreePagedata.open_all();
                        });
                        jstreePagedata.activate_node($tree.find("ul:first li:first")[0]);
                    })
                    .on("refresh.jstree refresh_node.jstree", function () {
                        localJS.updatePreview();
                    })
                    .on("destroy.jstree", function () {
                        console.log("destroyed");
                    })
                    .jstree(true);

            });
        },
        // }}}
        // {{{ loadDocProperties
        loadDocProperties: function(docref, nodeid) {
            if (currentDocPropertyId == nodeid) return false;

            currentDocPropertyId = nodeid;

            var url = baseUrl + "project/" + projectName + "/doc-properties/" + docref + "/" + nodeid + "/";
            var xmldb = new DepageXmldb(baseUrl, projectName, "pages");

            $docPropertiesContainer.removeClass("loaded").empty().load(url + "?ajax=true", function() {
                // @todo scroll to top
                $docPropertiesContainer.addClass("loaded");
                var $form = $docPropertiesContainer.find('.depage-form');

                $form.depageForm();
                $form.find("p.submit").remove();
                $form.find("input, textarea, .textarea-content").on("focus", function() {
                    var lang = $(this).parents("p[lang]").attr("lang");
                    if (typeof lang == "undefined" || lang == "") return;

                    currentPreviewLang = lang;
                    // @todo replace language more intelligently
                    currentPreviewUrl = currentPreviewUrl.replace(/\/pre\/..\//, "/pre/" + lang + "/");
                });
                $form.find(".page-navigations input").on("change", function() {
                    var pageId = $(this).parents("p").data("pageid");
                    var attrName = "nav_" + this.value;
                    var attrValue = this.checked ? 'true' : 'false';

                    xmldb.setAttribute(pageId, attrName, attrValue);
                });
                $form.find(".page-tags input").on("change", function() {
                    var pageId = $(this).parents("p").data("pageid");
                    var attrName = "tag_" + this.value;
                    var attrValue = this.checked ? 'true' : 'false';

                    xmldb.setAttribute(pageId, attrName, attrValue);
                });
                $form.find(".page-type select").on("change", function() {
                    var pageId = $(this).parents("p").data("pageid");
                    var attrName = "file_type";
                    var attrValue = this.value;

                    xmldb.setAttribute(pageId, attrName, attrValue);
                });
                $form.find(".doc-property-meta a.release").on("click", function() {
                    $(this).addClass("disabled");
                    var docRef = $(this).parents("fieldset").data("docref");
                    var xmldb = new DepageXmldb(baseUrl, projectName, docRef);

                    xmldb.releaseDocument();

                    return false;
                });
                $form.find(".edit-src").each(function() {
                    var $input = $(this).find("input");
                    var $button = $("<a class=\"button choose-file\">…</a>").insertAfter($input.parent());

                    $input.on("change", function() {
                        // image changed -> update thumbnail
                        var thumbUrl = url + "thumbnail/" + encodeURIComponent($input[0].value) + "/?ajax=true";

                        $.get(thumbUrl, function(data) {
                            var $thumb = $(data).insertBefore($input.parent().parent());
                            $thumb.prev("figure.thumb").eq(0).remove();
                        });
                    });
                    $button.on("click", function() {
                        localJS.loadFileChooser($input);
                    });
                });
                $form.find(".edit-href").each(function() {
                    var $input = $(this).find("input");
                    var $button = $("<a class=\"button choose-file\">…</a>").insertAfter($input.parent());

                    $button.on("click", function() {
                        localJS.loadFileChooser($input);
                    });
                });
                $form.on("depageForm.autosaved", function() {
                    $form.find(".doc-property-meta a.release").removeClass("disabled");
                });

                // @todo add ui for editing table columns and rows
                // @todo keep squire from merging cells when deleting at the beginning or end of cell
                // @todo add support for better handling of tab key to jump between cells

                localJS.hightlighCurrentDocProperty();
            });
        },
        // }}}
        // {{{ loadLibraryFiles
        loadLibraryFiles: function(path) {
            path = encodeURIComponent(path);
            var url = baseUrl + "project/" + projectName + "/library/files/" + path + "/";
            var $fileContainer = $(".files .file-list");

            $fileContainer.removeClass("loaded").empty().load(url + "?ajax=true", function() {
                localJS.setupFileList();
            });
        },
        // }}}
        // {{{ loadFileChooser
        loadFileChooser: function($input) {
            var path = $input[0].value.replace(/^libref:\/\//, '').replace(/[^\/]*$/, '') || currentLibPath;
            var $inputParent = $input.parent().parent();
            var url = baseUrl + "project/" + projectName + "/library/manager/" + encodeURIComponent(path) + "/";

            currentLibAccept = $inputParent.attr("data-accept");
            currentLibForceSize = $inputParent.attr("data-forceSize");
            currentLibPath = path;

            $pageTreeContainer.children(".jstree-container").jstree(true).looseFocus();
            $pagedataTreeContainer.children(".jstree-container").jstree(true).looseFocus();

            var $dialogContainer = $("<div class=\"dialog-full\"><div class=\"content\"></div></div>")
                .appendTo($body);

            setTimeout(function() {
                $dialogContainer.addClass("visible");
                $(".layout").addClass("no-live-help");
            }, 10);

            $dialogContainer.children(".content").load(url + "?ajax=true", function() {
                $dialogContainer.on("click", function() {
                    localJS.removeFileChooser();
                });
                $dialogContainer.on("click", ".content", function(e) {
                    e.stopPropagation();
                });
                var $dialogBar = $("<div class=\"dialog-bar\"></div>");
                var $ok = $("<a class=\"button default disabled\">" + locale.choose + "</a>").appendTo($dialogBar);
                var $cancel = $("<a class=\"button\">"+ locale.cancel + "</a>").appendTo($dialogBar);

                $ok.on("click.depageFileChooser", function() {
                    if ($(this).hasClass("disabled")) return false;

                    localJS.removeFileChooser($input);
                });
                $cancel.on("click.depageFileChooser", function() {
                    localJS.removeFileChooser();
                });

                $(document).on("keyup.depageFileChooser", function(e) {
                    var key = e.which;
                    if (key === 27) { // ESC
                        localJS.removeFileChooser();
                    } else if (key === 13) { // Enter
                        $ok.click();
                    }
                });

                $dialogBar.prependTo($dialogContainer.children(".content"));

                // @todo select input current file if available and scroll into view
                $("figure.thumb[data-libref='" + $input[0].value + "']").addClass("selected");

                localJS.setupLibrary();

                var $fileContainer = $(".files .file-list");
                $fileContainer
                    .on("selectionChange.depage", function() {
                        if ($fileContainer.find(".selected:not(.invalid-selection)").length > 0) {
                            $ok.removeClass("disabled");
                        } else {
                            $ok.addClass("disabled");
                        }
                    });
            });
        },
        // }}}
        // {{{ removeFileChooser()
        removeFileChooser: function($input) {
            var jstree;
            var $dialogContainer = $(".dialog-full");
            var $selected = $dialogContainer.find("figure.selected:not(.invalid-selection)");

            if (typeof $input !== 'undefined' && $selected.length > 0) {
                $input[0].value = $selected.attr("data-libref");
                $input.trigger("change");
            }

            $(document).off("keypress.depageFileChooser");
            $(".toolbar-filelist").remove();
            if ((jstree = $(".tree.library .jstree-container").jstree(true))) {
                jstree.destroy();
            }


            // focus document tree
            $pagedataTreeContainer.children(".jstree-container").jstree(true).gainFocus();

            $dialogContainer.removeClass("visible");
            $(".layout").removeClass("no-live-help");
            setTimeout(function() {
                $dialogContainer.remove();
            }, 500);
        },
        // }}}
        // {{{ checkSelectedFiles()
        checkSelectedFiles: function($input) {
            var $fileContainer = $(".files .file-list");
            var $files = $fileContainer.find(".selected");
            var exts = [];
            var matches = /(\d+|X)x(\d+|X)/.exec(currentLibForceSize);
            var width = "X", height = "X";

            if (currentLibAccept != "") {
                exts = currentLibAccept.split(",");
            }
            if (matches && matches.length == 3) {
                width = matches[1];
                height = matches[2];
            }

            $(".file-list > .message").remove();
            var $message = $("<p class=\"message\"></p>").insertBefore(".dialog-full .file-list > ul");

            $message.append("<b>" + locale.chooseFileMessage + "</b>");
            $message.append("<br>");

            if (width == "X" && height == "X" && exts.length == 0) return;

            if (exts.length > 0) {
                $message.append(document.createTextNode(currentLibAccept));
                $message.append("<br>");
            }
            if (width != "X") {
                $message.append(document.createTextNode(locale.forceWidthMessage + width + "px"));
                $message.append("<br>");
            }
            if (height != "X") {
                $message.append(document.createTextNode(locale.forceHeightMessage + height + "px"));
                $message.append("<br>");
            }

            // @todo add message about selectable files

            $files.each(function() {
                var $file = $(this);

                if (exts.length > 0) {
                    var ext = "." + $file.attr("data-ext");
                    if (exts.indexOf(ext) == -1) {
                        $file.addClass("invalid-selection");
                    }
                }
                if (width != "X") {
                    if ($file.attr("data-width") != width) {
                        $file.addClass("invalid-selection");
                    }
                }
                if (height != "X") {
                    if ($file.attr("data-height") != height) {
                        $file.addClass("invalid-selection");
                    }
                }
            });
        },
        // }}}
        // {{{ deleteSelectedFiles()
        deleteSelectedFiles: function() {
            var $fileContainer = $(".files .file-list");
            var $files = $fileContainer.find(".selected");
            var files = [];

            if ($files.length == 0) return;

            $files.each(function() {
                files.push($(this).attr("data-libref"));
            });

            var pos = $files.eq(0).offset();
            var url = baseUrl + "project/" + projectName + "/library/delete/";

            pos.top += 135;
            pos.left += 100;

            $body.depageShyDialogue({
                ok: {
                    title: locale.delete,
                    classes: 'default',
                    click: function(e) {
                        $.post(url, {
                            files: files
                        }, function() {
                            $files.parent().remove();
                            $fileContainer.trigger("selectionChange.depage");
                        });

                        return true;
                    }
                },
                cancel: {
                    title: locale.cancel
                }
            },{
                bind_el: false,
                direction: "LC",
                directionMarker: true,
                title: locale.delete,
                message: locale.deleteQuestion
            });

            // @todo add click event outside of shy dialogue to hide it
            $body.data("depage.shyDialogue").showDialogue(pos.left, pos.top);
        },
        // }}}
        // {{{ addColor()
        addColor: function() {
            var $colorContainer = $(".colorscheme .color-list");
            var colorType = $colorContainer.children("ul").attr("data-type");
            var colorschemeId = $colorContainer.children("ul").attr("data-colorschemeid");
            var url = baseUrl + "project/" + projectName + "/colors/addColor/";

            $.post(url, {
                colorType: colorType
            }, function() {
                var url = baseUrl + "project/" + projectName + "/colors/edit/" + colorschemeId + "/";

                $colorContainer.removeClass("loaded").load(url + "?ajax=true", function() {
                    $colorContainer.trigger("selectionChange.depage");
                });
            });
        },
        // }}}
        // {{{ saveColor()
        saveColor: _.throttle(function(nodeId, value) {
            var xmldb = new DepageXmldb(baseUrl, projectName, "colors");
                xmldb.setAttribute(nodeId, "value", value);
        }, 750, {
            leading: true,
            trailing: true
        }),
        // }}}
        // {{{ renameColor()
        renameColor: function(nodeId, value) {
            var url = baseUrl + "project/" + projectName + "/colors/renameColor/";

            $.post(url, {
                id: nodeId,
                name: value
            });
        },
        // }}}
        // {{{ deleteSelectedColor()
        deleteSelectedColor: function(nodeId) {
            var url = baseUrl + "project/" + projectName + "/colors/deleteColor/";
            var $colorContainer = $(".colorscheme .color-list");
            var $colors = $colorContainer.find(".selected");
            var colorId = $colors.attr("data-nodeid");

            if ($colors.length == 0) return;

            var pos = $colors.eq(0).offset();

            pos.top += 75;
            pos.left += 100;

            $body.depageShyDialogue({
                ok: {
                    title: locale.delete,
                    classes: 'default',
                    click: function(e) {
                        $.post(url, {
                            id: colorId
                        }, function() {
                            $colors.parent().remove();
                            $colorContainer.trigger("selectionChange.depage");
                        });
                    }
                },
                cancel: {
                    title: locale.cancel
                }
            },{
                bind_el: false,
                direction: "LC",
                directionMarker: true,
                title: locale.delete,
                message: locale.deleteQuestion
            });

            // @todo add click event outside of shy dialogue to hide it
            $body.data("depage.shyDialogue").showDialogue(pos.left, pos.top);
        },
        // }}}
        // {{{ addNewPost()
        addNewPost: function(projectName) {
            var url = baseUrl + "project/" + projectName + "/add-new-post/";

            $.post(url, {
                projectName: projectName
            }, function(data) {
                if (!data.pageId) return;

                var url = baseUrl + "project/" + projectName + "/edit/" + data.pageId + "/";

                window.location = url;
            });
        },
        // }}}

        // {{{ updateAjaxContent
        updateAjaxContent: function() {
            if (window != window.top) {
                // don't call this in iframed content
                return;
            }
            var url = "overview/";
            var timeout = 5000;

            $.ajax({
                url: baseUrl + url.trim() + "?ajax=true",
                success: function(responseText, textStatus, jqXHR) {
                    if (!responseText) {
                        return;
                    }
                    var $html = $(responseText);
                    var found = false;

                    // get children with ids and replace content
                    $html.filter("[id]").each( function() {
                        var $el = $(this);
                        var id = this.id;
                        var newTimeout = $el.find("*[data-ajax-update-timeout]").data("ajax-update-timeout");

                        if (newTimeout && newTimeout < timeout) {
                            timeout = newTimeout;
                        }
                        var $target = $("#" + id);
                        if ($target.length > 0) {
                            $target.empty().append($el.children());
                            found = true;
                        }
                    });

                    // get script elements
                    $html.filter("script").each( function() {
                        $body.append(this);
                    });
                    if (found) {
                        setTimeout(localJS.updateAjaxContent, timeout);
                    }
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
                $(".preview-buttons").css({display: "inline"});
            }
            $html
                .removeClass("layout-left-full layout-right-full layout-tree-split layout-split")
                .addClass("layout-" + currentLayout);

            var $buttons = $toolbarRight.find(".layout-buttons a")
                .removeClass("active")
                .filter("." + currentLayout).addClass("active");

            if (currentLayout != "left-full") {
                localJS.updatePreview();
            }
        },
        // }}}
        // {{{ preview
        preview: function(url) {
            if (typeof url == 'undefined' || url[0] == "/") return;

            // @todo add preview language on multilanguage sites
            if (parent != window) {
                parent.depageCMS.preview(url);
            } else if ($previewFrame.length == 1) {
                var newUrl = unescape(url);
                var oldUrl = "";
                try {
                    oldUrl = $previewFrame[0].contentWindow.location.href;
                } catch(error) {
                }

                if (newUrl.substring(0, baseUrl.length) != baseUrl) {
                    newUrl = baseUrl + newUrl;
                }
                currentPreviewUrl = newUrl;

                if (currentLayout == "left-full") {
                    // @todo load preview when changing layout?
                    return;
                }

                if (oldUrl == newUrl) {
                    $previewFrame[0].contentWindow.location.reload();
                } else {
                    var $newFrame = $("<iframe />").insertAfter($previewFrame);
                    $previewFrame.remove();
                    $previewFrame = $newFrame.attr("id", "previewFrame");
                    $previewFrame.one("load", localJS.hightlighCurrentDocProperty);
                    $previewFrame[0].src = newUrl;
                }
            } else {
                // add preview frame
                var projectName = url.match(/project\/(.*)\/preview/)[1];

                $.get(baseUrl + "project/" + projectName + "/edit/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.preview")
                        .appendTo($body);

                    $previewFrame = $("#previewFrame");
                    $previewFrame.one("load", localJS.hightlighCurrentDocProperty);
                    $previewFrame[0].src = unescape(url);

                    $window.triggerHandler("switchLayout", "split");
                });
            }
        },
        // }}}
        // {{{ updatePreview
        updatePreview: _.throttle(function() {
            // @todo update throttle to just reload when old page has already been loaded -> test performance esp. on iOS
            this.preview(currentPreviewUrl);
        }, 3000, {
            leading: true,
            trailing: true
        }),
        // }}}
        // {{{ help
        help: function(url) {
            if (typeof url == 'undefined' || url[0] == "/") return;

            if ($helpFrame && $helpFrame.length == 1) {
                var newUrl = unescape(url);
                var oldUrl = "";
                try {
                    oldUrl = $helpFrame[0].contentWindow.location.href;
                } catch(error) {
                }

                if (currentLayout == "left-full") {
                    // @todo load preview when changing layout?
                    return;
                }

                if (oldUrl == newUrl) {
                    $helpFrame[0].contentWindow.location.reload();
                } else {
                    var $newFrame = $("<iframe />").insertAfter($helpFrame);
                    $helpFrame.remove();
                    $helpFrame = $newFrame.attr("id", "helpFrame");
                    $helpFrame[0].src = newUrl;
                }
            } else {
                // add help frame
                $.get(baseUrl + "help/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.help")
                        .removeClass("layout-full")
                        .addClass("layout-right")
                        .appendTo($body);

                    // @todo add close button to header
                    $("<a class=\"close\" aria-label=\"" + locale.close + "\">" + locale.close + "</a>").appendTo(
                        $result.find("header.info")
                    ).on("click", function() {
                        $("div.help").remove();
                        $helpFrame = null;

                        if ($previewFrame.length == 0) {
                            $window.triggerHandler("switchLayout", "left");
                        }
                    });

                    $helpFrame = $("#helpFrame");
                    $helpFrame[0].src = unescape(url);

                    $window.triggerHandler("switchLayout", "split");
                });
            }
        },
        // }}}
        // {{{ hightlighCurrentDocProperty
        hightlighCurrentDocProperty: function() {
            try {
                var className = "depage-live-edit-highlight";
                var $iframe = $previewFrame.contents();
                var $current = $iframe.find("*[data-db-id='" + currentDocPropertyId + "']");

                $iframe.find("." + className).removeClass(className);
                $current.addClass(className);
                if ($current.length == 1) {
                    $current[0].scrollIntoView();
                    var $scroller = $current.scrollParent();
                    $scroller.scrollTop($scroller.scrollTop() - 100);
                }
            } catch(error) {
            }
        },
        // }}}
        // {{{ edit
        edit: function(pName, page) {
            if (jstreePages) {
                $.ajax({
                    async: true,
                    type: 'POST',
                    url: baseUrl + "api/" + pName + "/project/pageId/",
                    data: { url: page },
                    success: function(data, status) {
                        var node = jstreePages.get_node(data.pageId);
                        if (node) {
                            jstreePages.activate_node(node);
                            jstreePages.get_node(node, true)[0].scrollIntoView();
                        }
                    }
                });
            } else {
                // @todo updated for jsinterface
                $.get(baseUrl + "project/" + pName + "/edit/?ajax=true", function(data) {
                    var $result = $("<div></div>")
                        .html( data )
                        .find("div.edit")
                        .appendTo($body);
                    var $header = $result.find("header.info");
                    projectName = pName;
                    currentPreviewUrl = page;

                    localJS.setupTrees();

                    $window.triggerHandler("switchLayout", "split");
                });
            }
            if (typeof window.history != 'undefined') {
                window.history.pushState(null, null, baseUrl + "project/" + pName + "/edit/");
            }
        },
        // }}}

        // {{{ handleNotifications
        handleNotifications: function(n) {
            var action = null;
            var duration = 3000;
            var options = {
                message: n.message,
                backend: 'html'
            };

            if (typeof n.options.link == 'string') {
                options.onClick = function() {
                    window.location = n.options.link;
                };
                options.duration = 10000;
            }

            $.depage.growl(n.title, options);
        },
        // }}}
        // {{{ handleTaskMessage
        handleTaskMessage: function(tasks) {
            var $wrappers = $(".task-progress");
            var percent = 0;
            var i, prop;

            for (prop in currentTasks) {
                currentTasks[prop] = false;
            }

            if (tasks.length > 0) {
                if (Object.keys(currentTasks).length == 0) {
                    $(".task-progress .task-list").text("");
                }
                $("#toolbarmain .task-progress").show();
            } else {
                localJS.cleanTaskProgress();
                $("#toolbarmain .task-progress").hide();
                $(".task-progress .task-list").text(locale.noCurrentTasks);

                return;
            }

            // render global progress
            // @todo keep number of tasks until back to zero
            percent = Math.round(tasks.reduce(function(a, task) { return a + task.percent; }, 0) / tasks.length);
            localJS.renderProgressFor($wrappers.children(".task-overview"), "global", "global", "", percent, "");

            // render local progress
            for (i = 0; i < tasks.length; i++) {
                var t = tasks[i];

                localJS.renderProgressFor($wrappers.children(".task-list"), t.id, t.name, t.project, t.percent, t.status);
            }

            localJS.cleanTaskProgress();

            clearTimeout(currentTasksTimeout);
            currentTasksTimeout = setTimeout(function() {
                localJS.cleanTaskProgress(true);
            }, 2500);
        },
        // }}}
        // {{{ renderProgressFor
        renderProgressFor: function($wrappers, taskId, name, project, percent, status) {
            var lastFrame = +(new Date()) - 100;

            $wrappers.each(function(i, wrapper) {
                var id = "task-progress-" + i + "-" + taskId;
                var $t = $("#" + id);
                var $b;

                currentTasks[id] = true;

                if ($t.length == 0) {
                    $t = $("<div id=\"" + id + "\"><strong></strong><progress max=\"100\" value=\"" + percent + "\"></progress><em></em><br /><em></em></div>").appendTo(wrapper);

                    if (taskId != "global") {
                        $b = $("<a class=\"button\"></a>")
                            .text(locale.delete)
                            .appendTo($t)
                            .depageShyDialogue({
                                ok: {
                                    title: locale.delete,
                                    classes: 'default',
                                    click: function(e) {
                                        $.ajax({
                                            async: true,
                                            type: 'POST',
                                            url: baseUrl + "api/-/task/delete/",
                                            data: { taskId: taskId }
                                        });

                                        return true;
                                    }
                                },
                                cancel: {
                                    title: locale.cancel
                                }
                            },{
                                title: locale.delete,
                                message : locale.deleteQuestion,
                                direction: 'LC',
                                directionMarker: true
                            });
                    }
                }

                var $p = $t.children("progress");
                var $pText = $t.children("strong");
                var targetP = percent;
                var lastP = parseFloat($p.attr("value"));

                $pText.text(lastP + "%");
                $p.attr("value", lastP);
                $t.children("em").eq(0).text(name);
                $t.children("em").eq(1).text(status);

                // @todo add different optics when task failed

                animLoop(function(deltaT, now) {
                    var timeDiff = now - lastFrame;
                    if (timeDiff > 1000) {
                        // run for maximal 1 second until next update from websocket server
                        return false;
                    }

                    var newP = lerp(lastP, targetP, timeDiff / 1000);
                    var newPfloored = Math.floor(newP);

                    if (newP - newPfloored < 1) {
                        newP = newPfloored;
                    }
                    if (lastP == newP) {
                        return;
                    }

                    $pText.text(newP + "%");
                    $p.attr("value", newP);
                });
            });
        },
        // }}}
        // {{{ cleanTaskProgress
        cleanTaskProgress: function(force) {
            for (var prop in currentTasks) {
                if (force || !currentTasks[prop]) {
                    $("#" + prop).remove();

                    delete currentTasks[prop];
                }
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
                var $finishButton = $("<a class=\"button\">" + locale.uploadFinishedCancel + "</a>").appendTo($box);

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
                // @todo add better solution instead of this locale hack
                layout = layout.replace(/Seiten editieren/, "edit-pages");
                layout = layout.replace(/Dateien/, "files");
                layout = layout.replace(/Farben/, "colors");
                layout = layout.replace(/ /, "-");
                $(".live-help-mock")
                    .hide()
                    .filter(".layout-" + layout)
                    .show();
            }
        },
        // }}}

        // {{{ addToolbarButton
        addToolbarButton: function($container, name, className, callback) {
            var $button = $("<a></a>");
            $button
                .text(name)
                .addClass("button")
                .addClass(className)
                .attr("title", name)
                .on("click", function() {
                    if (!$(this).hasClass("disabled")) callback.apply(this);
                });

            $button.appendTo($container);

            return $button;
        },
        // }}}

        // {{{ logout
        logout: function() {
            var logoutUrl = baseUrl + "logout/";

            $.ajax({
                type: "GET",
                url: logoutUrl,
                cache: false,
                username: "logout",
                password: "logout",
                complete: function(XMLHttpRequest, textStatus) {
                    window.location = baseUrl;
                },
                error: function() {
                    window.location = logoutUrl;
                }
            });
        }
        // }}}
    };

    return localJS;
})();

// {{{ register events
$(document).ready(function() {
    depageCMS.ready();
});
// }}}

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
