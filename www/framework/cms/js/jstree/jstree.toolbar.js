/* File: jstree.toolbar.js
 * Enables a toolbar
 */
/* Group: jstree sort plugin */

(function ($) {
    $.jstree.plugin("toolbar", {
        __construct : function () {
            this.get_container()
                .bind("__loaded.jstree", $.proxy(function(e) {
                    this.show_toolbar($(this.get_container()));
                }, this))
                .bind("select_node.jstree", $.proxy(function(e, obj) {
                    this.node_selected(obj.args[0]);
            }, this))
        },
        defaults : {

            items : function(obj) {
                return {
                    "create" : {
                        "label"             : "Create",
                        "submenu"           : {
                            "page"          : {
                                "label"     : "page",
                                "action"    : function() {

                                }
                            },
                            "folder"          : {
                                "label"     : "folder",
                                "action"    : function() {

                                }
                            }
                        },
                        "action"            : function () {
                        }
                    },
                    "remove" : {
                        "label"             : "Delete",
                        "action"            : function () {
                        }
                    },
                    "duplicate" : {
                        "label"             : "Copy",
                        "action"            : function () {
                        }
                    }
                }
            }
        },
        _fn : {
            show_toolbar : function (obj) {
                // function adds toolbar list items
                var self = this;

                var additem = function($ul, item) {
                    var $li = $('<li class="js-tree-toolbar-item" />');

                    var $a = $('<a href="#">' + item.label + '</a>').addClass('js-tree-toolbar-' + item.label.toLowerCase());

                    $a.bind('click.js-tree', function() {
                        self.click_handler(item, obj);
                        return false;
                    });

                    if (item.submenu) {
                        var $sub = $('<ul class="jstree-toolbar-submenu closed">');
                        // recursively add sub menu
                        $.each(item.submenu, function(i, item) {
                            additem($sub, item);
                        });

                        $li.append($sub);
                    }

                    $ul.append($li.append($a));
                }

                var items = this.get_toolbar_items(obj);

                if($.isFunction(items)) {
                    items = items.call(this, obj);
                }

                var $ul = $("<ul />");
                $.each(items, function(i, item) {
                    additem($ul, item);
                });

                $("body").prepend($ul);
            },

            node_selected : function (obj) {
                var self = this;
                var items = this.get_toolbar_items(obj);
                $.each(items, function(i, item){
                    var $a = $('a.js-tree-toolbar-' + item.label.toLowerCase());
                    $a.unbind('click.jstree');
                    if (item._disabled) {
                        $a.addClass('disabled');
                        $a.bind('click.jstree', function(e) {
                            e.preventDefault();
                            return false;
                        });
                    } else {
                        $a.removeClass('disabled');
                        $a.bind('click.jstree', function() {
                            self.click_handler(item, obj);
                            return false;
                        });
                    }
                });
            },

            get_toolbar_items: function(obj) {
                return obj.data("jstree") && obj.data("jstree").toolbar ?
                    obj.data("jstree").toolbar :
                    this.get_settings().toolbar.items.apply(this, [obj]);
            },

            click_handler: function(item, obj) {
                var $a = $(this);

                if (!item._disabled) {
                    if (item.action) {
                        item.action.apply(this, [obj]);
                    } else if(item.submenu)  {
                        $a.children("ul.closed").removeClass("closed").addClass("open");
                    }
                }
                return false;
            }
        }
    });
    $.jstree.defaults.plugins.push("toolbar");
})(jQuery);