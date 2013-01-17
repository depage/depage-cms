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
 *
 * @require framework/shared/jquery.json-2.2.js
 * @require framework/shared/jquery.gracefulWebSocket.js
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
            ],
            ui : {
                // TODO:
                "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
            },
            core : { 
                animation : 0,
                initially_open : ($(this).attr("data-open-nodes") || "").split(" ")
            },
            themes : {
                "theme" : "default",
                "url" : $(this).attr("data-theme")
            },
            // Plugin configuration
            deltaupdates : {
                "webSocketURL" : $(this).attr("data-delta-updates-websocket-url"),
                "fallbackPollURL" : $(this).attr("data-delta-updates-fallback-poll-url"),
                "postURL" : $(this).attr("data-delta-updates-post-url"),
            },
            hotkeys : {
                "del" : function () { 
                    this.delete_node(); 
                    return false;
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
                                var inst = $.jstree._reference(data.reference), 
                                    obj = inst.get_node(data.reference);
                                inst.edit(obj);
                            }
                        },
                        "remove" : {
                            "separator_before"  : false,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Delete",
                            "action"            : function (data) {
                                var inst = $.jstree._reference(data.reference),
                                    obj = inst.get_node(data.reference);
                                if(inst.data.ui && inst.is_selected(obj)) {
                                    obj = inst.get_selected();
                                }
                                inst.delete_node(obj);
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
                                        var inst = $.jstree._reference(data.reference),
                                            obj = inst.get_node(data.reference);
                                        if(data.ui && inst.is_selected(obj)) {
                                            obj = inst.get_selected();
                                        }
                                        inst.cut(obj);
                                    }
                                },
                                "copy" : {
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Copy",
                                    "action"            : function (data) {
                                        var inst = $.jstree._reference(data.reference),
                                            obj = inst.get_node(data.reference);
                                        if(data.ui && inst.is_selected(obj)) {
                                            obj = inst.get_selected();
                                        }
                                        inst.copy(obj);
                                    }
                                },
                                "paste" : {
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Paste",
                                    "action"            : function (data) {
                                        var inst = $.jstree._reference(data.reference),
                                            obj = inst.get_node(data.reference);
                                        inst.paste(obj);
                                    }
                                }
                            }
                        }
                    };

                    var create_menu = {};

                    // build the create menu based on the available nodes fetched in typesfromurl
                    if(typeof(this.get_settings) !== "undefined" &&
                        typeof(this.get_settings().typesfromurl.available_nodes) !== "undefined") {

                        var available_nodes = this.get_settings().typesfromurl.available_nodes;
                        var sub_menu = {};

                        $.each(available_nodes, function(type, node){
                            sub_menu[type] = {
                                "label"             : type,
                                "separator_before"  : false,
                                "separator_after"   : false,
                                "action"            : function (data) {
                                    $.jstree._reference(data.reference)
                                        .create_node(data.reference, type, 'after');
                                }
                            }
                        });

                        create_menu = {
                            "create" : {
                            "label"             : "Create",
                                "separator_before"  : false,
                                "separator_after"   : true,
                                "action"            : false,
                                "submenu"           : sub_menu
                            }
                        }
                    }

                    default_items = $.extend(create_menu, default_items);

                    return default_items;
                }
            }
        });
    });
});
