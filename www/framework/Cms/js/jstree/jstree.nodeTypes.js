/**
* ### nodeTypes plugin
*
* Adds nodeTypes functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.nodeTypes', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.nodeTypes) { return; }

    var unique = function(ar) {
        var j = {};

        ar.forEach( function(v) {
            j[v.name] = v;
        });

        return Object.keys(j).map(function(v){
            return j[v];
        });
    };

    /**
     * nodeTypes configuration
     *
     * @name $.jstree.defaults.nodeTypes
     * @plugin nodeTypes
     */
    $.jstree.defaults.nodeTypes = null;
    $.jstree.plugins.nodeTypes = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            var className = "jstree-node-actions";
            this._data.nodeTypes = {};
            parent.init.call(this, el, options);

            var $tree = this.element;
            try {
                var settings = JSON.parse($tree.attr("data-tree-settings"));
                $.extend(this._data.nodeTypes, settings);
            } catch (e) {
                // continue
                return true;
            }
            this._data.nodeTypes.inst = this.element.jstree(true);

            // override default check_callback
            this.settings.core.check_callback = this.checkTypes;
        };
        // }}}
        // {{{ getAvailableNodesFor()
        this.getAvailableNodesFor = function(node) {
            var nodeType = this.getNodeType(node);
            var nodeNames = Object.keys(this._data.nodeTypes.validParents);
            var available = [];

            for (var i = 0; i < nodeNames.length; i++) {
                var nodeName = nodeNames[i];
                if (this._data.nodeTypes.validParents[nodeName].indexOf(nodeType) != -1 || 
                    this._data.nodeTypes.validParents[nodeName].indexOf('*') != -1 || 
                    (
                        typeof this._data.nodeTypes.validParents['*'] != 'undefined' &&
                            (this._data.nodeTypes.validParents['*'].indexOf(nodeType) != -1 ||
                            this._data.nodeTypes.validParents['*'].indexOf('*') != -1)
                    )
                ) {
                    available.push(this._data.nodeTypes.availableNodes[nodeName]);
                }
            }

            return unique(available);
        };
        // }}}
        // {{{ createInsertCallbackInside()
        this.insertCallback = function(node, pos) {
            // pos is "inside", "before" or "after"
            var inst = this._data.nodeTypes.inst;

            return function(data) {
                var newNode = {
                    text: data.item.newName,
                    li_attr: {
                        rel: data.item.nodeName,
                        xmlTemplate: data.item.xmlTemplate
                    },
                };
                inst.create_node(node, newNode, pos);
            };
        };
        // }}}
        // {{{ getCreateMenu()
        this.getCreateMenu = function(inst, availableNodes, insertCallback) {
            var menu = {};

            if (availableNodes.length > 0) {
                menu['_add-title'] = {
                    // @todo localize
                    label: "Create new",
                    action: false,
                    _disabled: true,
                    separator_after: true
                };
            } else {
                menu['_add-title'] = {
                    // @todo localize
                    label: "There are no elements that can be created in this element",
                    action: false,
                    _disabled: true,
                };
            }

            for (var i = 0; i < availableNodes.length; i++) {
                var node = availableNodes[i];
                menu[node.name] = {
                    label: node.name,
                    nodeName: node.nodeName,
                    newName: node.newName,
                    xmlTemplate: node.xmlTemplate,
                    action: insertCallback
                };
            }

            return menu;
        };
        // }}}
        // {{{ askDelete()
        this.askDelete = $.proxy(function(node) {
            var $body = $("body");
            var inst = this._data.nodeTypes.inst;
            var $node = inst.get_node(node, true);
            var pos = $node.offset();

            $body.depageShyDialogue({
                ok: {
                    title: "Delete",
                    classes: 'default',
                    click: function(e) {
                        inst.delete_node(node);

                        return true;
                    }
                },
                cancel: {
                    title: "Cancel",
                    click: function(e) {
                        //console.log("cancel");
                    }
                }
            },{
                bind_el: false,
                direction: "LC",
                directionMarker: true,
                title: "Delete",
                message : "Really delete?"
            });

            // @todo add click event outside of shy dialogue to hide it
            $body.data("depage.shyDialogue").showDialogue(pos.left + 100, pos.top + 10);
        }, this);
        // }}}
        // {{{ checkTypes()
        this.checkTypes = function(operation, node, node_parent, node_position, more) {
            // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node', 'copy_node' or 'edit'
            // in case of 'rename_node' node_position is filled with the new node name

            // hard coded defaults
            if (node.li_attr.rel == 'pg:meta') {
                return false;
            } else if ((operation == "move_node" || operation == "copy_node") && typeof node_parent.li_attr != 'undefined' && (node_parent.li_attr.rel == 'pg:meta' || node_parent.li_attr.rel == 'sec:separator')) {
                return false;
            } else if ((operation == "edit" || operation == "create_node") && node.li_attr.rel == 'sec:separator') {
                return false;
            }

            if (operation == "create_node" || operation == "move_node" || operation == "copy_node") {
                var validParents = this._data.nodeTypes.validParents[this.getNodeType(node)] || this._data.nodeTypes.validParents['*'];
                return validParents.indexOf(this.getNodeType(node_parent)) > -1 || validParents.indexOf('*') > -1;
            }
            console.log(operation, node, node_parent);

            return true;
        };
        // }}}
        // {{{ getNodeType()
        this.getNodeType = function(node) {
            if (typeof node.li_attr !== 'undefined') {
                return node.li_attr.rel;
            } else {
                return this.element.attr("rel");
            }
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
