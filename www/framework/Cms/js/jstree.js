/**
 * @require framework/Cms/js/depage.jstree.js
 *
 * Global function allows tree to be init with required options
 *
 * TODO refactor and namespacing of global func
 *
 * @param tree
 *
 */
function init_tree() {

    /**
     * Init depage tree
     */
    $(this).depageTree({
        /**
         * Plugins
         *
         * The list of plugins to include
         */
        plugins : [
            //"select_created_nodes",
            //"pedantic_html_data", // @todo check if still needed
            //"dnd_placeholder", // @todo check dnd vs dnd_placeholder

            "themes",
            "ui",
            "dnd",
            "typesfromurl",
            "hotkeys",
            "contextmenu",
            "nodeinfo",
            "dblclickrename",
            "tooltips",
            "add_marker",
            "deltaupdates",
            "toolbar",

        /**
         * custom doctype handlers
         */
            "doctype_page"
        ],

        /**
         * Plugin configuration
         */
        ui : {
            // TODO:
            "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
        },

        /**
         * Core
         */
        core : {
            animation : 0,
            initially_open : ($(this).attr("data-open-nodes") || "").split(" "),
            copy_node : function() {alert('hello');}
        },

        /**
         * Themes
         */
        themes : {
            "theme" : "default",
            "url" : $(this).attr("data-theme")
        },

        /**
         * Delta Updates
         */
        deltaupdates : {
            "webSocketURL" : $(this).attr("data-delta-updates-websocket-url"),
            "fallbackPollURL" : $(this).attr("data-delta-updates-fallback-poll-url"),
            "postURL" : $(this).attr("data-delta-updates-post-url")
        },

        /**
         * Hotkeys
         */
        hotkeys : {
            "up" : function() {
                $.depage.jstree.keyUp.apply(this);
            },
            "ctrl+up" : function () {
                $.depage.jstree.keyUp.apply(this);
                return false;
            },
            "shift+up" : function () {
                $.depage.jstree.keyUp.apply(this);
                return false;
            },
            "down" : function(){
                $.depage.jstree.keyDown.apply(this);
                return false;
            },
            "ctrl+down" : function () {
                $.depage.jstree.keyDown.apply(this);
                return false;
            },
            "shift+down" : function () {
                $.depage.jstree.keyDown.apply(this);
                return false;
            },
            "left" : function () {
                $.depage.jstree.keyLeft.apply(this);
                return false;
            },
            "ctrl+left" : function () {
                $.depage.jstree.keyLeft.apply(this);
                return false;
            },
            "shift+left" : function () {
                $.depage.jstree.keyLeft.apply(this);
                return false;
            },
            "right" : function () {
                $.depage.jstree.keyRight.apply(this);
                return false;
            },
            "ctrl+right" : function () {
                $.depage.jstree.keyRight.apply(this);
                return false;
            },
            "shift+right" : function () {
                $.depage.jstree.keyRight.apply(this);
                return false;
            },
            "del" : function () {
                var node = $(this.data.ui.selected[0] || this.data.ui.hovered[0]);

                var offset = node.offset();

                $depageTree = $.depage.jstree;

                $depageTree.confirmDelete(offset.left, offset.top, function(){
                    $depageTree.contextDelete(node);
                });
            },
            "return" : function() {
                // @todo bind enter key to prevent default so that we dont leave input on enter
                var node = this;
                setTimeout(function () { node.edit(); }, 300);
                return false;
            }
        },

        /**
         * Context Menu
         */
        contextmenu : {
            items : function (obj) {

                var default_items = {
                    "rename" : {
                        "_disabled"         : !this.check('rename_node', obj, this.get_parent()),
                        "separator_before"  : false,
                        "separator_after"   : false,
                        "label"             : "Rename",
                        "action"            : function (data) {
                            $.depage.jstree.contextRename(data);
                        }
                    },
                    "remove" : {
                        "_disabled"          : !this.check('delete_node', obj, this.get_parent()),
                        "separator_before"  : false,
                        "icon"              : false,
                        "separator_after"   : false,
                        "label"             : "Delete",
                        "action"            : function (data) {
                            $.depage.jstree.contextDelete(data);
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
                                "_disabled"         : !this.check('cut_node', obj, this.get_parent()),
                                "separator_before"  : false,
                                "separator_after"   : false,
                                "label"             : "Cut",
                                "action"            : function (data) {
                                    $depageTree = $.depage.jstree.contextCut(data);
                                }
                            },
                            "copy" : {
                                "_disabled"         : !this.check('copy_node', obj, this.get_parent()),
                                "separator_before"  : false,
                                "icon"              : false,
                                "separator_after"   : false,
                                "label"             : "Copy",
                                "action"            : function (data) {
                                    $depageTree = $.depage.jstree.contextCopy(data);
                                }
                            },
                            "paste" : {
                                "separator_before"  : false,
                                "icon"              : false,
                                "separator_after"   : false,
                                "label"             : "Paste",
                                "_disabled"         : typeof(this.can_paste) === "undefined" ? false : !(this.can_paste()),
                                "action"            : function (data) {
                                    $depageTree = $.depage.jstree.contextPaste(data);
                                }
                            }
                        }
                    }
                };

                // add the create menu based on the available nodes fetched in typesfromurl
                if(typeof(this.get_settings()['typesfromurl']) !== "undefined") {

                    var type_settings = this.get_settings()['typesfromurl'];

                    var type = obj.attr(type_settings.type_attr);
                    var available_nodes = type_settings.valid_children[type];

                    default_items = $.extend($depageTree = $.depage.jstree.buildCreateMenu(available_nodes), default_items);

                } else {
                    // TODO default create menu
                }

                return default_items;
            }
        },

        /**
         * Toolbar
         */
        toolbar : {
            items : function(obj) {
                return {
                    "create" : {
                        "label"             : "Create",
                        "separator_before"  : false,
                        "separator_after"   : true,
                        "_disabled"         : !this.check('create_node', obj, this.get_parent()),
                        "action"            : function(obj, top, left) {

                            var node = $(".jstree-clicked");

                            var data = {
                                "reference" : node,
                                "element"   : node,
                                position    : {
                                    "x"     : left,
                                    "y"     : top
                                }
                            };

                            if (data.reference.length) {
                                var inst = $.jstree._reference(data.reference);

                                // build the create menu based on the available nodes fetched in typesfromurl

                                if(typeof(inst.get_settings()['typesfromurl']) !== "undefined") {

                                    var type_settings = inst.get_settings()['typesfromurl'];

                                    var type = data.reference.parent().attr(type_settings.type_attr);
                                    var available_nodes = type_settings.valid_children[type];

                                    var create_menu = $.depage.jstree.buildCreateMenu(available_nodes);

                                    $.vakata.context.show(data.reference, data.position, create_menu.create.submenu);

                                } else {
                                    // TODO default create menu
                                }
                            }
                        }
                    },
                    "remove" : {
                        "label"             : "Delete",
                        "_disabled"         : !this.check('delete_node', obj, this.get_parent()),
                        "action"            : function () {
                            var data = { "reference" : $(".jstree-clicked") };
                            if (data.reference.length) {
                                $.depage.jstree.contextDelete(data);
                            }
                        }
                    },
                    "duplicate" : {
                        "label"             : "Duplicate",
                        "_disabled"         : !this.check('duplicate_node', obj, this.get_parent()),
                        "action"            : function () {
                            var obj = $(".jstree-clicked").parent("li");
                            if (obj.length){
                                var inst = $.depage.jstree._reference(obj);

                                var data = { "reference" : obj };

                                $.depage.jstree.contextDuplicate(data);
                            }
                        }
                    }
                }
            }
        }
    });
}

$(function($){

    $('.jstree-container').each(function(){
        init_tree.apply(this);
    });

    /**
     * When a new doc type is loaded replace the sub tree with the new doc type tree
     */
    $('.jstree-container').bind('doc_load.jstree', function(e, data){
        init_tree.apply(data);
        $('#doc-tree .jstree-container').replaceWith(data);
    });

});
