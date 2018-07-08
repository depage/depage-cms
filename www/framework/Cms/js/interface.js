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
    var locale = depageCMSlocale[lang];
    var baseUrl = $("base").attr("href");
    var projectName;
    var currentPreviewUrl,
        currentDocId,
        currentDocPropertyId,
        currentPreviewLang = "de";
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
            localJS.setupLibrary();
            localJS.setupDropTargets();
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
        },
        // }}}
        // {{{ setupHelp
        setupHelp: function() {
            $("#help").depageLivehelp({});
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

                    if ($parent.hasClass("edit-href") && data.element.href.indexOf("pageref://") === 0) {
                        $target.addClass("dnd-hover");
                    } else {
                        $("input.dnd-hover").removeClass("dnd-hover");
                    }
                })
                .on('dnd_stop.vakata.jstree', function (e, data) {
                    var $target = $(data.event.target);
                    var $parent = $target.parent().parent();

                    if ($parent.hasClass("edit-href") && data.element.href.indexOf("pageref://") === 0) {
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

            $libraryTreeContainer.on("activate_node.jstree", function(e, data) {
                var path = data.node.a_attr.href.replace(/libref:\/\//, "");

                localJS.loadLibraryFiles(path);
            });
            $libraryTreeContainer.jstree();
            //$libraryTreeContainer.depageTree();

            var $fileContainer = $(".files .file-list");
            var last = false;

            $fileContainer.on("click", "figure", function(e) {
                var $thumbs = $fileContainer.find("figure");
                var current = $thumbs.index(this);

                // @todo allow multiple select with ctrl and shift
                if (!e.metaKey && !e.ctrlKey && !e.shiftKey) {
                    $fileContainer.find(".selected").removeClass("selected");
                    last = false;
                }
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

                return false;
            });
            $fileContainer.on("dblclick", "figure", function(e) {
                var $ok = $(".dialog-full .dialog-bar .button.default");
                if ($ok.length == 1) {
                    $ok.click();
                }
            });
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
        // {{{ loadFileChooser
        loadFileChooser: function($input) {
            // @todo get path from input
            var path = $input[0].value.replace(/\/[^\/]*$/, '').replace(/^libref:\/\//, '');
            path = encodeURIComponent(path);
            var url = baseUrl + "project/" + projectName + "/library/manager/" + path + "/";

            var $dialogContainer = $("<div class=\"dialog-full\"><div class=\"content\"></div></div>")
                .appendTo($body);

            setTimeout(function() {
                $dialogContainer.addClass("visible");
            }, 10);

            $dialogContainer.children(".content").load(url + "?ajax=true", function() {
                $dialogContainer.on("click", function() {
                    localJS.removeFileChooser();
                });
                $dialogContainer.children(".content").on("click", function() {
                    return false;
                });
                var $dialogBar = $("<div class=\"dialog-bar\"></div>");
                var $ok = $("<a class=\"button default disabled\">" + locale.choose + "</a>").appendTo($dialogBar);
                var $cancel = $("<a class=\"button\">"+ locale.cancel + "</a>").appendTo($dialogBar);

                $ok.on("click", function() {
                    localJS.removeFileChooser($input);
                });
                $cancel.on("click", function() {
                    localJS.removeFileChooser();
                });

                $(document).on("keydown.depageFileChooser", function(e) {
                    var key = e.which;
                    if (key === 27) { // ESC
                        localJS.removeFileChooser();
                    } else if (key === 13) { // Enter
                        $ok.click();
                    }
                });

                $dialogBar.prependTo($dialogContainer.children(".content"));

                localJS.setupLibrary();
            });
        },
        // }}}
        // {{{ removeFileChooser()
        removeFileChooser: function($input) {
            var $dialogContainer = $(".dialog-full");
            var $selected = $dialogContainer.find("figure.selected");

            if (typeof $input !== 'undefined' && $selected.length > 0) {
                $input[0].value = $selected.attr("data-libref");
                $input.trigger("change");
            }

            $(document).off("keypress.depageFileChooser");

            $dialogContainer.removeClass("visible");
            setTimeout(function() {
                $dialogContainer.remove();
            }, 500);
        },
        // }}}
        // {{{ loadLibraryFiles
        loadLibraryFiles: function(path) {
            path = encodeURIComponent(path);
            var url = baseUrl + "project/" + projectName + "/library/files/" + path + "/";
            var $fileContainer = $(".files .file-list");

            $fileContainer.removeClass("loaded").load(url + "?ajax=true", function() {
                console.log("loaded");
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
                    $previewFrame.one("load", localJS.hightlighCurrentDocProperty);
                    $previewFrame[0].src = newUrl;
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
        // {{{ edit
        edit: function(projectName, page) {
            if (jstreePages) {
                $.ajax({
                    async: true,
                    type: 'POST',
                    url: baseUrl + "api/" + projectName + "/project/pageId/",
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
