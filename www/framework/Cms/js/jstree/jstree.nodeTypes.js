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
                var settings = $.evalJSON($tree.attr("data-tree-settings"));
                $.extend(this._data.nodeTypes, settings);
            } catch (e) {
                // continue
                return true;
            }
            this._data.nodeTypes.inst = this.element.jstree(true);
            // override default check_callback
            this.settings.core.check_callback = this.checkTypes;
            //console.log(this._data.nodeTypes.availableNodes);
            //console.log(this._data.nodeTypes.validParents);
        };
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

            //console.log(operation, node, node_parent);
            if (operation == "move_node" || operation == "copy_node") {
                var validParents = this._data.nodeTypes.validParents[this.getNodeType(node)] || this._data.nodeTypes.validParents['*'];
                return validParents.indexOf(this.getNodeType(node_parent)) > -1 || validParents.indexOf('*') > -1;
            }

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
