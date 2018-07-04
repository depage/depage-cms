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

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];

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
        var nodeToActivate = false;

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
                .on("refresh.jstree", base.onRefresh)
                .on("dblclick.jstree", ".jstree-anchor", base.onNodeDblClick);

            // init the tree
            jstree = base.$el.jstree(base.options).jstree(true);
        };
        // }}}

        // {{{ onRename
        base.onRename = $.proxy(function(e, param) {
            if (param.text == param.old) {
                return;
            }
            xmldb.renameNode(param.node.data.nodeId, param.text);

            jstree.disable_node(param.node);
        }, base);
        // }}}
        // {{{ onDelete
        base.onDelete = $.proxy(function(e, param) {
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
        }, base);
        // }}}
        // {{{ onMove
        base.onMove = $.proxy(function(e, param) {
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
        }, base);
        // }}}
        // {{{ onCopy
        base.onCopy = $.proxy(function(e, param) {
            var originalId = param.original.data.nodeId;
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            if (typeof prevId !== 'undefined') {
                xmldb.copyNodeAfter(originalId, prevId, base.afterCreate);
            } else if (typeof nextId !== 'undefined') {
                xmldb.copyNodeBefore(originalId, nextId, base.afterCreate);
            } else {
                xmldb.copyNodeIn(originalId, parentId, base.afterCreate);
                jstree.open_node(parentId);
            }

            jstree.disable_node(param.node);
        }, base);
        // }}}
        // {{{ onCreate
        base.onCreate = $.proxy(function(e, param) {
            var nodeId = param.node.id;
            var prevId = jstree.get_node(jstree.get_prev_dom(nodeId, true)).id;
            var nextId = jstree.get_node(jstree.get_next_dom(nodeId, true)).id;
            var parentId = jstree.get_parent(nodeId);

            console.log(param);
            if (typeof prevId !== 'undefined') {
                xmldb.createNodeAfter(param.node.li_attr.rel, prevId, base.afterCreate, param.node.li_attr.xmlTemplateData);
            } else if (typeof nextId !== 'undefined') {
                xmldb.createNodeBefore(param.node.li_attr.rel, nextId, base.afterCreate, param.node.li_attr.xmlTemplateData);
            } else {
                xmldb.createNodeIn(param.node.li_attr.rel, parentId, base.afterCreate, param.node.li_attr.xmlTemplateData);
                jstree.open_node(parentId);
            }

            jstree.disable_node(param.node);
        }, base);
        // }}}
        // {{{ afterCreate
        base.afterCreate = $.proxy(function(data) {
            if (data.status) {
                nodeToActivate = data.id;
            }
        }, base);
        // }}}
        // {{{ onRefresh
        base.onRefresh = $.proxy(function(e, param) {
            if (nodeToActivate) {
                jstree.activate_node(jstree.get_node(nodeToActivate));
                nodeToActivate = false;
            }
        }, base);
        // }}}
        // {{{ onActivate
        base.onActivate = $.proxy(function(e, param) {
            nodeToActivate = false;
        }, base);
        // }}}
        // {{{ onNodeDblClick
        base.onNodeDblClick = $.proxy(function(e, param) {
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
        }, base);
        // }}}

        // go!
        base.init();
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
        plugins: [
            "ui",
            "dnd",
            "typesfromurl",
            "hotkeys",
            "contextmenu",
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
        ],

        /**
         * Plugin configuration
         */
        ui: {
            // @todo:
            "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
        },

        /**
         * Core
         */
        core: {
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
        dnd: {
            inside_pos: "last",
            touch: "selected"
        },

        /**
         * Hotkeys
         */
        disabled_keyboard: {
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
        contextmenu: {
            items: function (o, cb) {
                var defaultItems = {
                    rename: {
                        "label": locale.rename,
                        "_disabled": function(data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            return !inst.check("rename_node", data.reference, inst.get_parent(data.reference), "");
                        },
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);
                            inst.edit(obj);
                        }
                    },
                    duplicate: {
                        "label": locale.duplicate,
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            inst.copy_node(obj, obj, "after");
                        }
                    },
                    remove: {
                        "label": locale.delete,
                        "_disabled": function(data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            return !inst.check("delete_node", data.reference, inst.get_parent(data.reference), "");
                        },
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            inst.askDelete(obj);
                        }
                    },
                    cut: {
                        "label": locale.cut,
                        "separator_before": true,
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            if (inst.is_selected(obj)) {
                                inst.cut(inst.get_top_selected());
                            } else {
                                inst.cut(obj);
                            }
                        }
                    },
                    copy: {
                        "label": locale.copy,
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            if (inst.is_selected(obj)) {
                                inst.copy(inst.get_top_selected());
                            } else {
                                inst.copy(obj);
                            }
                        }
                    },
                    paste: {
                        "label": locale.paste,
                        "_disabled": function (data) {
                            return !$.jstree.reference(data.reference).can_paste();
                        },
                        "action": function (data) {
                            var inst = $.jstree.reference(data.reference),
                                obj = inst.get_node(data.reference);

                            inst.paste(obj);
                        }
                    }
                };

                return defaultItems;
            }
        },
    };
    // }}}

    $.fn.depageTree = function(options){
        return this.each(function(index){
            (new $.depage.jstree(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
