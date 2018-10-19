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

    var lang = $('html').attr('lang');
    var locale = depageCMSlocale[lang];

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
            var el = this.element;
            var inst = this.element.jstree(true);
            var node;
            var nodeParent;
            var nodesForSelf = [];
            var nodesForParent = [];
            var re = / insert-[a-z]+/;
            var menuVisible = false;

            // bind events
            inst.element
                .on("mouseover.jstree", "." + className, function(e) {
                    if (menuVisible) return;

                    node = inst.get_node(this);
                    nodeParent = inst.get_node(inst.get_parent(node));
                    nodesForSelf = inst.getAvailableNodesFor(node);
                    nodesForParent = inst.getAvailableNodesFor(nodeParent);
                })
                .on("mousemove.jstree", "." + className, function(e) {
                    if (menuVisible) return;

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
                    if (menuVisible) return;

                    var parent = this.parentNode;

                    parent.className = parent.className.replace(re, "");
                })
                .on("click.jstree", "." + className, function(e) {
                    if (menuVisible) return;

                    // @todo check
                    if (nodesForSelf.length > 0 && e.offsetY > this.clientHeight / 4 && e.offsetY < this.clientHeight / 4 * 3) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForSelf, inst.insertCallback(node, "last")));
                    } else if (nodesForParent.length > 0 && e.offsetY < this.clientHeight / 2) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent, inst.insertCallback(node, "before")));
                    } else if (nodesForParent.length > 0 && e.offsetY > this.clientHeight / 2) {
                        $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForParent, inst.insertCallback(node, "after")));
                    }
                    e.stopPropagation();
                })
                .on("redraw.jstree", function(e) {
                    var $button = el.children(".jstree-root-add-button");

                    if ($button.length == 0) {
                        $button = $("<a class=\"jstree-root-add-button\" data-tooltip=\"" + locale.createNewAtEnd + "\">+</a>").appendTo(el);

                        $button.on("click", function() {
                            var node = inst.get_node(el);
                            var nodesForSelf = inst.getAvailableNodesFor(node);

                            $.vakata.context.show($(this), false, inst.getCreateMenu(inst, nodesForSelf, inst.insertCallback(node, "last")));
                        });
                    }
                });

            // @todo unbind events when tree is destroyed?
            $(document)
                .on("context_show.vakata", function(e) {
                    menuVisible = true;
                })
                .on("context_hide.vakata", function(e) {
                    menuVisible = false;

                    if (!inst.element) return;

                    inst.element.find(".jstree-node.insert-into").removeClass("insert-into");
                    inst.element.find(".jstree-node.insert-before").removeClass("insert-before");
                    inst.element.find(".jstree-node.insert-after").removeClass("insert-after");
                })
                .on("dnd_move.vakata", function(e, data) {
                    var $parent = $('.jstree-hovered');

                    if ($parent.length > 0) {
                        var $marker = $('#jstree-marker');
                        if ($parent.parent().hasClass("jstree-dnd-parent")) {
                            $marker.width(0);
                        } else {
                            $marker.width($parent.width() + 5);
                        }
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
