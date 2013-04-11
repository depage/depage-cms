/**
 * File: jstree.toolbar.js
 * Enables a toolbar
 *
 * Group: jstree sort plugin
 *
 **/

(function ($) {
    $.jstree.plugin("toolbar", {

        /**
         * Construct
         *
         * @private
         */
        __construct : function () {
            this.get_container()
                .bind("__loaded.jstree", $.proxy(function(e) {
                    this.show_toolbar($(this.get_container()));
                }, this))
                .bind("select_node.jstree", $.proxy(function(e, obj) {
                    this.node_selected(obj.args[0]);
            }, this))
        },

        /**
         * Defaults
         *
         */
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

        /**
         * Functions
         *
         */
        _fn : {

            /**
             * Show Toolbar
             *
             * @param obj
             * @return {Boolean}
             */
            show_toolbar : function (obj) {
                // function adds toolbar list items
                var self = this;

                if(self.get_container().siblings('.toolbar').length) {
                    return false;
                }

                var additem = function($ul, item) {
                    var $li = $('<li class="js-tree-toolbar-item" />');

                    var $a = $('<a href="#">' + item.label + '</a>').addClass('js-tree-toolbar-' + item.label.toLowerCase());

                    $a.bind('click.js-tree', function() {
                        self.click_handler(this, item, obj);
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

                var $ul = $("<menu />");
                var $toolbar = $("<div class=\"toolbar\" />");
                $toolbar.append($ul);
                $.each(items, function(i, item) {
                    additem($ul, item);
                });

                self.get_container().before($toolbar);
            },

            /**
             * Node Selected
             *
             * @param obj
             */
            node_selected : function (obj) {
                var self = this;
                var items = this.get_toolbar_items(obj);
                $.each(items, function(i, item){
                    var $a = $('a.js-tree-toolbar-' + item.label.toLowerCase(), self.get_container().siblings('.toolbar'));
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
                            self.click_handler(this, item, obj);
                            return false;
                        });
                    }
                });
            },

            /**
             * Get Toolbar Items
             *
             * @param obj
             * @return {*}
             */
            get_toolbar_items: function(obj) {
                return obj.data("jstree") && obj.data("jstree").toolbar ?
                    obj.data("jstree").toolbar :
                    this.get_settings().toolbar.items.apply(this, [obj]);
            },

            /**
             * Click Handler
             *
             * @param item
             * @param obj
             * @return {Boolean}
             */
            click_handler: function(context, item, obj) {
                var $a = $(context);

                var offset = $a.offset();

                if (!item._disabled) {
                    if (item.action) {
                        item.action.apply(this, [obj, offset.top, offset.left]);
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
