/**
 * @file doctypes/jstree.doctype.page.js
 *
 * Client side handler for nodes of doctype: page.
 *
 * Group: jstree doctypes
 *
 */
(function ($) {
    $.jstree.plugin("doctype_page", {

        /**
         * Plugin Default Options
         *
         * @var object
         */
        defaults : {

        },

        /**
         * Constructor
         *
         */
        __construct : function () {

            var self = this;

            var $container = this.get_container();

            /**
             * Bind to click events
             */
            $container.delegate("a", "click.jstree", function (e) {

                var db_ref = $(this).parent('li').data('db-ref');

                if (db_ref) {
                    self.load(db_ref);
                }

            });

        },

        /**
         * Destructor
         *
         */
        __destruct : function () {
        },


        /**
         * Functions
         *
         * @private
         */
        _fn : {

            /**
             * Load
             *
             * Set the doc-id data attr and re-init the jstree
             *
             */
            load : function (id) {

                var self = this;

                var $container = this.get_container();

                $.get($container.data('tree-url') + id, function(data) {
                    data = $(data).filter('.jstree-container');
                    $('#doc-tree .jstree-container').replaceWith(data);
                    $container.trigger('doc_load', data);
                });
            }
        }
    });

    // push to jstree plugin stack
    $.jstree.defaults.plugins.push("doctype_page");

})(jQuery);