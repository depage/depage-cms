// {{{ add_marker
/**
 * File: jstree.add_marker.js
 *
 * Adds a marker position indicator with an "add" button that allows the user to pick the position of the
 * node they wish to insert.
 *
 */
(function ($) {

    $.jstree.plugin("add_marker", {

        timer: null,

        /**
         * Construct
         *
         * @private
         */
        __construct : function () {

            var self = this;

            var $container = this.get_container();

            $marker = $("<div>add</div>").attr({ class : "jstree-add-marker" }).hide().appendTo('body');
            $indicator = $("<div />").attr({ class : "jstree-add-marker-indicator" }).hide().appendTo('body');

            // set defaults
            this.data.add_marker = {
                offset : null,
                w : null,
                target : null,
                context_menu : false,
                marker : $marker,
                indicator : $indicator
            };

             // hide the marker when mouse leaves the container.
            var bind_doc_move = function(){
                $(document).bind('mousemove.jstree-marker', function(e) {
                    var offset = $container.offset();
                    var boundary = {
                        x1 : offset.left,
                        x2 : offset.left + $container.outerWidth(),
                        y1 : offset.top,
                        y2 : offset.top + $container.outerHeight()
                    }
                    if(e.clientX < boundary.x1 || e.clientX > boundary.x2 || e.clientY < boundary.y1 || e.clientY > boundary.y2) {
                        clearTimeout(self.timer);
                        $indicator.hide();
                        $marker.hide();
                        $(document).unbind('mousemove.jstree-marker');
                    }
                });
            }

            $container.delegate("li", "mousemove.jstree", function(e) {
                if (!self.data.add_marker.context_menu){
                    if (!$marker.is(':visible')) {
                        clearTimeout(self.timer);
                        self.timer = setTimeout(function() {
                            self._show_add_marker($(e.target), e.pageX, e.pageY);
                            bind_doc_move();
                        }, 200);
                    }
                }
            });

            $marker.mousemove(function (e) {
                if (!self.data.add_marker.context_menu) {
                    clearTimeout(self.timer);
                    // add marker swallows mousemove event. try to delegate to correct li_node.
                    // TODO: fix for Opera < 10.5, Safari 4.0 Win. see http://www.quirksmode.org/dom/w3c%5Fcssom.html#documentview
                    var element = $(document.elementFromPoint(e.clientX - $marker.width(), e.clientY));
                    self._show_add_marker(element, e.pageX, e.pageY);
                }
            });

            $marker.click(function (e) {
                self._show_add_context_menu();
            });

            $(document).bind("context_hide.vakata", function () {
                clearTimeout(self.timer);
                self.data.add_marker.context_menu = false;
                $indicator.hide();
                $marker.hide();
            });
        },

        /**
         * Functions
         *
         */
        _fn : {

            /**
             * Show Context Menu
             *
             * @private
             */
            _show_add_context_menu : function () {

                var self = this;

                var type_settings =  this.get_settings()['typesfromurl'];
                var type = self.data.add_marker.target.attr(type_settings.type_attr);
                var available_nodes = type_settings['valid_children'][type];

                var create_menu = $.depage.jstree.buildCreateMenu(available_nodes, this.data.add_marker.pos);

                var position = {
                    'x' : this.data.add_marker.marker.offset()['left'],
                    'y' : this.data.add_marker.marker.offset()['top'] + this.data.core.li_height
                };

                $.vakata.context.show(
                    this.data.add_marker.target,
                    position,
                    create_menu.create.submenu
                );

                $(document).bind("context_hide.vakata", function () {
                    self.data.add_marker.context_menu = false;
                });

                this.data.add_marker.context_menu = true;
            },

            /**
             * Show Add Marker
             *
             * @param target
             * @param page_x
             * @param page_y
             * @private
             */
            _show_add_marker : function (target, page_x, page_y) {

                if(this.data.add_marker.context_menu) {
                    return;
                }

                var node = this.get_node(target);

                if (!node || node == -1 || target[0].nodeName == "UL") {
                    clearTimeout(self.timer);
                    this.data.add_marker.marker.hide();
                    this.data.add_marker.indicator.hide();
                    return false;
                }

                var c = this.get_container();
                var marker_pos = {'left' : 0, 'top' : 0};
                var indicator_pos = {'left' : 0, 'top' : 0};
                marker_pos.left = c.offset().left + c.width() - (c.attr("data-add-marker-right") || 30) - (c.attr("data-add-marker-margin-right") || 10);
                var min_x = marker_pos.left - (c.attr("data-add-marker-margin-left") || 10);
                if (page_x < min_x) {
                    clearTimeout(self.timer);
                    this.data.add_marker.marker.hide();
                    this.data.add_marker.indicator.hide();
                    return false;
                }

                // fix li_height
                this.data.core.li_height = c.find("ul li.jstree-closed, ul li.jstree-leaf").eq(0).height() || 18;
                this.data.add_marker.offset = target.offset();
                this.data.add_marker.w = (page_y - (this.data.add_marker.offset.top || 0)) % this.data.core.li_height;
                marker_pos.top = this.data.add_marker.offset.top;
                indicator_pos.top = this.data.add_marker.offset.top;

                indicator_pos.left = target.is('a')
                    ? target.position().left + target.width()
                    : target.children('a').position().left;

                if (this.data.add_marker.w < this.data.core.li_height / 4) {
                    // before
                    this.data.add_marker.parent = this.get_parent(node);
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "before";
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    marker_pos.top -= this.data.core.li_height / 2;
                    indicator_pos.top -= this.data.core.li_height / 2;
                } else if (this.data.add_marker.w <= this.data.core.li_height * 3/4) {
                    // inside
                    this.data.add_marker.parent = node;
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "last";
                    this.data.add_marker.marker.addClass("jstree-add-marker-inside").removeClass("jstree-add-marker-between");
                    indicator_pos.left += target.children('a').width();
                } else {
                    // after
                    var target_node = this.get_next(node);
                    if (target_node.length) {
                        this.data.add_marker.parent = this.get_parent(target_node);
                        this.data.add_marker.target = target_node;
                        this.data.add_marker.pos = "before";
                    } else {
                        // special case for last node
                        this.data.add_marker.target = node.parentsUntil(".jstree", "li:last").andSelf().eq(0);
                        this.data.add_marker.parent = this.get_parent(this.data.add_marker.target);
                        this.data.add_marker.pos = "after";
                    }
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    marker_pos.top += this.data.core.li_height / 2;
                    indicator_pos.top += this.data.core.li_height / 2;
                }

                // indicator width
                var width = marker_pos.left - indicator_pos.left;

                // set indicator position
                this.data.add_marker.indicator.css({ "left" : indicator_pos.left + "px", "top" : indicator_pos.top + "px", "width" : width + "px"}).show();

                // set marker position
                this.data.add_marker.marker.css({ "left" : marker_pos.left + "px", "top" : marker_pos.top + "px" }).show();
            }
        }
    });

})(jQuery);
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */