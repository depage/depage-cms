/**
 * @require framework/Cms/js/jstree/jstree.js
 * @require framework/Cms/js/jstree/jstree.changed.js
 * @require framework/Cms/js/jstree/jstree.conditionalselect.js
 * @require framework/Cms/js/jstree/jstree.contextmenu.js
 * @require framework/Cms/js/jstree/jstree.dnd.js
 * @require framework/Cms/js/jstree/jstree.massload.js
 * @require framework/Cms/js/jstree/jstree.search.js
 * @require framework/Cms/js/jstree/jstree.sort.js
 * @require framework/Cms/js/jstree/jstree.state.js
 * @require framework/Cms/js/jstree/jstree.focus.js
 * @require framework/Cms/js/jstree/jstree.toolbar.js
 * @require framework/Cms/js/jstree/jstree.nodeActions.js
 * @require framework/Cms/js/jstree/jstree.nodeTypes.js
 * @require framework/Cms/js/jstree/jstree.deltaUpdates.js
 * @require framework/Cms/js/jstree/jstree.types.js
 * @require framework/Cms/js/jstree/jstree.unique.js
 * @require framework/Cms/js/jstree/vakata-jstree.js
 *
 * @file    depage-jstree
 *
 * Depage jstree - wraps the jstree in the depage namespace adding custom configuration and functionality.
 *
 * @copyright (c) 2006-2018 Frank Hellenkamp [jonas@depage.net]
 *
 * @author Frank Hellenkamp
 * @author Ben Wallis
 */
(function($){

    if(!$.depage){
        $.depage = {};
    }

    /**
     * jstree
     *
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.jstree = function(el, index, options) {
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        base.projectName = "";
        base.docName = "";

        var baseUrl = $("base").attr("href");
        var xmldb;
        var jstree;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.jstree", base);

        // {{{ init()
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.jstree.defaultOptions, options);

            xmldb = new DepageXmldb(baseUrl, base.$el.data("projectname"), base.$el.data("docname"));

            base.$el
                .on("rename_node.jstree", base.onRename)
                .on("delete_node.jstree", base.onDelete)
                .on("move_node.jstree", base.onMove)
                .on("copy_node.jstree", base.onCopy)
                .on("create_node.jstree", base.onCreate)
                .on("activate_node.jstree", base.onActivate)
                .on("dblclick.jstree", ".jstree-anchor", base.onNodeDblClick);

            // init the tree
            jstree = base.$el.jstree(base.options).jstree(true);
        };
        // }}}

        // {{{ onRename
        base.onRename= function(e, param) {
            if (param.text == param.old) {
                return;
            }
            xmldb.renameNode(param.node.data.nodeId, param.text);

            jstree.disable_node(param.node);
        };
        // }}}
        // {{{ onDelete
        base.onDelete = function(e, param) {
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            // @todo add dialog to make sure you want to delete node
            // @todo select sibling/parent after node is deleted
            xmldb.deleteNode(param.node.data.nodeId);

            if (typeof prevId !== 'undefined') {
                jstree.activate_node(prevId);
            } else if (typeof nextId !== 'undefined') {
                jstree.activate_node(nextId);
            } else {
                jstree.activate_node(parentId);
            }
        };
        // }}}
        // {{{ onMove
        base.onMove = function(e, param) {
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            if (typeof prevId !== 'undefined') {
                xmldb.moveNodeAfter(nodeId, prevId);
            } else if (typeof nextId !== 'undefined') {
                xmldb.moveNodeBefore(nodeId, nextId);
            } else {
                xmldb.moveNodeIn(nodeId, parentId);
                jstree.open_node(parentId);
            }

            jstree.disable_node(param.node);
        };
        // }}}
        // {{{ onCopy
        base.onCopy = function(e, param) {
            var originalId = param.original.data.nodeId;
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            if (typeof prevId !== 'undefined') {
                xmldb.copyNodeAfter(originalId, prevId);
            } else if (typeof nextId !== 'undefined') {
                xmldb.copyNodeBefore(originalId, nextId);
            } else {
                xmldb.copyNodeIn(originalId, parentId);
                jstree.open_node(parentId);
            }

            jstree.disable_node(param.node);
        };
        // }}}
        // {{{ onCreate
        base.onCreate = function(e, param) {
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            if (typeof prevId !== 'undefined') {
                xmldb.createNodeAfter(param.node.li_attr.rel, prevId);
            } else if (typeof nextId !== 'undefined') {
                xmldb.createNodeBefore(param.node.li_attr.rel, nextId);
            } else {
                xmldb.createNodeIn(param.node.li_attr.rel, parentId);
                jstree.open_node(parentId);
            }

            jstree.disable_node(param.node);
        };
        // }}}
        // {{{ onActivate
        base.onActivate = function(e, param) {
        };
        // }}}
        // {{{ onNodeDblClick
        base.onNodeDblClick = function(e, param) {
            var $target = $(e.target);
            var $label;

            if ($(e.target).hasClass("jstree-icon")) {
                jstree.toggle_node(e.target);
            } else if ($(e.target).hasClass("jstree-anchor")) {
                $target.find(".hint").remove();
                jstree.edit($target, $target.text());
            } else if ($(e.target).hasClass("hint")) {
                $label = $target.parent();
                $target.remove();
                jstree.edit($label, $label.text());
            }
        };
        // }}}

        // go!
        base.init();
    };
    // }}}

    // {{{ buildCreateMenu()
    /**
     *
     * @param available_nodes
     * @param position
     * @return {Object}
     */
    $.depage.jstree.buildCreateMenu = function (available_nodes, position){

        available_nodes = available_nodes || {};
        position = position || 'inside';

        var sub_menu = {};

        $.each(available_nodes, function(type, node){
            sub_menu[type] = {
                "label"             : node.name,
                "separator_before"  : false,
                "separator_after"   : false,
                "action"            : function (data) {
                    $.depage.jstree.contextCreate(data, type, position);
                }
            };
        });

        var create_menu = {
            "create" : {
                "_disabled"         : !$(available_nodes).size(),
                "label"             : "Create",
                "separator_before"  : false,
                "separator_after"   : true,
                "action"            : false,
                "submenu"           : sub_menu
            }
        };

        return create_menu;
    };
    // }}}

    // {{{ contextDelete()
    /**
     * contextDelete
     */
    $.depage.jstree.contextDelete = function(data) {
        var offset = data.reference.offset();

        $.depage.jstree.confirmDelete(offset.left, offset.top, function() {
            var inst = $.jstree._reference(data.reference);

            if (inst) {
                var obj = inst.get_node(data.reference);
                if(inst.data.ui && inst.is_selected(obj)) {
                    obj = inst.get_selected();
                }
                inst.delete_node(obj);
            }
        });
    };
    // }}}

    // {{{ confirmDelete()
    /**
     * confirmDelete
     *
     * @param left
     * @param top
     * @param delete_callback
     */
    $.depage.jstree.confirmDelete = function(left, top, delete_callback) {
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
    // }}}

    // defaultOptions {{{
    /**
     * Default Options
     *
     * @var object
     */
    $.depage.jstree.defaultOptions = {
        /**
         * Plugins
         *
         * The list of plugins to include
         */
        plugins : [
            "ui",
            "dnd",
            "typesfromurl",
            "hotkeys",
            //"contextmenu",
            "nodeinfo",
            "dblclickrename",
            "tooltips",
            "add_marker",

            // custom plugins
            "focus",
            "toolbar",
            "nodeActions",
            "nodeTypes",
            "deltaUpdates",

            // custom doctype handlers
            // @todo get doctype handler from data-attributes
            "doctype_page"
        ],

        /**
         * Plugin configuration
         */
        ui : {
            // @todo:
            "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
        },

        /**
         * Core
         */
        core : {
            animation : 100,
            multiple: false,
            dblclick_toggle: false,
            data: {
                url: function(node) {
                    var id = node.id != '#' ? node.id + '/' : '';
                    return this.element.attr("data-tree-url") + "nodes/" + id;
                },
            },
            initially_open : ($(this).attr("data-open-nodes") || "").split(" "),
            check_callback : function (operation, node, node_parent, node_position, more) {
                // @todo check types and operations
                // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node', 'copy_node' or 'edit'
                // in case of 'rename_node' node_position is filled with the new node name
                if (node.li_attr.rel == 'pg:meta') {
                    return false;
                } else if ((operation == "move_node" || operation == "copy_node") && typeof node_parent.li_attr != 'undefined' && (node_parent.li_attr.rel == 'pg:meta' || node_parent.li_attr.rel == 'sec:separator')) {
                    return false;
                } else if ((operation == "edit" || operation == "create_node") && node.li_attr.rel == 'sec:separator') {
                    return false;
                }

                return true;
            }
        },

        /**
         * Drag and drop
         */
        dnd : {
            inside_pos: "last",
            touch: "selected"
        },

        /**
         * Hotkeys
         */
        disabled_keyboard : {
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
        disabled_contextmenu : {
            items : function (obj) {

                var default_items = {
                    "rename" : {
                        "_disabled"         : !this.check('rename_node', obj, this.get_parent()),
                        "separator_before"  : false,
                        "separator_after"   : false,
                        "label"             : "Rename",
                        "action"            : function (data) {
                            console.log(data);
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
                /*
                if(typeof(this.get_settings()['typesfromurl']) !== "undefined") {

                    var type_settings = this.get_settings()['typesfromurl'];

                    var type = obj.attr(type_settings.type_attr);
                    var available_nodes = type_settings.valid_children[type];

                    default_items = $.extend($depageTree = $.depage.jstree.buildCreateMenu(available_nodes), default_items);

                } else {
                    // @todo default create menu
                }
                */

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
                                    // @todo default create menu
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
                };
            }
        }
    };
    // }}}

    $.fn.depageTree = function(options){
        return this.each(function(index){
            (new $.depage.jstree(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
