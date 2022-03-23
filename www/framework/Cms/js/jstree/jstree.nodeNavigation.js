/**
* ### nodeNavigation plugin
*
* Adds nodeNavigation functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.nodeNavigation', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.nodeNavigation) { return; }

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];

    /**
     * nodeNavigation configuration
     *
     * @name $.jstree.defaults.nodeNavigation
     * @plugin nodeNavigation
     */
    $.jstree.defaults.nodeNavigation = null;
    $.jstree.plugins.nodeNavigation = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            this._data.nodeNavigation = {};
            parent.init.call(this, el, options);
            this._data.nodeNavigation.inst = this.element.jstree(true);
        };
        // }}}
        // {{{ bind()
        this.bind = function() {
            parent.bind.call(this);

            var className = "jstree-node-navigation";
            var el = this.element;
            var inst = this.element.jstree(true);
            var node;

            // bind events
            inst.element
                .on("mouseover.jstree", "." + className, function(e) {
                    //node = inst.get_node(this);
                })
                .on("mouseout.jstree", "." + className, function(e) {
                    //var parent = this.parentNode;

                    //parent.className = parent.className.replace(re, "");
                })
                .on("click.jstree", "." + className, function(e) {
                    node = inst.get_node(this);
                    inst.activate_node(node);

                    var rootNodeType = inst._data.nodeTypes.rootNodeType;
                    if (rootNodeType == "proj:pages_struct") {
                        $(window).triggerHandler("switchLayout", "document");
                    } else {
                        $(window).triggerHandler("switchLayout", "properties");
                    }

                    e.stopPropagation();
                });
        };
        // }}}
        // {{{ _create_prototype_node
        this._create_prototype_node = function() {
            var _node = parent._create_prototype_node(), _temp1;
            _temp1 = document.createElement('A');
            _temp1.className = 'jstree-node-navigation';
            _temp1.setAttribute('role', 'presentation');
            _node.appendChild(_temp1);
            _temp1 = null;

            return _node;
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
