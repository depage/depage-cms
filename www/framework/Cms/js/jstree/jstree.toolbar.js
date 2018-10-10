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

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];

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
            this._data.toolbar.inst = this.element.jstree(true);
            this._data.toolbar.$el = $("<span></span>").appendTo("#toolbarmain .tree-actions");


            var $toolbar = this._data.toolbar.$el;

            if (this._data.focus.focused) {
                $toolbar.addClass("visible");
            }

            this.element
                .on("focus.jstree", function() {
                    $toolbar.addClass("visible");
                })
                .on("blur.jstree", function() {
                    $toolbar.removeClass("visible");
                });
        };
        // }}}
        // {{{ activate_node()
        this.activate_node = function(obj, e) {
            var inst = this._data.toolbar.inst;

            parent.activate_node.call(this, obj, e);

            var node = inst.get_node(inst.get_selected());
            this._data.toolbar.$el.empty();

            var nodesForSelf = inst.getAvailableNodesFor(node);

            var $createButton = this.addToolbarButton(locale.create, "icon-create", function() {
                var $button = $(this);
                var pos = $button.offset();
                pos.top += $button.height() + 5;

                $button.addClass("open");
                $(document).one("context_hide.vakata", function() {
                    $button.removeClass("open");
                });

                $.vakata.context.show($button, {x: pos.left, y: pos.top }, inst.getCreateMenu(inst, nodesForSelf, inst.insertCallback(node, "last")));
            });
            if (nodesForSelf.length == 0) {
                $createButton.addClass("disabled");
            }

            if (inst._data.nodeTypes.rootNodeType != "proj:library") {
                var $duplicateButton = this.addToolbarButton(locale.duplicate, "icon-duplicate icon-only", function() {
                    inst.copy_node(node, node, "after");
                });
            }

            var $deleteButton = this.addToolbarButton(locale.delete, "icon-delete icon-only", function() {
                inst.askDelete(node);
            });
            if (!inst.check("delete_node", node)) {
                $deleteButton.addClass("disabled");
            }
        };
        // }}}
        // {{{ destroy()
        this.destroy = function(keep_html) {
            this._data.toolbar.$el.remove();

            parent.destroy.call(this, keep_html);
        };
        // }}}
        // {{{ addToolbarButton()
        this.addToolbarButton = function(name, className, callback) {
            var $button = $("<a></a>");
            $button
                .text(name)
                .addClass("button")
                .addClass(className)
                .attr("data-tooltip", name)
                .on("click", function() {
                    if (!$(this).hasClass("disabled")) callback.apply(this);
                });

            $button.appendTo(this._data.toolbar.$el);

            return $button;
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
