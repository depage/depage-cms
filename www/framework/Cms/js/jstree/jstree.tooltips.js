// {{{ tooltips
/*
 * hover tooltips plugin
 *
 * @todo add html tooltip to have better styling
 */
(function ($) {
    $.jstree.plugin("tooltips", {
        __construct : function () {
            var c = this.get_container();
            c.bind("hover_node.jstree", function (e, data) {
                var tooltip = c.jstree("get_text", data.rslt.obj);
                tooltip = tooltip.replace("<span>", " - ").replace("</span>", "");
                data.rslt.obj.children("a").attr("title", tooltip);
            });
        },
    });
})(jQuery);
// }}}
