/*
 * @require framework/cms/js/jstree/vakata.js
 * @require framework/cms/js/jstree/jstree.js
 * @require framework/cms/js/jstree/jstree.themes.js
 * @require framework/cms/js/jstree/jstree.ui.js
 * @require framework/cms/js/jstree/jstree.dnd.js
 * @require framework/cms/js/jstree/jstree.hotkeys.js
 * @require framework/cms/js/jstree/jstree.nodeinfo.js
 * @require framework/cms/js/jstree/jstree.tooltips.js
 * @require framework/cms/js/jstree/jstree.typesfromurl.js
 * @require framework/cms/js/jstree/jstree.contextmenu.js
 * @require framework/cms/js/jstree/jstree.dblclickrename.js
 * @require framework/cms/js/jstree/jstree.deltaupdates.js
 * @require framework/cms/js/jstree/jstree.pedantic_html_data.js
 * @require framework/cms/js/jstree/jstree.toolbar.js
 *
 * @require framework/shared/jquery.json-2.2.js
 * @require framework/shared/jquery.gracefulWebSocket.js
 *
 */
"use strict";

$(function () {
    $(".jstree-container").each(function () {
        $(this).jstree({
            // the list of plugins to include
            plugins : [
                "themes",
                //"pedantic_html_data", // @todo check if still needed
                "ui",
                "dnd",
                //"dnd_placeholder", // @todo check dnd vs dnd_palceholder
                "typesfromurl",
                "hotkeys",
                "contextmenu",
                "nodeinfo",
                "dblclickrename",
                "tooltips",
                //"select_created_nodes",
                //"add_marker",
                "deltaupdates",
                "toolbar"
            ],
            ui : {
                // TODO:
                "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
            },
            core : { 
                animation : 0,
                initially_open : ($(this).attr("data-open-nodes") || "").split(" "),
                copy_node : function() {alert('hello');}
            },
            themes : {
                "theme" : "default",
                "url" : $(this).attr("data-theme")
            },
            // Plugin configuration
            deltaupdates : {
                "webSocketURL" : $(this).attr("data-delta-updates-websocket-url"),
                "fallbackPollURL" : $(this).attr("data-delta-updates-fallback-poll-url"),
                "postURL" : $(this).attr("data-delta-updates-post-url")
            },
            hotkeys : {
                "up" : function() {
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var prev = this.get_prev(o);
                    if (prev.length) {
                        this.deselect_node(o);
                        this.select_node(prev);
                    }
                    return false;
                },
                "ctrl+up" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var prev = this.get_prev(o);
                    if (prev.length) {
                        this.deselect_node(o);
                        this.select_node(prev);
                    }
                    return false;
                },
                "shift+up" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var prev = this.get_prev(o);
                    if (prev.length) {
                        this.deselect_node(o);
                        this.select_node(prev);
                    }
                    return false;
                },
                "down" : function(){
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var next = this.get_next(o);
                    if (next.length) {
                        this.deselect_node(o);
                        this.select_node(next);
                    }
                    return false;
                },
                "ctrl+down" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var next = this.get_next(o);
                    if (next.length) {
                        this.deselect_node(o);
                        this.select_node(next);
                    }
                    return false;
                },
                "shift+down" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected || -1;

                    var next = this.get_next(o);
                    if (next.length) {
                        this.deselect_node(o);
                        this.select_node(next);
                    }
                    return false;
                },
                "left" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;
                    if(o) {
                        if(o.hasClass("jstree-open")) {
                            this.close_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_prev(o));
                        }
                    }
                    return false;
                },
                "ctrl+left" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;
                    if(o) {
                        if(o.hasClass("jstree-open")) {
                            this.close_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_prev(o));
                        }
                    }
                    return false;
                },
                "shift+left" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;
                    if(o) {
                        if(o.hasClass("jstree-open")) {
                            this.close_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_prev(o));
                        }
                    }
                    return false;
                },
                "right" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;

                    if(o && o.length) {
                        if(o.hasClass("jstree-closed")) {
                            this.open_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_next(o));
                        }
                    }
                    return false;
                },
                "ctrl+right" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;

                    if(o && o.length) {
                        if(o.hasClass("jstree-closed")) {
                            this.open_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_next(o));
                        }
                    }
                    return false;
                },
                "shift+right" : function () {
                    var o = this.data.ui.hovered || this.data.ui.last_selected;

                    if(o && o.length) {
                        if(o.hasClass("jstree-closed")) {
                            this.open_node(o);
                        }
                        else {
                            this.deselect_node(o);
                            this.select_node(this.get_next(o));
                        }
                    }
                    return false;
                },
                "del" : function () {
                    var node = $(this.data.ui.selected[0] || this.data.ui.hovered[0]);

                    var offset = node.offset();

                    $.jstree.confirmDelete(offset.left, offset.top, function(){
                        $.jstree.contextDelete(node);
                    });
                },
                "return" : function() {
                    // @todo bind enter key to prevent default so that we dont leave input on enter
                    var node = this;
                    setTimeout(function () { node.edit(); }, 300);
                    return false;
                }
            },
            contextmenu : {
                items : function (obj) {
                    var default_items = { // Could be a function that should return an object like this one
                        "rename" : {
                            "separator_before"  : false,
                            "separator_after"   : false,
                            "label"             : "Rename",
                            "action"            : function (data) {
                                $.jstree.contextRename(data);
                            }
                        },
                        "remove" : {
                            "separator_before"  : false,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Delete",
                            "action"            : function (data) {
                                $.jstree.contextDelete(data);
                            }
                        },
                        "ccp" : {
                            "separator_before"  : true,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Edit",
                            "action"            : false,
                            "submenu" : { 
                                "cut" : {
                                    "separator_before"  : false,
                                    "separator_after"   : false,
                                    "label"             : "Cut",
                                    "action"            : function (data) {
                                        $.jstree.contextCut(data);
                                    }
                                },
                                "copy" : {
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Copy",
                                    "action"            : function (data) {
                                        $.jstree.contextCopy(data);
                                    }
                                },
                                "paste" : {
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Paste",
                                    "_disabled"         : typeof(this.can_paste) === "undefined" ? false : !(this.can_paste()),
                                    "action"            : function (data) {
                                        $.jstree.contextPaste(data);
                                    }
                                }
                            }
                        }
                    };

                    // build the create menu based on the available nodes fetched in typesfromurl
                    if(typeof(this.get_settings) !== "undefined" &&
                        typeof(this.get_settings().typesfromurl.available_nodes) !== "undefined") {

                        var available_nodes = this.get_settings().typesfromurl.available_nodes;

                        default_items = $.extend($.jstree.buildCreateMenu(available_nodes), default_items);
                    }

                    return default_items;
                }
            },
            toolbar : {
                items : {
                    "create" : {
                        "label"             : "Create",
                        "separator_before"  : false,
                        "separator_after"   : true,
                        "action"            : function(obj) {

                            var node = $(".jstree-clicked");
                            var offset = $(this).offset();

                            var data = {
                                "reference" : node,
                                "element"   : node,
                                position    : {
                                    "x"     : offset.left,
                                    "y"     : offset.top
                                }
                            };

                            if (data.reference.length) {
                                var inst = $.jstree._reference(data.reference);

                                // build the create menu based on the available nodes fetched in typesfromurl
                                if(typeof(inst.get_settings) !== "undefined" &&
                                    typeof(inst.get_settings().typesfromurl.available_nodes) !== "undefined") {

                                    var available_nodes = inst.get_settings().typesfromurl.available_nodes;
                                    var create_menu = $.jstree.buildCreateMenu(available_nodes);
                                }

                                $.vakata.context.show(data.reference, data.position, create_menu.create.submenu);
                            }
                        }
                    },
                    "remove" : {
                        "label"             : "Delete",
                        "action"            : function () {
                            var data = { "reference" : $(".jstree-clicked") };
                            if (data.reference.length) {
                                $.jstree.contextDelete(data);
                            }
                        }
                    },
                    "duplicate" : {
                        "label"             : "Duplicate",
                        "action"            : function () {
                            var obj = $(".jstree-clicked").parent("li");
                            if (obj.length){
                                var inst = $.jstree._reference(obj);

                                var data = { "reference" : obj };

                                $.jstree.contextCopy(data);

                                data.reference = inst.get_parent(obj);

                                $.jstree.contextPaste(data, obj.index() + 1);
                            }
                        }
                    }
                }
        }
        })
    });

    // TODO can these extensions be scoped better?

    $.jstree.buildCreateMenu = function (available_nodes){
        var sub_menu = {};

        $.each(available_nodes, function(type, node){
            sub_menu[type] = {
                "label"             : node.name,
                "separator_before"  : false,
                "separator_after"   : false,
                "action"            : function (data) {
                    $.jstree.contextCreate(data, type);
                }
            }
        });

        var create_menu = {
            "create" : {
                "label"             : "Create",
                "separator_before"  : false,
                "separator_after"   : true,
                "action"            : false,
                "submenu"           : sub_menu
            }
        }

        return create_menu;
    };

    $.jstree.contextDelete = function(data) {
        var offset = data.reference.offset();

        $.jstree.confirmDelete(offset.left, offset.top, function() {
            var inst = $.jstree._reference(data.reference),
                obj = inst.get_node(data.reference);
            if(inst.data.ui && inst.is_selected(obj)) {
                obj = inst.get_selected();
            }
            inst.delete_node(obj);
        });
    };

    $.jstree.contextCut = function(data) {
        var inst = $.jstree._reference(data.reference);

        if (inst) { // TODO why null?
            var obj = inst.get_node(data.reference);
            if(data.ui && inst.is_selected(obj)) {
                obj = inst.get_selected();
            }
            inst.cut(obj);
        }
    };

    $.jstree.contextCreate = function(data, type) {
        var inst = $.jstree._reference(data.reference);
        var obj = inst.create_node(data.reference, type, 'inside');

        // focus for edit
        inst.edit(obj);
    };

    $.jstree.contextCopy = function(data) {
        var inst = $.jstree._reference(data.reference);
        if (inst){ // TODO why null?
            var obj = inst.get_node(data.reference);
            if(inst.is_selected(obj)) {
                obj = inst.get_selected();
            }
            inst.copy(obj);
        }
    };

    $.jstree.contextPaste = function(data, pos) {
        pos = pos || "after";
        var inst = $.jstree._reference(data.reference);
        var obj = inst.get_node(data.reference);

        inst.paste(obj, pos);
    };

    $.jstree.contextRename = function(data) {
        var inst = $.jstree._reference(data.reference);
        var obj = inst.get_node(data.reference);
        inst.edit(obj);
    };

    $.jstree.confirmDelete = function(left, top, delete_callback) {
        // setup confirm on the delete context menu using shy-dialogue
        var buttons = {
            yes: {
                click: function(e) {
                    e.stopImmediatePropagation();
                    delete_callback();
                    $("#node_1").data('depage.shyDialogue').hide();
                    return false;
                }
            },
            no : false
        };

        $("#node_1").depageShyDialogue(
            buttons, {
                title: "Delete?",
                message: "Are you sure you want to delete this menu item?",
                bind_el: false // show manually
            });

        // prevent the click event hiding the menu
        $(document).bind("click.marker", function(e) {
            e.stopImmediatePropagation();
            return false;
        });


        $("#node_1").data('depage.shyDialogue').showDialogue(left, top);
    };
});
