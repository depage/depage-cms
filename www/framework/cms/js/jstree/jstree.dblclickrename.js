// {{{ dblclick_rename 
/*
 * double click rename plugin
 */
(function ($) {
    $.jstree.plugin("dblclickrename", {
        __construct : function () {
            var c = this.get_container();
            c.delegate("a", "dblclick", function (e) {
                c.jstree("edit", this);
                // do not call generic double click handler, which disables text selections 
                e.stopImmediatePropagation();
            });
        },
    });
})(jQuery);
// }}}
