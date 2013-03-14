// {{{ add_marker
/*
 * add marker plugin
 */
(function ($) {
    $.jstree.plugin("add_marker", {
        __init : function () {
            this.data.add_marker = {
                offset : null,
                w : null,
                target : null,
                context_menu : false,
                marker : $("<div>ADD</div>").attr({ id : "jstree-add-marker" }).hide().appendTo("body")
            };

            var c = this.get_container();
            c.bind("mouseleave.jstree", $.proxy(function(e) {
                if (!this.data.add_marker.context_menu) {
                    this.data.add_marker.marker.hide();
                }
            }, this))
                .delegate("li", "mousemove.jstree", $.proxy(function(e) {
                if (!this.data.add_marker.context_menu) {
                    this._show_add_marker($(e.target), e.pageX, e.pageY);
                }
            }, this));

            this.data.add_marker.marker.mousemove($.proxy(function (e) {
                if (!this.data.add_marker.context_menu) {
                    // add marker swallows mousemove event. try to delegate to correct li_node.
                    // TODO: fix for Opera < 10.5, Safari 4.0 Win. see http://www.quirksmode.org/dom/w3c%5Fcssom.html#documentview
                    var element = $(document.elementFromPoint(e.clientX - this.data.add_marker.marker.width(), e.clientY));
                    this._show_add_marker(element, e.pageX, e.pageY);
                }
            }, this))
                .click($.proxy(function (e) {
                this._show_add_context_menu();
            }, this));
            $(document).bind("context_hide.vakata", $.proxy(function () {
                this.data.add_marker.context_menu = false;
                this.data.add_marker.marker.hide();
            }, this));
        },
        _fn : {
            _get_valid_children : function () {
                var types_settings = this._get_settings().types_from_url;
                if (this.data.add_marker.parent !== -1) {
                    var parent_type = this.data.add_marker.parent.attr(types_settings.type_attr);
                    var valid_children = (types_settings.types[parent_type] || types_settings.types["default"]).valid_children;
                } else {
                    // root element
                    var valid_children = types_settings.valid_children;
                }

                return valid_children;
            },
            _has_valid_children : function () {
                return this._get_valid_children() != "none";
            },
            _get_add_context_menu_item : function (name, separator) {
                return {
                    separator_before : separator || false,
                    separator_after : false,
                    label : "Create " + name,
                    action : function (obj) {
                        this.create(this.data.add_marker.target, this.data.add_marker.pos, { attr : { rel : name } });
                    }
                };
            },
            _get_add_context_menu_items : function () {
                var valid_children = this._get_valid_children();
                var special_children = (this.get_container().attr("data-add-marker-special-children") || "").split(" ");
                var items = [];

                if ($.isArray(valid_children)) {
                    for (var i = 0; i < special_children.length; i++) {
                        if ($.inArray(special_children[i], valid_children) != -1) {
                            items.push(this._get_add_context_menu_item(special_children[i]));
                        }
                    }

                    for (var i = 0; i < valid_children.length; i++) {
                        if ($.inArray(valid_children[i], special_children) == -1) {
                            items.push(this._get_add_context_menu_item(valid_children[i], i == 0));
                        }
                    }
                }

                return items;
            },
            _show_add_context_menu : function () {
                var items = this._get_add_context_menu_items();
                if (items.length) {
                    var a = this.data.add_marker.marker;
                    var o = a.offset();
                    var x = o.left;
                    var y = o.top + this.data.core.li_height;

                    this.data.add_marker.context_menu = true;
                    $.vakata.context.show(items, a, x, y, this, this.data.add_marker.target);
                    if(this.data.themes) { $.vakata.context.cnt.attr("class", "jstree-" + this.data.themes.theme + "-context"); }
                }
            },
            _show_add_marker : function (target, page_x, page_y) {
                var node = this._get_node(target);
                if (!node || node == -1 || target[0].nodeName == "UL") {
                    this.data.add_marker.marker.hide();
                    return;
                }

                var c = this.get_container();
                var x_pos = c.offset().left + c.width() - (c.attr("data-add-marker-right") || 30) - (c.attr("data-add-marker-margin-right") || 10);
                var min_x = x_pos - (c.attr("data-add-marker-margin-left") || 10);
                if (page_x < min_x) {
                    this.data.add_marker.marker.hide();
                    return;
                }

                // fix li_height
                this.data.core.li_height = c.find("ul li.jstree-closed, ul li.jstree-leaf").eq(0).height() || 18;
                this.data.add_marker.offset = target.offset();
                this.data.add_marker.w = (page_y - (this.data.add_marker.offset.top || 0)) % this.data.core.li_height;
                var top = this.data.add_marker.offset.top;

                if (this.data.add_marker.w < this.data.core.li_height / 4) {
                    // before
                    this.data.add_marker.parent = this._get_parent(node);
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "before";
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    top -= this.data.core.li_height / 2;
                } else if (this.data.add_marker.w <= this.data.core.li_height * 3/4) {
                    // inside
                    this.data.add_marker.parent = node;
                    this.data.add_marker.target = node;
                    this.data.add_marker.pos = "last";
                    this.data.add_marker.marker.addClass("jstree-add-marker-inside").removeClass("jstree-add-marker-between");
                } else {
                    // after
                    var target_node = this._get_next(node);
                    if (target_node.length) {
                        this.data.add_marker.parent = this._get_parent(target_node);
                        this.data.add_marker.target = target_node;
                        this.data.add_marker.pos = "before";
                    } else {
                        // special case for last node
                        this.data.add_marker.target = node.parentsUntil(".jstree", "li:last").andSelf().eq(0);
                        this.data.add_marker.parent = this._get_parent(this.data.add_marker.target);
                        this.data.add_marker.pos = "after";
                    }
                    this.data.add_marker.marker.addClass("jstree-add-marker-between").removeClass("jstree-add-marker-inside");
                    top += this.data.core.li_height / 2;
                }

                if (this._has_valid_children()) {
                    this.data.add_marker.marker.removeClass("jstree-add-marker-disabled");
                } else {
                    this.data.add_marker.marker.addClass("jstree-add-marker-disabled");
                }
                this.data.add_marker.marker.css({ "left" : x_pos + "px", "top" : top + "px" }).show();
            }
        }
    });
})(jQuery);
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */