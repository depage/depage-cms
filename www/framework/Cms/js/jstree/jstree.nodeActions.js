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
        // {{{ bind()
        this.bind = function() {
            parent.bind.call(this);

            var className = "jstree-node-actions";
            var inst = this.element.jstree(true);
            var node;
            var nodeParent;
            var nodesForSelf = [];
            var nodesForParent = [];
            var re = / insert-[a-z]+/;

            // bind events
            inst.element
                .on("mouseover.jstree", "." + className, function(e) {
                    node = inst.get_node(this);
                    nodeParent = inst.get_node(inst.get_parent(node));
                    nodesForSelf = inst.getAvailableNodesFor(node);
                    nodesForParent = inst.getAvailableNodesFor(nodeParent);
                })
                .on("mousemove.jstree", "." + className, function(e) {
                    var parent = this.parentNode;

                    if (e.offsetY < 0) {
                        // hide when out of bounds
                        parent.className = parent.className.replace(re, "");
                    } else if (nodesForSelf.length > 0 && e.offsetY > this.clientHeight / 4 && e.offsetY < this.clientHeight / 4 * 3) {
                        parent.className = parent.className.replace(re, "") + " insert-into";
                    } else if (nodesForParent.length > 0 && e.offsetY < this.clientHeight / 2) {
                        parent.className = parent.className.replace(re, "") + " insert-before";
                    } else if (nodesForParent.length > 0 && e.offsetY > this.clientHeight / 2) {
                        parent.className = parent.className.replace(re, "") + " insert-after";
                    }
                })
                .on("mouseout.jstree", "." + className, function(e) {
                    var parent = this.parentNode;

                    parent.className = parent.className.replace(re, "");
                })
                .on("click.jstree", "." + className, function(e) {
                    // @todo check
                    if (nodesForSelf.length > 0 && e.offsetY > this.clientHeight / 4 && e.offsetY < this.clientHeight / 4 * 3) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForSelf, inst.insertCallback(node, "last")));
                    } else if (nodesForParent.length > 0 && e.offsetY < this.clientHeight / 2) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent, inst.insertCallback(node, "before")));
                    } else if (nodesForParent.length > 0 && e.offsetY > this.clientHeight / 2) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent, inst.insertCallback(node, "after")));
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
