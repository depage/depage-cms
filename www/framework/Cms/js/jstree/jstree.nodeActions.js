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
            var className = "jstree-node-actions";
            this._data.nodeActions = {};
            parent.init.call(this, el, options);
            this._data.nodeActions.inst = this.element.jstree(true);

            // bind events
            this._data.nodeActions.inst.element
                .on("mousemove.jstree", "." + className, function(e) {
                    // @todo check
                    if (e.offsetY < this.clientHeight / 4) {
                        this.className = className + " insert-before";
                    } else if (e.offsetY > this.clientHeight / 4 * 3) {
                        this.className = className + " insert-after";
                    } else {
                        this.className = className + " insert-into";
                    }
                })
                .on("mouseout.jstree", "." + className, function(e) {
                    this.className = className;
                })
                .on("click.jstree", "." + className, function(e) {
                    // @todo check
                    if (e.offsetY < this.clientHeight / 4) {
                        alert("insert before");
                    } else if (e.offsetY > this.clientHeight / 4 * 3) {
                        alert("insert after");
                    } else {
                        alert("insert into");
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
