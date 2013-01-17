// {{{ nodeinfo
/*
 * hide / show span when renaming plugin
 */
(function ($) {
    $.jstree.plugin("nodeinfo", {
        // show span again after rename
        __construct : function () {
            this.get_container().bind("rename_node.jstree", function (e, data) {
                var a = data.rslt.obj.children("a");
                var span = data.rslt.obj.children("span");
                span.appendTo(a).show();
            });
        },
        // hide span before rename
        _fn : {
            edit : function (obj) {
                var node = this.get_node(obj);
                var a = node.children("a");
                a.children("span").insertAfter(a);
                var a = node.children("span").hide();
                // call without any argument, so that original arguments are used
                return this.__call_old();
            }
        },
    });
})(jQuery);
// }}}
