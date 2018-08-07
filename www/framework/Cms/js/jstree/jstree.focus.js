/**
* ### focus plugin
*
* Adds focus functionality to jsTree for multiple trees in document
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.focus', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.focus) { return; }

    /**
     * focus configuration
     *
     * @name $.jstree.defaults.focus
     * @plugin focus
     */
    $.jstree.defaults.focus = null;
    $.jstree.plugins.focus = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            this._data.focus = {};
            parent.init.call(this, el, options);
            this._data.focus.inst = this.element.jstree(true);
            this._data.focus.focused = $(".jstree:jstree").length == 1;

            if (this._data.focus.focused) {
                this.element.addClass("jstree-focus");
            }

            this.element.on("mousedown.jstree mouseup.jstree focus.jstree", this.gainFocus);
        };
        // }}}
        // {{{ gainFocus
        this.gainFocus = $.proxy(function() {
            if (this._data.focus.focused) return;

            var inst = this._data.focus.inst;
            this._data.focus.focused = true;

            $(".jstree:jstree").each(function() {
                var otherInst = $(this).jstree(true);
                if (inst != otherInst) {
                    otherInst.looseFocus();
                }
            });

            inst.element.addClass("jstree-focus");
            inst.trigger("focus");

            var $node = inst.get_node(inst.get_selected(), true);
            if ($node) {
                $node.find('> .jstree-anchor').focus();
            }
        }, this);
        // }}}
        // {{{ looseFocus
        this.looseFocus = $.proxy(function() {
            if (!this._data.focus.focused) return;

            var inst = this._data.focus.inst;
            this._data.focus.focused = false;

            inst.element.removeClass("jstree-focus");

            inst.trigger("blur");
        }, this);
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
