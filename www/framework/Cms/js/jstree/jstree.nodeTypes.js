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

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];

    var unique = function(ar) {
        var j = {};

        ar.forEach( function(v) {
            if (typeof v !== 'undefined') {
                j[v.name] = v;
            }
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
            this._data.nodeTypes.rootNodeType = this.element.attr("rel");

            // override default check_callback
            this.settings.core.check_callback = this.checkTypes;
        };
        // }}}
        // {{{ bind()
        this.bind = function() {
            parent.bind.call(this);

            var inst = this;

            // bind events
            inst.element
                .on("ready.jstree refresh.jstree", function(e) {
                    if (inst._data.nodeTypes.rootNodeType == "proj:library") {
                        if (!inst._data.nodeTypes.userCanPublish) {
                            var $toHide = inst.get_container_ul()
                                .children(".jstree-node[data-url='/global/'], .jstree-node[data-url='/cache/']");

                            for (var i = 0; i < $toHide.length; i++) {
                                inst.hide_node($toHide[i]);
                            }
                        }
                    }
                });
        };
        // }}}
        // {{{ edit()
        this.edit = function(obj, default_text, callback) {
            var inst = this._data.nodeTypes.inst;
            var $node = inst.get_node(obj, true).children("a.jstree-anchor");
            var $hint = $node.children("span").detach();
            var $input;

            parent.edit.call(this, obj, $node.text(), function(tmp, nv, cancel) {
                var $node = inst.get_node(tmp, true).children("a.jstree-anchor");
                $node.append($hint);
            });

            $input = $("input.jstree-rename-input");

            if (this._data.nodeTypes.rootNodeType == "proj:library") {
                $input
                    .attr("pattern", "^[a-zA-Z0-9\-_]*$")
                    .on("keypress", function(e) {
                        var key = e.which;

                        if (e.charCode != 0 &&
                            (key < 48 || key > 57) && // 0-9
                            (key < 65 || key > 90) &&  // a-z
                            (key < 97 || key > 122) &&  // A-Z
                            key != 45 &&  // -
                            key != 95 // _
                        ) {
                            e.preventDefault();
                        }
                    });
            }
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
                        xmlTemplate: data.item.xmlTemplate,
                        xmlTemplateData: data.item.xmlTemplateData || ""
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
                    label: locale.createNew,
                    action: false,
                    _disabled: true,
                    separator_after: true
                };
            } else {
                menu['_add-title'] = {
                    label: locale.createNoElements,
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

                if (typeof node.subTypes != 'undefined') {
                    for (var j = 0; j < node.subTypes.length; j++) {
                        var sepAfter = j == node.subTypes.length - 1;
                        menu[node.subTypes[j].name] = {
                            // four non-breakable spaces for intendation
                            label: "    " + node.subTypes[j].name,
                            nodeName: node.nodeName,
                            newName: node.newName,
                            xmlTemplate: node.xmlTemplate,
                            xmlTemplateData: node.subTypes[j].xmlTemplateData,
                            action: insertCallback,
                            separator_after: sepAfter
                        };
                    }
                }
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
                    title: locale.delete,
                    classes: 'default',
                    click: function(e) {
                        inst.delete_node(node);

                        return true;
                    }
                },
                cancel: {
                    title: locale.cancel
                }
            },{
                bind_el: false,
                directionMarker: true,
                title: locale.delete,
                message: locale.deleteQuestion
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

            // test validParents
            if (operation == "create_node" || operation == "move_node" || operation == "copy_node") {
                var validParents = this._data.nodeTypes.validParents[this.getNodeType(node)] || this._data.nodeTypes.validParents['*'] || [];
                return validParents.indexOf(this.getNodeType(node_parent)) > -1 || validParents.indexOf('*') > -1;
            }
            //console.log(operation, node, node_parent);

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
