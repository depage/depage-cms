/* File: jstree.toolbar.js
 * Enables a toolbar
 */
/* Group: jstree sort plugin */

(function ($) {
    $.jstree.plugin("toolbar", {
        __construct : function () {
            this.get_container()
                .bind("__loaded.jstree", $.proxy(function(e) {
                    this.show_toolbar($(e.currentTarget));
                }, this));
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
                var additem = function($ul, item) {
                    var $li = $('<li class="js-tree-toolbar-item" />');

                    var $a = $('<a href="#">' + item.label + '</a>').click(function() {
                        if (item.action) {
                            item.action.apply(this, [obj]);
                        } else if(item.submenu)  {
                            $(this).children("ul.closed").removeClass("closed").addClass("open");
                        }
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

                var s = this.get_settings().toolbar;
                var items = obj.data("jstree") && obj.data("jstree").toolbar ? obj.data("jstree").toolbar : s.items;
                if($.isFunction(items)) {
                    items = items.call(this, obj);
                }

                var $ul = $("<ul />");
                $.each(items, function(i, item) {
                    additem($ul, item);
                });

                $("body").prepend($ul);
            }
        }
    });
    $.jstree.defaults.plugins.push("toolbar");
})(jQuery);