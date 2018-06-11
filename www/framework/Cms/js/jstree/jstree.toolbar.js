/**
* ### toolbar plugin
*
* Adds toolbar functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.toolbar', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.toolbar) { return; }

    var inst;

    /**
     * toolbar configuration
     *
     * @name $.jstree.defaults.toolbar
     * @plugin toolbar
     */
    $.jstree.defaults.toolbar = null;
    $.jstree.plugins.toolbar = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            this._data.toolbar = {};
            parent.init.call(this, el, options);
            inst = this.element.jstree(true);
            this._data.toolbar.$el = $("<div class=\"toolbar\"></div>").appendTo(this.element.parent());
        };
        // }}}
        // {{{ activate_node()
        this.activate_node = function(obj, e) {
            parent.activate_node.call(this, obj, e);
            this._data.toolbar.$el.empty();

            this.addToolbarButton("create", "button-create", function() {
                alert("create");
            });
            this.addToolbarButton("delete", "button-delete", function() {
                // @todo ask before deleting
                inst.delete_node(parent.get_selected());
            });
            this.addToolbarButton("reload", "button-reload", function() {
                inst.refresh();
            });
        };
        // }}}
        // {{{
        this.addToolbarButton = function(name, className, callback) {
            var $button = $("<a></a>");
            $button
                .text(name)
                .addClass("button")
                .addClass(className).
                on("click", callback);

            $button.appendTo(this._data.toolbar.$el);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
