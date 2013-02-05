/* File: jstree.toolbar.js
 * Enables a toolbar
 */
/* Group: jstree sort plugin */
(function ($) {
    $.jstree.plugin("toolbar", {
        __construct : function () {
            this.get_container()
                .bind("__loaded.jstree", $.proxy(function() {
                    this.show_toolbar();
                }, this));
        },
        defaults : {
            "create" : {
                "label"             : "Create",
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
        },
        _fn : {
            show_toolbar : function (obj, x, y) {
                // function adds toolbar list items
                var additem = function($ul, item) {
                    var $li = $('<li class="js-tree-context-toolbar-item" />');

                    var $a = $('<a href="#">' + item.label + '</a>').click(function() {
                        if (item.action) {
                            item.action();
                        } else if(item.submenu)  {
                            (this).children("ul closed").removeClass("closed").addClass("open");
                        }
                        return false;
                    });

                    if (item.submenu) {
                        var $sub = $('<ul class="jstree-context-toolbar-submenu closed">');
                        // recursively add sub menu
                        $.each(item.submenu, function(i, item) {
                            additem($sub, item);
                        });

                        $li.append($sub);
                    }

                    $ul.append($li.append($a));
                }

                var items = this.get_settings().toolbar;

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