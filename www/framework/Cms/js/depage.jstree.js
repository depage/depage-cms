/**
 * @require framework/Cms/js/jstree/jstree.js
 * @require framework/Cms/js/jstree/jstree.contextmenu.js
 * @require framework/Cms/js/jstree/jstree.dnd.js
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

    var decodeEntities = (function() {
        // this prevents any overhead from creating the object each time
        var element = document.createElement('div');

        function decodeHTMLEntities (str) {
            if(str && typeof str === 'string') {
                // strip script/html tags
                str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
                element.innerHTML = str;
                str = element.textContent;
                element.textContent = '';
            }

            return str;
        }

        return decodeHTMLEntities;
    })();

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
                .on("ready.jstree", base.onReady)
                .on("rename_node.jstree", base.onRename)
                .on("delete_node.jstree", base.onDelete)
                .on("move_node.jstree", base.onMove)
                .on("copy_node.jstree", base.onCopy)
                .on("create_node.jstree", base.onCreate)
                .on("activate_node.jstree", base.onActivate)
                .on("refresh.jstree", base.onRefresh)
                .on("dblclick.jstree", ".jstree-anchor", base.onNodeDblClick);

            base.options.contextmenu.items = base.contextMenuItems;

            // init the tree
            jstree = base.$el.jstree(base.options).jstree(true);
        };
        // }}}

        // {{{ onReady
        base.onReady = $.proxy(function(e, param) {
            var nodeId = base.$el.attr("data-selected-nodes");
            var node = jstree.get_node(nodeId);

            if (node) {
                jstree.activate_node(node);
            }
        }, base);
        // }}}
        // {{{ onRename
        base.onRename = $.proxy(function(e, param) {
            if (param.text == param.old) {
                return;
            }
            xmldb.renameNode(param.node.data.nodeId, decodeEntities(param.text));
            // @todo updated page status in pg-meta element

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

            if (typeof prevId !== 'undefined') {
                xmldb.createNodeAfter(param.node.li_attr.newNodeId, prevId, base.afterCreate, param.node.li_attr.xmlTemplateData);
            } else if (typeof nextId !== 'undefined') {
                xmldb.createNodeBefore(param.node.li_attr.newNodeId, nextId, base.afterCreate, param.node.li_attr.xmlTemplateData);
            } else {
                jstree.open_node(parentId);
                xmldb.createNodeIn(param.node.li_attr.newNodeId, parentId, base.afterCreate, param.node.li_attr.xmlTemplateData);
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
                var node = jstree.get_node(nodeToActivate);
                if (!node) return;

                var nodeType = node.li_attr.rel;

                jstree.activate_node(node);
                jstree.open_node(node);
                if (nodeType == "pg:page" || nodeType == "pg:folder" || nodeType == "proj:folder" || nodeType == "proj:colorscheme") {
                    jstree.edit(node);
                }
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

                return;
            } else if ($(e.target).hasClass("hint")) {
                $target = $target.parent();
            }
            setTimeout(function() {
                jstree.edit($target);
            }, 50);

        }, base);
        // }}}

        // {{{ contextMenuItems
        base.contextMenuItems = function(o, cb) {
            var inst = jstree;

            var defaultItems = {
                create : {
                        "separator_after": true,
                        "label": locale.create,
                        "action": false,
                        "submenu": inst.getCreateMenu(inst, inst.getAvailableNodesFor(o), inst.insertCallback(o, "last"))
                },
                rename: {
                    "label": locale.rename,
                    "_disabled": function(data) {
                        var obj = inst.get_node(data.reference);

                        return !inst.check("rename_node", data.reference, inst.get_parent(data.reference), "");
                    },
                    "action": function (data) {
                        var obj = inst.get_node(data.reference);
                        inst.edit(obj);
                    }
                },
                duplicate: {
                    "label": locale.duplicate,
                    "action": function (data) {
                        var obj = inst.get_node(data.reference);

                        inst.copy_node(obj, obj, "after");
                    }
                },
                remove: {
                    "label": locale.delete,
                    "_disabled": function(data) {
                        var obj = inst.get_node(data.reference);

                        return !inst.check("delete_node", data.reference, inst.get_parent(data.reference), "");
                    },
                    "action": function (data) {
                        var obj = inst.get_node(data.reference);

                        inst.askDelete(obj);
                    }
                },
                cut: {
                    "label": locale.cut,
                    "separator_before": true,
                    "action": function (data) {
                        var obj = inst.get_node(data.reference);

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
                        var obj = inst.get_node(data.reference);

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
                        var obj = inst.get_node(data.reference);

                        inst.paste(obj);
                    }
                }
            };

            return defaultItems;
        };
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
            "dnd",
            "contextmenu",

            // custom plugins
            "focus",
            "toolbar",
            "nodeActions",
            "nodeTypes",
            "deltaUpdates",
        ],

        /**
         * Core
         */
        core: {
            animation : 100,
            multiple: false,
            dblclick_toggle: false,
            force_text: false,
            data: {
                url: function(node) {
                    var id = node.id != '#' ? node.id + '/' : '';
                    return this.element.attr("data-tree-url") + "nodes/" + id;
                },
            },
            keyboard: {
                'ctrl-space': function (e) {
                    // aria defines space only with Ctrl
                    e.type = "click";
                    $(e.currentTarget).trigger(e);
                },
                'enter': function (e) {
                    // enter
                    e.type = "click";
                    $(e.currentTarget).trigger(e);
                },
                'left': function (e) {
                    // left
                    e.preventDefault();
                    if(this.is_open(e.currentTarget)) {
                        this.close_node(e.currentTarget);
                    } else {
                        var o = this.get_parent(e.currentTarget);
                        if (o && o.id !== $.jstree.root) { this.get_node(o, true).children('.jstree-anchor').click(); }
                    }
                },
                'up': function (e) {
                    // up
                    e.preventDefault();
                    var o = this.get_prev_dom(e.currentTarget);
                    if (o && o.length) { o.children('.jstree-anchor').click(); }
                },
                'right': function (e) {
                    // right
                    e.preventDefault();
                    if(this.is_closed(e.currentTarget)) {
                            this.open_node(e.currentTarget, function (o) { this.get_node(o, true).children('.jstree-anchor').focus(); });
                    }
                    else if (this.is_open(e.currentTarget)) {
                            var o = this.get_node(e.currentTarget, true).children('.jstree-children')[0];
                            if(o) { $(this._firstChild(o)).children('.jstree-anchor').click(); }
                    }
                },
                'down': function (e) {
                    // down
                    e.preventDefault();
                    var o = this.get_next_dom(e.currentTarget);
                    if(o && o.length) { o.children('.jstree-anchor').click(); }
                },
                '*': function (e) {
                    // aria defines * on numpad as open_all - not very common
                    this.open_all();
                },
                'home': function (e) {
                    // home
                    e.preventDefault();
                    var o = this._firstChild(this.get_container_ul()[0]);
                    if(o) { $(o).children('.jstree-anchor').filter(':visible').click(); }
                },
                'end': function (e) {
                    // end
                    e.preventDefault();
                    this.element.find('.jstree-anchor').filter(':visible').last().click();
                },
                'delete': function (e) {
                    e.preventDefault();
                    this.askDelete(e.currentTarget);
                },
                'f2': function (e) {
                    // f2 - safe to include - if check_callback is false it will fail
                    e.preventDefault();
                    this.edit(e.currentTarget);
                }
            },
        },
        dnd: {
            inside_pos: "last",
            touch: "selected"
        },
        contextmenu: {
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
