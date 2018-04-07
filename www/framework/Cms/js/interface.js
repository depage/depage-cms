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
    var baseUrl = $("base").attr("href");
    var projectName;
    var currentPreviewUrl,
        currentDocId,
        currentDocPropertyId;
    var $html;
    var $window;
    var $body;
    var $previewFrame;
    var $flashFrame;
    var $upload;
    var $toolbarLeft,
        $toolbarPreview,
        $toolbarRight;

    var $pageTreeContainer,
        $pagedataTreeContainer,
        $docPropertiesContainer;

    var currentLayout;
    var locale = depageCMSlocale[lang];

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

            $projects.on("depage.detail-opened", function(e, $head, $detail) {
                var project = $head.data("project");
                var projectNewsletter = $head.data("project-newsletter");
                var changesUrl;

                if (project) {
                    changesUrl = baseUrl + "project/" + project + "/details/15/?ajax=true";
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
                        message : locale.deleteQuestion
                    });
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
            $(".sortable-forms").each(function() {
                var currentPos, newPos;
                var $sortable = $(this);

                var $form = $(this).find("form");
                var xmldb = new DepageXmldb(baseUrl, $form.attr("data-project"), $form.attr("data-document"));

                $sortable.find(".sortable").each( function() {
                    var $container = $(this);
                    var $head = $(this).find("h1");
                    var $form = $(this).find("form");
                    var xmldb = new DepageXmldb(baseUrl, $form.attr("data-project"), $form.attr("data-document"));

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

                        $deleteButton.appendTo($form.find("p.submit"));
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
                            message : locale.deleteQuestion
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

                $form.on("depage.form.autoSaved", function() {
                    var matches = window.location.href.match(/project\/([^\/]*)\/newsletter\/([^\/]*)\//);
                    var lang = "de";
                    var url = baseUrl + "project/" + matches[1] + "/preview/newsletter/pre/" + lang + "/" + matches[2] + ".html";

                    localJS.preview(url);
                });
            });
        },
        // }}}
        // {{{ setupHelp
        setupHelp: function() {
            $("#help").depageLivehelp({});
        },
        // }}}
        // {{{ setupTrees
        setupTrees: function() {
            $pageTreeContainer = $(".tree.pages").addClass("focus");
            $pagedataTreeContainer = $(".tree.pagedata");
            $docPropertiesContainer = $(".doc-properties");

            $pageTreeContainer.on("click", function() {
                $pageTreeContainer.addClass("focus");
                $pagedataTreeContainer.removeClass("focus");
            });
            $pagedataTreeContainer.on("click", function() {
                $pageTreeContainer.removeClass("focus");
                $pagedataTreeContainer.addClass("focus");
            });

            localJS.loadPageTree();
        },
        // }}}

        // {{{ loadPageTree
        loadPageTree: function() {
            if ($pageTreeContainer.length === 0) return false;

            var $tree;
            var url = baseUrl + $pageTreeContainer.data("url");

            $pageTreeContainer.load(url + "?ajax=true", function() {
                $tree = $pageTreeContainer.children(".jstree-container");

                var jstree = $tree.depageTree()
                    .on("activate_node.jstree", function(e, data) {
                        localJS.loadPagedataTree(data.node.data.docRef);

                        // preview page
                        var lang = "de";
                        var url = baseUrl + "project/" + projectName + "/preview/html/pre/" + lang + data.node.data.url;

                        localJS.preview(url);
                    })
                    .jstree(true);

                jstree.activate_node($tree.find("ul:first li:first")[0]);
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
            var url = baseUrl + "project/" + projectName + "/tree/"+ docref + "/";

            $pagedataTreeContainer.load(url + "?ajax=true", function() {
                $tree = $pagedataTreeContainer.children(".jstree-container");

                var jstree = $tree.depageTree()
                    .on("activate_node.jstree", function(e, data) {
                        var nodeId = null;
                        if (typeof data.node.data.nodeId !== 'undefined') {
                            nodeId = data.node.data.nodeId;
                        }
                        localJS.loadDocProperties(nodeId);
                    })
                    .on("ready.jstree", function () {
                        $tree.find("ul:first li").each(function() {
                            jstree.open_node(this, false, false);
                        });
                    })
                    .jstree(true);

                jstree.activate_node($tree.find("ul:first li:first")[0]);
            });
        },
        // }}}
        // {{{ loadDocProperties
        loadDocProperties: function(nodeid) {
            if (currentDocPropertyId == nodeid) return false;

            currentDocPropertyId = nodeid;

            var url = baseUrl + "project/" + projectName + "/doc-properties/"+ nodeid + "/";

            $docPropertiesContainer.load(url + "?ajax=true", function() {
                var $form = $docPropertiesContainer.find('.depage-form');

                $form.depageForm();
                $form.find("p.submit").remove();

                // @todo add ui for editing table columns and rows
                // @todo keep squire from merging cells when deleteing at the beginning or end of cell
                // @todo add support for better handling of tab key to jump between cells

                localJS.hightlighCurrentDocProperty();

                $form.on("depageForm.autosaved", function() {
                    localJS.updatePreview();
                });
            });
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
                    $iframe.scrollTop($iframe.scrollTop() - 100);
                }
            } catch(error) {
            }
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
                $(".preview-buttons").css({display: "inline"});
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

                if (oldUrl == newUrl) {
                    $previewFrame[0].contentWindow.location.reload();
                } else {
                    var $newFrame = $("<iframe />").insertAfter($previewFrame);
                    $previewFrame.remove();
                    $previewFrame = $newFrame.attr("id", "previewFrame");
                    $previewFrame[0].src = newUrl;
                    //$previewFrame.on("load", localJS.hightlighCurrentDocProperty);
                }
                currentPreviewUrl = newUrl;

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
                    //$previewFrame.on("load", localJS.hightlighCurrentDocProperty);
                    $previewFrame[0].src = unescape(url);

                    $window.triggerHandler("switchLayout", "split");
                });
            }
        },
        // }}}
        // {{{ updatePreview
        updatePreview: _.throttle(function() {
            this.preview(currentPreviewUrl);
        }, 1000),
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
                    $flashFrame[0].src = "project/" + projectName + "/flash/flash/false/" + encodeURIComponent(page);
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

// {{{ registeroevents
$(document).ready(function() {
    depageCMS.ready();
});
// }}}

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
