/**
* ### nodeActions plugin
*
* Adds nodeActions functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.nodeActions', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.nodeActions) { return; }

    /**
     * nodeActions configuration
     *
     * @name $.jstree.defaults.nodeActions
     * @plugin nodeActions
     */
    $.jstree.defaults.nodeActions = null;
    $.jstree.plugins.nodeActions = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            this._data.nodeActions = {};
            parent.init.call(this, el, options);
            this._data.nodeActions.inst = this.element.jstree(true);
        };
        // }}}
        // {{{
        this.bind = function() {
            parent.bind.call(this);

            var className = "jstree-node-actions";
            var inst = this.element.jstree(true);
            var nodesForSelf = [];
            var nodesForParent = [];

            // bind events
            inst.element
                .on("mouseover.jstree", "." + className, function(e) {
                    nodesForSelf = inst.getAvailableNodesFor(inst.get_node(this));
                    nodesForParent = inst.getAvailableNodesFor(inst.get_node(inst.get_parent(inst.get_node(this))));
                })
                .on("mousemove.jstree", "." + className, function(e) {
                    if (e.offsetY < 0) {
                        // hide when out of bounds
                        this.className = className;
                    } else if (nodesForParent.length > 0 && e.offsetY < this.clientHeight / 4) {
                        this.className = className + " insert-before";
                    } else if (nodesForParent.length > 0 && e.offsetY > this.clientHeight / 4 * 3) {
                        this.className = className + " insert-after";
                    } else if (nodesForSelf.length > 0) {
                        this.className = className + " insert-into";
                    }
                })
                .on("mouseout.jstree", "." + className, function(e) {
                    this.className = className;
                })
                .on("click.jstree", "." + className, function(e) {
                    // @todo check
                    if (nodesForParent.length > 0 && e.offsetY < this.clientHeight / 4) {
                        console.log("insert before", nodesForParent);
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent));
                    } else if (nodesForParent.length > 0 && e.offsetY > this.clientHeight / 4 * 3) {
                        console.log("insert after", nodesForParent);
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent));
                    } else if (nodesForSelf.length > 0) {
                        console.log("insert into", nodesForSelf);
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForSelf));
                    }
                });
        };
        // }}}
        // {{{ _create_prototype_node
        this._create_prototype_node = function() {
            var _node = parent._create_prototype_node(), _temp1;
            _temp1 = document.createElement('A');
            _temp1.className = 'jstree-node-actions';
            _temp1.setAttribute('role', 'presentation');
            _node.appendChild(_temp1);
            _temp1 = null;

            return _node;
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
