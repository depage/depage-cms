/*
 * jsTree 1.0-rc1 plugins
 * http://jstree.com/
 *
 * Copyright (c) 2011 Lion Vollnhals
 *
 * Dual licensed under the MIT and GPL licenses (same as jQuery):
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 */

/*jslint browser: true, onevar: true, undef: true, bitwise: true, strict: true */
/*global window : false, clearInterval: false, clearTimeout: false, document: false, setInterval: false, setTimeout: false, jQuery: false, navigator: false, XSLTProcessor: false, DOMParser: false, XMLSerializer: false*/

"use strict";

// Firefox 4 needs global variables declared
var placeholder;

/*
 * jsTree DND plugin 1.0
 * Drag and drop plugin for moving/copying nodes
 */
(function ($) {
    var o = false,
        r = false,
        m = false,
        sli = false,
        sti = false,
        dir1 = false,
        dir2 = false;
    $.vakata.dnd = {
        is_down : false,
        is_drag : false,
        helper : false,
        scroll_spd : 10,
        init_x : 0,
        init_y : 0,
        threshold : 5,
        user_data : {},

        drag_start : function (e, data, html) { 
            if($.vakata.dnd.is_drag) { $.vakata.drag_stop({}); }
            try {
                e.currentTarget.unselectable = "on";
                e.currentTarget.onselectstart = function() { return false; };
                if(e.currentTarget.style) { e.currentTarget.style.MozUserSelect = "none"; }
            } catch(err) { }
            $.vakata.dnd.init_x = e.pageX;
            $.vakata.dnd.init_y = e.pageY;
            $.vakata.dnd.user_data = data;
            $.vakata.dnd.is_down = true;
            $.vakata.dnd.helper = $("<div id='vakata-dragged'>").html(html).css("opacity", "0.75");
            $(document).bind("mousemove", $.vakata.dnd.drag);
            $(document).bind("mouseup", $.vakata.dnd.drag_stop);
            return false;
        },
        drag : function (e) { 
            if(!$.vakata.dnd.is_down) { return; }
            if(!$.vakata.dnd.is_drag) {
                if(Math.abs(e.pageX - $.vakata.dnd.init_x) > 5 || Math.abs(e.pageY - $.vakata.dnd.init_y) > 5) { 
                    $.vakata.dnd.helper.appendTo("body");
                    $.vakata.dnd.is_drag = true;
                    $(document).triggerHandler("drag_start.vakata", { "event" : e, "data" : $.vakata.dnd.user_data });
                }
                else { return; }
            }

            // maybe use a scrolling parent element instead of document?
            if(e.type === "mousemove") { // thought of adding scroll in order to move the helper, but mouse poisition is n/a
                var d = $(document), t = d.scrollTop(), l = d.scrollLeft();
                if(e.pageY - t < 20) { 
                    if(sti && dir1 === "down") { clearInterval(sti); sti = false; }
                    if(!sti) { dir1 = "up"; sti = setInterval(function () { $(document).scrollTop($(document).scrollTop() - $.vakata.dnd.scroll_spd); }, 150); }
                }
                else { 
                    if(sti && dir1 === "up") { clearInterval(sti); sti = false; }
                }
                if($(window).height() - (e.pageY - t) < 20) {
                    if(sti && dir1 === "up") { clearInterval(sti); sti = false; }
                    if(!sti) { dir1 = "down"; sti = setInterval(function () { $(document).scrollTop($(document).scrollTop() + $.vakata.dnd.scroll_spd); }, 150); }
                }
                else { 
                    if(sti && dir1 === "down") { clearInterval(sti); sti = false; }
                }

                if(e.pageX - l < 20) {
                    if(sli && dir2 === "right") { clearInterval(sli); sli = false; }
                    if(!sli) { dir2 = "left"; sli = setInterval(function () { $(document).scrollLeft($(document).scrollLeft() - $.vakata.dnd.scroll_spd); }, 150); }
                }
                else { 
                    if(sli && dir2 === "left") { clearInterval(sli); sli = false; }
                }
                if($(window).width() - (e.pageX - l) < 20) {
                    if(sli && dir2 === "left") { clearInterval(sli); sli = false; }
                    if(!sli) { dir2 = "right"; sli = setInterval(function () { $(document).scrollLeft($(document).scrollLeft() + $.vakata.dnd.scroll_spd); }, 150); }
                }
                else { 
                    if(sli && dir2 === "right") { clearInterval(sli); sli = false; }
                }
            }

            $.vakata.dnd.helper.css({ left : (e.pageX + 5) + "px", top : (e.pageY + 10) + "px" });
            $(document).triggerHandler("drag.vakata", { "event" : e, "data" : $.vakata.dnd.user_data });
        },
        drag_stop : function (e) {
            $(document).unbind("mousemove", $.vakata.dnd.drag);
            $(document).unbind("mouseup", $.vakata.dnd.drag_stop);
            $(document).triggerHandler("drag_stop.vakata", { "event" : e, "data" : $.vakata.dnd.user_data });
            $.vakata.dnd.helper.remove();
            $.vakata.dnd.init_x = 0;
            $.vakata.dnd.init_y = 0;
            $.vakata.dnd.user_data = {};
            $.vakata.dnd.is_down = false;
            $.vakata.dnd.is_drag = false;
        }
    };
    $(function() {
        var css_string = '#vakata-dragged { display:block; margin:0 0 0 0; padding:4px 4px 4px 24px; position:absolute; top:-2000px; line-height:16px; z-index:10000; } ';
        $.vakata.css.add_sheet({ str : css_string });
    });

    $.jstree.plugin("dnd_placeholder", {
        __init : function () {
            this.data.dnd_placeholder = {
                active : false,
                after : false,
                inside : false,
                before : false,
                off : false,
                prepared : false,
                w : 0,
                to1 : false,
                to2 : false,
                cof : false,
                cw : false,
                ch : false,
                i1 : false,
                i2 : false,
                target: null,
            };
            this.get_container()
                // ignore placeholder in rollback
                .bind("get_rollback.jstree", $.proxy(function () { 
                    placeholder.detach().hide();
                }, this))
                // save prepared_move data for later use in check_move
                .bind("prepare_move.jstree", $.proxy(function (e, data) {
                    this.data.dnd_placeholder.prepared_move = data.rslt;
                }, this))
                .bind("mouseenter.jstree", $.proxy(function () {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree && this.data.themes) {
                        m.attr("class", "jstree-" + this.data.themes.theme); 
                        $.vakata.dnd.helper.attr("class", "jstree-dnd-helper jstree-" + this.data.themes.theme);
                    }
                }, this))
                .bind("mouseleave.jstree", $.proxy(function () {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        if(this.data.dnd_placeholder.i1) { clearInterval(this.data.dnd_placeholder.i1); }
                        if(this.data.dnd_placeholder.i2) { clearInterval(this.data.dnd_placeholder.i2); }
                    }
                }, this))
                .bind("mousemove.jstree", $.proxy(function (e) {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        var cnt = this.get_container()[0];

                        // Horizontal scroll
                        if(e.pageX + 24 > this.data.dnd_placeholder.cof.left + this.data.dnd_placeholder.cw) {
                            if(this.data.dnd_placeholder.i1) { clearInterval(this.data.dnd_placeholder.i1); }
                            this.data.dnd_placeholder.i1 = setInterval($.proxy(function () { this.scrollLeft += $.vakata.dnd.scroll_spd; }, cnt), 100);
                        }
                        else if(e.pageX - 24 < this.data.dnd_placeholder.cof.left) {
                            if(this.data.dnd_placeholder.i1) { clearInterval(this.data.dnd_placeholder.i1); }
                            this.data.dnd_placeholder.i1 = setInterval($.proxy(function () { this.scrollLeft -= $.vakata.dnd.scroll_spd; }, cnt), 100);
                        }
                        else {
                            if(this.data.dnd_placeholder.i1) { clearInterval(this.data.dnd_placeholder.i1); }
                        }

                        // Vertical scroll
                        if(e.pageY + 24 > this.data.dnd_placeholder.cof.top + this.data.dnd_placeholder.ch) {
                            if(this.data.dnd_placeholder.i2) { clearInterval(this.data.dnd_placeholder.i2); }
                            this.data.dnd_placeholder.i2 = setInterval($.proxy(function () { this.scrollTop += $.vakata.dnd.scroll_spd; }, cnt), 100);
                        }
                        else if(e.pageY - 24 < this.data.dnd_placeholder.cof.top) {
                            if(this.data.dnd_placeholder.i2) { clearInterval(this.data.dnd_placeholder.i2); }
                            this.data.dnd_placeholder.i2 = setInterval($.proxy(function () { this.scrollTop -= $.vakata.dnd.scroll_spd; }, cnt), 100);
                        }
                        else {
                            if(this.data.dnd_placeholder.i2) { clearInterval(this.data.dnd_placeholder.i2); }
                        }
                    }
                }, this))
                .delegate("a", "mousedown.jstree", $.proxy(function (e) { 
                    if(e.which === 1) {
                        this.start_drag(e.currentTarget, e);
                        return false;
                    }
                }, this))
                .delegate("a", "mouseenter.jstree", $.proxy(function (e) { 
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        placeholder.data("fadeOut", false);
                        this.dnd_enter(e.currentTarget);
                    }
                }, this))
                .delegate("a", "mousemove.jstree", $.proxy(function (e) { 
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        if(typeof this.data.dnd_placeholder.off.top === "undefined") { this.data.dnd_placeholder.off = $(e.target).offset(); }
                        this.data.dnd_placeholder.w = (e.pageY - (this.data.dnd_placeholder.off.top || 0)) % this.data.core.li_height;
                        if(this.data.dnd_placeholder.w < 0) { this.data.dnd_placeholder.w += this.data.core.li_height; }
                        this.dnd_show();
                    }
                }, this))
                .delegate("a", "mouseleave.jstree", $.proxy(function (e) { 
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        // remember last known status for placeholder drop
                        this.data.dnd_placeholder.placeholder = {};
                        this.data.dnd_placeholder.placeholder.dnd_show = this.dnd_show();
                        this.data.dnd_placeholder.placeholder.o = o;
                        this.data.dnd_placeholder.placeholder.r = r;
                        this.data.dnd_placeholder.placeholder.e = e[this._get_settings().dnd_placeholder.copy_modifier + "Key"];

                        this.data.dnd_placeholder.after         = false;
                        this.data.dnd_placeholder.before        = false;
                        this.data.dnd_placeholder.inside        = false;
                        $.vakata.dnd.helper.children("ins").attr("class","jstree-invalid");
                        m.hide();
                        // fade out placeholder if not mouse entering it immediatly
                        placeholder.data("fadeOut", true);
                        setTimeout(function () { if (placeholder.data("fadeOut")) placeholder.detach().hide(); }, 200);

                        if(r && r[0] === e.target.parentNode) {
                            if(this.data.dnd_placeholder.to1) {
                                clearTimeout(this.data.dnd_placeholder.to1);
                                this.data.dnd_placeholder.to1 = false;
                            }
                            if(this.data.dnd_placeholder.to2) {
                                clearTimeout(this.data.dnd_placeholder.to2);
                                this.data.dnd_placeholder.to2 = false;
                            }
                        }
                    }
                }, this))
                .delegate("a", "mouseup.jstree", $.proxy(function (e) { 
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        this.dnd_finish(e);
                    }
                }, this))
                .delegate("#jstree-placeholder", "mouseenter.jstree", $.proxy(function (e) {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        placeholder.data("fadeOut", false);
                        $.vakata.dnd.helper.children("ins").attr("class","jstree-ok");
                    }
                }, this))
                .delegate("#jstree-placeholder", "mouseleave.jstree", $.proxy(function (e) {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        placeholder.data("fadeOut", true);
                        setTimeout(function () { if (placeholder.data("fadeOut")) placeholder.detach().hide(); }, 200);
                        $.vakata.dnd.helper.children("ins").attr("class","jstree-invalid");
                    }
                }, this))
                .delegate("#jstree-placeholder", "mouseup.jstree", $.proxy(function (e) {
                    if($.vakata.dnd.is_drag && $.vakata.dnd.user_data.jstree) {
                        this.dnd_placeholder_finish(e.currentTarget);
                    }
                }, this));

            // stop drag and drop if escape is pressed, depends on jquery hotkeys
            if(typeof $.hotkeys !== "undefined") {
                $(document).bind("keydown", "esc", $.proxy(function (e) {
                    if (this.data.dnd_placeholder.active) {
                        $.vakata.dnd.drag_stop(e);
                        e.preventDefault();
                    }
                }, this));
            }

            $(document)
                .bind("drag_stop.vakata", $.proxy(function () {
                    this.data.dnd_placeholder.after         = false;
                    this.data.dnd_placeholder.before        = false;
                    this.data.dnd_placeholder.inside        = false;
                    this.data.dnd_placeholder.off           = false;
                    this.data.dnd_placeholder.prepared      = false;
                    this.data.dnd_placeholder.w                     = false;
                    this.data.dnd_placeholder.to1           = false;
                    this.data.dnd_placeholder.to2           = false;
                    this.data.dnd_placeholder.active        = false;
                    this.data.dnd_placeholder.foreign       = false;
                    if(m) { m.css({ "top" : "-2000px" }); }
                }, this))
                .bind("drag_start.vakata", $.proxy(function (e, data) {
                    if(data.data.jstree) { 
                        var et = $(data.event.target);
                        if(et.closest(".jstree").hasClass("jstree-" + this.get_index())) {
                            this.dnd_enter(et);
                        }
                    }
                }, this));

            var s = this._get_settings().dnd_placeholder;
            if(s.drag_target) {
                $(document)
                    .delegate(s.drag_target, "mousedown.jstree", $.proxy(function (e) {
                        o = e.target;
                        $.vakata.dnd.drag_start(e, { jstree : true, obj : e.target }, "<ins class='jstree-icon'></ins>" + $(e.target).text() );
                        if(this.data.themes) { 
                                m.attr("class", "jstree-" + this.data.themes.theme); 
                                $.vakata.dnd.helper.attr("class", "jstree-dnd-helper jstree-" + this.data.themes.theme); 
                        }
                        $.vakata.dnd.helper.children("ins").attr("class","jstree-invalid");
                        var cnt = this.get_container();
                        this.data.dnd_placeholder.cof = cnt.offset();
                        this.data.dnd_placeholder.cw = parseInt(cnt.width(),10);
                        this.data.dnd_placeholder.ch = parseInt(cnt.height(),10);
                        this.data.dnd_placeholder.foreign = true;
                        return false;
                    }, this));
            }
            if(s.drop_target) {
                $(document)
                    .delegate(s.drop_target, "mouseenter.jstree", $.proxy(function (e) {
                        if(this.data.dnd_placeholder.active && this._get_settings().dnd_placeholder.drop_check.call(this, { "o" : o, "r" : $(e.target) })) {
                            $.vakata.dnd.helper.children("ins").attr("class","jstree-ok");
                        }
                    }, this))
                    .delegate(s.drop_target, "mouseleave.jstree", $.proxy(function (e) {
                        if(this.data.dnd_placeholder.active) {
                            $.vakata.dnd.helper.children("ins").attr("class","jstree-invalid");
                        }
                    }, this))
                    .delegate(s.drop_target, "mouseup.jstree", $.proxy(function (e) {
                        if(this.data.dnd_placeholder.active && $.vakata.dnd.helper.children("ins").hasClass("jstree-ok")) {
                            this._get_settings().dnd_placeholder.drop_finish.call(this, { "o" : o, "r" : $(e.target) });
                        }
                    }, this));
            }
        },
        defaults : {
            copy_modifier   : "ctrl",
            check_timeout   : 200,
            open_timeout    : 500,
            drop_target             : ".jstree-drop",
            drop_check              : function (data) { return true; },
            drop_finish             : $.noop,
            drag_target             : ".jstree-draggable",
            drag_finish             : $.noop,
            drag_check              : function (data) { return { after : false, before : false, inside : true }; }
        },
        _fn : {
            // overwrite check_move to disable superfluous drop targets
            check_move : function () {
                if (this.data.dnd_placeholder.prepared_move.p == "before" &&
                    this.data.dnd_placeholder.prepared_move.or[0] === this.data.dnd_placeholder.prepared_move.r[0] &&
                    this.data.dnd_placeholder.prepared_move.or.prev()[0] === this.data.dnd_placeholder.prepared_move.o[0]) {
                    return false; 
                } 
                else if (this.data.dnd_placeholder.prepared_move.p == "inside" &&
                    this.data.dnd_placeholder.prepared_move.np[0] === this.data.dnd_placeholder.prepared_move.op[0] &&
                    this.data.dnd_placeholder.prepared_move.o.hasClass("jstree-last")) {
                    return false;
                }
                return this.__call_old();
            },
            dnd_prepare : function () {
                if(!r || !r.length) { return; }
                this.data.dnd_placeholder.off = r.offset();
                if(this._get_settings().core.rtl) {
                    this.data.dnd_placeholder.off.right = this.data.dnd_placeholder.off.left + r.width();
                }
                if(this.data.dnd_placeholder.foreign) {
                    var a = this._get_settings().dnd_placeholder.drag_check.call(this, { "o" : o, "r" : r });
                    this.data.dnd_placeholder.after = a.after;
                    this.data.dnd_placeholder.before = a.before;
                    this.data.dnd_placeholder.inside = a.inside;
                    this.data.dnd_placeholder.prepared = true;
                    return this.dnd_show();
                }
                this.prepare_move(o, r, "before");
                this.data.dnd_placeholder.before = this.check_move();
                this.prepare_move(o, r, "after");
                this.data.dnd_placeholder.after = this.check_move();
                if(this._is_loaded(r)) {
                    this.prepare_move(o, r, "inside");
                    this.data.dnd_placeholder.inside = this.check_move();
                }
                else {
                    this.data.dnd_placeholder.inside = false;
                }
                this.data.dnd_placeholder.prepared = true;
                return this.dnd_show();
            },
            dnd_show : function () {
                if(!this.data.dnd_placeholder.prepared) { return; }
                var o = ["before","inside","after"],
                    r = false,
                    rtl = this._get_settings().core.rtl,
                    pos;
                if(this.data.dnd_placeholder.w < this.data.core.li_height/3) { o = ["before","inside","after"]; }
                else if(this.data.dnd_placeholder.w <= this.data.core.li_height*2/3) {
                    o = this.data.dnd_placeholder.w < this.data.core.li_height/2 ? ["inside","before","after"] : ["inside","after","before"];
                }
                else { o = ["after","inside","before"]; }
                $.each(o, $.proxy(function (i, val) { 
                    if(this.data.dnd_placeholder[val]) {
                        $.vakata.dnd.helper.children("ins").attr("class","jstree-ok");
                        r = val;
                        return false;
                    }
                }, this));
                if(r === false) { $.vakata.dnd.helper.children("ins").attr("class","jstree-invalid"); }
                pos = rtl ? (this.data.dnd_placeholder.off.right - 18) : (this.data.dnd_placeholder.off.left + 10);

                // we are going to show the placeholder, set line height
                placeholder.css("height", this.data.core.li_height);

                switch(r) {
                    case "before":
                        this.move_placeholder(this.data.dnd_placeholder.target, r);
                        break;
                    case "after":
                        this.move_placeholder(this.data.dnd_placeholder.target, r);
                        // only show marker if we are not targeting a leaf
                        if (!this.data.dnd_placeholder.target.hasClass("jstree-leaf"))
                            m.css({ "left" : pos + "px", "top" : (this.data.dnd_placeholder.off.top + this.data.core.li_height - 7) + "px" }).show();
                        break;
                    case "inside":
                        this.move_placeholder(this.data.dnd_placeholder.target, r);
                        m.css({ "left" : pos + ( rtl ? -4 : 4) + "px", "top" : (this.data.dnd_placeholder.off.top + this.data.core.li_height/2 - 5) + "px" }).show();
                        break;
                    default:
                        placeholder.detach().hide();
                        m.hide();
                        break;
                }
                return r;
            },
            move_placeholder : function (target, r) {
                switch(r) {
                    case "before":
                        if (placeholder.next()[0] !== target[0])
                            placeholder.detach().hide().insertBefore(target).fadeIn();
                        break;
                    case "after":
                        if (placeholder.prev()[0] !== target[0])
                            placeholder.detach().hide().insertAfter(target).fadeIn();
                        break;
                    case "inside":
                        placeholder.detach().hide().appendTo(target).fadeIn();
                        break;
                }
            },
            dnd_open : function () {
                this.data.dnd_placeholder.to2 = false;
                this.open_node(r, $.proxy(this.dnd_prepare,this), true);
            },
            dnd_finish : function (e) {
                if(this.data.dnd_placeholder.foreign) {
                    if(this.data.dnd_placeholder.after || this.data.dnd_placeholder.before || this.data.dnd_placeholder.inside) {
                        this._get_settings().dnd_placeholder.drag_finish.call(this, { "o" : o, "r" : r });
                    }
                }
                else {
                    this.dnd_prepare();
                    var pos = this.dnd_show();
                    // hide placeholder immediately again (after showing it) because move_node will NOT work reliably if placeholder is present
                    placeholder.detach().hide();
                    m.hide();

                    this.move_node(o, r, pos, e[this._get_settings().dnd_placeholder.copy_modifier + "Key"]);
                }
                o = false;
                r = false;
            },
            dnd_placeholder_finish : function(e) {
                // hide immidiately to prevent graphic glitch if server is slow
                placeholder.detach().hide();
                m.hide();

                // TODO: test foreign
                    if(this.data.dnd_placeholder.foreign) {
                        if(this.data.dnd_placeholder.placeholder.dnd_show) {
                            this._get_settings().dnd_placeholder.drag_finish.call(this, { "o" : this.data.dnd_placeholder.placeholder.o, "r" : this.data.dnd_placeholder.placeholder.r });
                        }
                    }
                else {
                    this.move_node(this.data.dnd_placeholder.placeholder.o, this.data.dnd_placeholder.placeholder.r, this.data.dnd_placeholder.placeholder.dnd_show, this.data.dnd_placeholder.placeholder.e);
                }
                o = false;
                r = false;
            },
            dnd_enter : function (obj) {
                var s = this._get_settings().dnd_placeholder;
                this.data.dnd_placeholder.prepared = false;
                r = this._get_node(obj);

// save target for place holder
this.data.dnd_placeholder.target = $(obj).parent();

                if(s.check_timeout) { 
                    // do the calculations after a minimal timeout (users tend to drag quickly to the desired location)
                    if(this.data.dnd_placeholder.to1) { clearTimeout(this.data.dnd_placeholder.to1); }
                    this.data.dnd_placeholder.to1 = setTimeout($.proxy(this.dnd_prepare, this), s.check_timeout); 
                }
                else { 
                    this.dnd_prepare(); 
                }
                if(s.open_timeout) { 
                    if(this.data.dnd_placeholder.to2) { clearTimeout(this.data.dnd_placeholder.to2); }
                    if(r && r.length && r.hasClass("jstree-closed")) { 
                        // if the node is closed - open it, then recalculate
                        this.data.dnd_placeholder.to2 = setTimeout($.proxy(this.dnd_open, this), s.open_timeout);
                    }
                }
                else {
                    if(r && r.length && r.hasClass("jstree-closed")) { 
                        this.dnd_open();
                    }
                }
            },
            start_drag : function (obj, e) {
                // HACK: reset li_height because early initialisation returns wrong result
                this.data.core.li_height = this.get_container().find("ul li.jstree-closed, ul li.jstree-leaf").eq(0).height() || 18;

                o = this._get_node(obj);
                if(this.data.ui && this.is_selected(o)) { 
                    o = this._get_node(null, true); 
                }
                else {
                    this.deselect_all();
                    this.select_node(o, true);
                }
                $.vakata.dnd.drag_start(e, { jstree : true, obj : o }, "<ins class='jstree-icon'></ins>" + (o.length > 1 ? "Multiple selection" : this.get_text(o)) );
                if(this.data.themes) { 
                    m.attr("class", "jstree-" + this.data.themes.theme); 
                    $.vakata.dnd.helper.attr("class", "jstree-dnd-helper jstree-" + this.data.themes.theme); 
                }
                var cnt = this.get_container();
                this.data.dnd_placeholder.cof = cnt.children("ul").offset();
                this.data.dnd_placeholder.cw = parseInt(cnt.width(),10);
                this.data.dnd_placeholder.ch = parseInt(cnt.height(),10);
                this.data.dnd_placeholder.active = true;
            }
        }
    });
    $(function() {
        var css_string = '' + 
            '#vakata-dragged ins { display:block; text-decoration:none; width:16px; height:16px; margin:0 0 0 0; padding:0; position:absolute; top:4px; left:4px; } ' + 
            '#vakata-dragged .jstree-ok { background:green; } ' + 
            '#vakata-dragged .jstree-invalid { background:red; } ' + 
            '#jstree-marker { padding:0; margin:0; line-height:12px; font-size:1px; overflow:hidden; height:12px; width:8px; position:absolute; top:-30px; z-index:10000; background-repeat:no-repeat; display:none; background-color:silver; } ';
        $.vakata.css.add_sheet({ str : css_string });
        m = $("<div>").attr({ id : "jstree-marker" }).hide().appendTo("body");
placeholder = $("<li>").attr({ id : 'jstree-placeholder'}).hide();

        $(document).bind("drag_start.vakata", function (e, data) {
            if(data.data.jstree) { 
                m.show(); 
                placeholder.width(data.data.obj.children("a").width());
            }
        });
        $(document).bind("drag_stop.vakata", function (e, data) {
            if(data.data.jstree) { m.hide(); placeholder.detach().hide() }
        });
    });
})(jQuery);

/* 
 * jsTree pedantic HTML data 1.0
 * The pedantic HTML data store. No automatic processing of given html structure. Uses the same settings as the original html data store. 
 */
(function ($) {
    $.jstree.plugin("pedantic_html_data", {
        __init : function () { 
            this.data.pedantic_html_data.original_container_html = this.get_container().children().clone(true);
            // LI nodes must not contain whitespace - otherwise nodes appear a bit to the right
        },
        defaults : { 
            data : false,
            ajax : false,
            correct_state : true
        },
        _fn : {
            load_node : function (obj, s_call, e_call) { var _this = this; this.load_node_html(obj, function () { _this.__callback({ "obj" : obj }); s_call.call(this); }, e_call); },
            _is_loaded : function (obj) { 
                obj = this._get_node(obj); 
                return obj == -1 || !obj || !this._get_settings().pedantic_html_data.ajax || obj.is(".jstree-open, .jstree-leaf") || obj.children("ul").children("li").size() > 0;
            },
            load_node_html : function (obj, s_call, e_call) {
                var d,
                    s = this.get_settings().pedantic_html_data,
                    error_func = function () {},
                    success_func = function () {};
                obj = this._get_node(obj);
                if(obj && obj !== -1) {
                    if(obj.data("jstree-is-loading")) { return; }
                    else { obj.data("jstree-is-loading",true); }
                }
                switch(!0) {
                    case (!s.data && !s.ajax):
                        if(!obj || obj == -1) {
                                this.get_container()
                                        .children("ul")
                                        .replaceWith(this.data.pedantic_html_data.original_container_html)
                                this.clean_node();
                        }
                        if(s_call) { s_call.call(this); }
                        break;
                    case (!!s.data && !s.ajax) || (!!s.data && !!s.ajax && (!obj || obj === -1)):
                        // UNTESTED
                        if(!obj || obj == -1) {
                                d = $(s.data);
                                if(!d.is("ul")) { d = $("<ul>").append(d); }
                                this.get_container()
                                        .children("ul").replaceWith(d)
                                this.clean_node();
                        }
                        if(s_call) { s_call.call(this); }
                        break;
                    case (!s.data && !!s.ajax) || (!!s.data && !!s.ajax && obj && obj !== -1):
                        // UNTESTED
                        obj = this._get_node(obj);
                        error_func = function (x, t, e) {
                            var ef = this.get_settings().pedantic_html_data.ajax.error; 
                            if(ef) { ef.call(this, x, t, e); }
                            if(obj != -1 && obj.length) {
                                obj.children(".jstree-loading").removeClass("jstree-loading");
                                obj.data("jstree-is-loading",false);
                                if(t === "success" && s.correct_state) { obj.removeClass("jstree-open jstree-closed").addClass("jstree-leaf"); }
                            }
                            else {
                                if(t === "success" && s.correct_state) { this.get_container().children("ul").empty(); }
                            }
                            if(e_call) { e_call.call(this); }
                        };
                        success_func = function (d, t, x) {
                            var sf = this.get_settings().pedantic_html_data.ajax.success; 
                            if(sf) { d = sf.call(this,d,t,x) || d; }
                            if(d == "") {
                                return error_func.call(this, x, t, "");
                            }
                            if(d) {
                                d = $(d);
                                if(!d.is("ul")) { d = $("<ul>").append(d); }
                                if(obj == -1 || !obj) { this.get_container().children("ul").replaceWith(d); }
                                else { obj.children(".jstree-loading").removeClass("jstree-loading"); obj.append(d); obj.data("jstree-is-loading",false); }
                                this.clean_node(obj);
                                if(s_call) { s_call.call(this); }
                            }
                            else {
                                if(obj && obj !== -1) {
                                    obj.children(".jstree-loading").removeClass("jstree-loading");
                                    obj.data("jstree-is-loading",false);
                                    if(s.correct_state) { 
                                        obj.removeClass("jstree-open jstree-closed").addClass("jstree-leaf"); 
                                        if(s_call) { s_call.call(this); } 
                                    }
                                }
                                else {
                                    if(s.correct_state) { 
                                        this.get_container().children("ul").empty();
                                        if(s_call) { s_call.call(this); } 
                                    }
                                }
                            }
                        };
                        s.ajax.context = this;
                        s.ajax.error = error_func;
                        s.ajax.success = success_func;
                        if(!s.ajax.dataType) { s.ajax.dataType = "html"; }
                        if($.isFunction(s.ajax.url)) { s.ajax.url = s.ajax.url.call(this, obj); }
                        if($.isFunction(s.ajax.data)) { s.ajax.data = s.ajax.data.call(this, obj); }
                        $.ajax(s.ajax);
                        break;
                }
            }
        }
    });
})(jQuery);
//*/

/*
 * hide / show span when renaming plugin
 */
(function ($) {
    $.jstree.plugin("span", {
        // show span again after rename
        __init : function () {
            this.get_container().bind("rename.jstree", function (e, data) {
                data.rslt.obj.children("span").show();
            });
        },
        // hide span before rename
        _fn : {
            rename : function (obj) {
                node = this._get_node(obj);
                node.children("span").hide();
                // call without any argument, so that original arguments are used
                return this.__call_old();
            }
        },
    });
})(jQuery);
//*/

/*
 * double click rename plugin
 */
(function ($) {
    $.jstree.plugin("dblclick_rename", {
        __init : function () {
            var c = this.get_container();
            c.delegate("a", "dblclick", function (e) {
                c.jstree("rename", this);
                // do not call generic double click handler, which disables text selections 
                e.stopImmediatePropagation();
            });
        },
    });
})(jQuery);
//*/

/*
 * hover tooltips plugin
 */
(function ($) {
    $.jstree.plugin("tooltips", {
                __init : function () {
            var c = this.get_container();
            c.bind("hover_node.jstree", function (e, data) {
                var tooltip = c.jstree("get_text", data.rslt.obj);
                var hint = data.rslt.obj.children("span").text();
                if (hint)
                    tooltip += "  -  " + hint;
                data.rslt.obj.children("a").attr("title", tooltip);
            });
        },
    });
})(jQuery);
//*/

/*
 * select newly created nodes plugin
 */
(function ($) {
    $.jstree.plugin("select_created_nodes", {
                __init : function () {
            var c = this.get_container();
            c.bind("create_node.jstree", function (e, data) {
                c.jstree("deselect_all");
                c.jstree("select_node", data.rslt.obj, true);
            });
        },
    });
})(jQuery);
//*/

/* 
 * jsTree types_from_url plugin 1.0 based on original types plugin
 * Adds support types of nodes
 * You can set an attribute on each li node, that represents its type.
 * According to the type setting the node may get custom icon/validation rules
 */
(function ($) {
    $.jstree.plugin("types_from_url", {
        __init : function () {
            this.data.types_from_url.attach_to = [];
            this.get_container()
                .bind("init.jstree", $.proxy(function () { 
                    this._load_type_settings();
                }, this))
                .bind("before.jstree", $.proxy(function (e, data) { 
                    if($.inArray(data.func, this.data.types_from_url.attach_to) !== -1) {
                        var s = this._get_settings().types_from_url.types,
                            t = this._get_type(data.args[0]);
                        if(
                            ( 
                                (s[t] && typeof s[t][data.func] !== "undefined") || 
                                (s["default"] && typeof s["default"][data.func] !== "undefined")
                            ) && !this._check(data.func, data.args[0])
                        ) {
                            e.stopImmediatePropagation();
                            return false;
                        }
                    }
                }, this));
            },
            defaults : {
                // defines maximum number of root nodes (-1 means unlimited, -2 means disable max_children checking)
                max_children: -2,
                // defines the maximum depth of the tree (-1 means unlimited, -2 means disable max_depth checking)
                max_depth: -2,
                // defines valid node types for the root nodes
                valid_children: "none",

                // where is the type stores (the rel attribute of the LI element)
                type_attr : "rel",
                // a list of types
                types : {
                    // the default type
                    "default" : {
                        "max_children": -2,
                        "max_depth": -2,
                        "valid_children": "none",
                        "delete_node": false,
                        "remove" : false

                        // Bound functions - you can bind any other function here (using boolean or function)
                        //"select_node": true,
                        //"open_node": true,
                        //"close_node": true,
                        //"create_node": true,
                    }
                }
            },
            _fn : {
                _load_type_settings : function() {
                    var _this = this;
                    var url = this.get_container().attr("data-types-settings-url");

                    $.getJSON(url, function(new_types_settings) {
                        _this._set_settings(new_types_settings);
                        _this.data.types_from_url.attach_to = [];

                        var s = _this._get_settings().types_from_url;
                        var types = s.types, 
                            attr  = s.type_attr, 
                            icons_css = ""; 

                        $.each(types, function (i, tp) {
                            $.each(tp, function (k, v) { 
                                if(!/^(max_depth|max_children|icon|valid_children)$/.test(k)) { _this.data.types_from_url.attach_to.push(k); }
                            });
                            if(!tp.icon) { return true; }
                            if( tp.icon.image || tp.icon.position) {
                                if(i == "default")  { icons_css += '.jstree-' + _this.get_index() + ' a > .jstree-icon { '; }
                                else                                { icons_css += '.jstree-' + _this.get_index() + ' li[' + attr + '=' + i + '] > a > .jstree-icon { '; }
                                if(tp.icon.image)   { icons_css += ' background-image:url(' + tp.icon.image + '); '; }
                                if(tp.icon.position){ icons_css += ' background-position:' + tp.icon.position + '; '; }
                                else                                { icons_css += ' background-position:0 0; '; }
                                icons_css += '} ';
                            }
                        });
                        if(icons_css != "") { $.vakata.css.add_sheet({ 'str' : icons_css }); }
                    });
                },
                _get_type : function (obj) {
                    obj = this._get_node(obj);
                    return (!obj || !obj.length) ? false : obj.attr(this._get_settings().types_from_url.type_attr) || "default";
                },
                set_type : function (str, obj) {
                    obj = this._get_node(obj);
                    return (!obj.length || !str) ? false : obj.attr(this._get_settings().types_from_url.type_attr, str);
                },
                _check : function (rule, obj, opts) {
                    var v = false, t = this._get_type(obj), d = 0, _this = this, s = this._get_settings().types_from_url;
                    if(obj === -1) { 
                        if(!!s[rule]) { v = s[rule]; }
                        else { return; }
                    }
                    else {
                        if(t === false) { return; }
                        if(!!s.types[t] && !!s.types[t][rule]) { v = s.types[t][rule]; }
                        else if(!!s.types["default"] && !!s.types["default"][rule]) { v = s.types["default"][rule]; }
                    }
                    if($.isFunction(v)) { v = v.call(this, obj); }
                    if(rule === "max_depth" && obj !== -1 && opts !== false && s.max_depth !== -2 && v !== 0) {
                        // also include the node itself - otherwise if root node it is not checked
                        this._get_node(obj).children("a:eq(0)").parentsUntil(".jstree","li").each(function (i) {
                            // check if current depth already exceeds global tree depth
                            if(s.max_depth !== -1 && s.max_depth - (i + 1) <= 0) { v = 0; return false; }
                            d = (i === 0) ? v : _this._check(rule, this, false);
                            // check if current node max depth is already matched or exceeded
                            if(d !== -1 && d - (i + 1) <= 0) { v = 0; return false; }
                            // otherwise - set the max depth to the current value minus current depth
                            if(d >= 0 && (d - (i + 1) < v || v < 0) ) { v = d - (i + 1); }
                            // if the global tree depth exists and it minus the nodes calculated so far is less than `v` or `v` is unlimited
                            if(s.max_depth >= 0 && (s.max_depth - (i + 1) < v || v < 0) ) { v = s.max_depth - (i + 1); }
                        });
                    }
                    return v;
                },
                check_move : function () {
                    if(!this.__call_old()) { return false; }
                    var m  = this._get_move(),
                        s  = m.rt._get_settings().types_from_url,
                        mc = m.rt._check("max_children", m.cr),
                        md = m.rt._check("max_depth", m.cr),
                        vc = m.rt._check("valid_children", m.cr),
                        ch = 0, d = 1, t;

                    if(vc === "none") { return false; } 
                    if($.isArray(vc) && m.ot && m.ot._get_type) {
                        m.o.each(function () {
                            if($.inArray(m.ot._get_type(this), vc) === -1) { d = false; return false; }
                        });
                        if(d === false) { return false; }
                    }
                    if(s.max_children !== -2 && mc !== -1) {
                        ch = m.cr === -1 ? this.get_container().children("> ul > li").not(m.o).length : m.cr.children("> ul > li").not(m.o).length;
                        if(ch + m.o.length > mc) { return false; }
                    }
                    if(s.max_depth !== -2 && md !== -1) {
                        d = 0;
                        if(md === 0) { return false; }
                        if(typeof m.o.d === "undefined") {
                            // TODO: deal with progressive rendering and async when checking max_depth (how to know the depth of the moved node)
                            t = m.o;
                            while(t.length > 0) {
                                t = t.find("> ul > li");
                                d ++;
                            }
                            m.o.d = d;
                        }
                        if(md - m.o.d < 0) { return false; }
                    }
                    return true;
                },
                create_node : function (obj, position, js, callback, is_loaded, skip_check) {
                    if(!skip_check && (is_loaded || this._is_loaded(obj))) {
                        var p  = (position && position.match(/^before|after$/i) && obj !== -1) ? this._get_parent(obj) : this._get_node(obj),
                            s  = this._get_settings().types_from_url,
                            mc = this._check("max_children", p),
                            md = this._check("max_depth", p),
                            vc = this._check("valid_children", p),
                            ch;
                        if(!js) { js = {}; }
                        if(vc === "none") { return false; } 
                        if($.isArray(vc)) {
                            if(!js.attr || !js.attr[s.type_attr]) { 
                                if(!js.attr) { js.attr = {}; }
                                js.attr[s.type_attr] = vc[0]; 
                            } else {
                                if($.inArray(js.attr[s.type_attr], vc) === -1) { return false; }
                            }
                        }
                        if(s.max_children !== -2 && mc !== -1) {
                            ch = p === -1 ? this.get_container().children("> ul > li").length : p.children("> ul > li").length;
                            if(ch + 1 > mc) { return false; }
                        }
                        if(s.max_depth !== -2 && md !== -1 && (md - 1) < 0) { return false; }
                    }
                    return this.__call_old(true, obj, position, js, callback, is_loaded, skip_check);
                }
            }
        });
    })(jQuery);
//*/

/*
 * delta updates
 *   expects JSON strings from Server, mapping ids to html code 
 *   config params:
 *     url
 *     fallbackPollURL
 */
(function ($) {
    $.jstree.plugin("delta_updates", {
        __init : function () {
            var tree = this.get_container();
            var settings = this.get_settings().delta_updates;
            this.data.delta_updates.active_ajax_requests = 0;
            this.data.delta_updates.pending_updates = [];

            var webSocketURL = "";
            if (settings.webSocketURL != "") {
                webSocketURL = settings.webSocketURL + tree.attr("data-doc-id");
            }
            this.data.delta_updates.ws = $.gracefulWebSocket(webSocketURL, {
                fallbackPollURL: settings.fallbackPollURL,
                fallbackPollParams:  {
                    "seq_nr": function () {
                        return tree.attr("data-seq-nr");
                    },
                    "doc_id": function () {
                        return tree.attr("data-doc-id");
                    }
                }
            });
            
            this.data.delta_updates.ws.onmessage = $.proxy(function (event) {
                if (event.data) {
                    this.data.delta_updates.pending_updates.push(event);
                    // only apply delta updates if no updates are in progress
                    // pending delta updates are applied when local update ajax calls return
                    if (!this.data.delta_updates.active_ajax_requests) {
                        this.apply_delta_updates();
                    }
                }
            }, this);

            var _this = this;

            tree.bind("create.jstree", function (e, data) {
                var parent = data.rslt.parent;
                if (parent == -1) {
                    parent = tree;
                }

                _this._init_update_seq();
                _this._ajax_call({
                    operation : "create_node",
                    data : {
                        "doc_id" : tree.attr("data-doc-id"),
                        "target_id" : parent.attr("id").replace("node_",""), 
                        "position" : data.rslt.position,
                        "node" : {
                            // TODO: include every .data(...) attribute
                            "_type" : data.rslt.obj.attr("rel"),
                            "name" : data.rslt.name
                        }
                    },
                    success : function (r) {
                        if(r.status) {
                            $(data.rslt.obj).attr("id", "node_" + r.id);
                        }
                        else {
                            _this._rollback_in_order(this.seq, data.rlbk);
                        }
                    },
                    rollback : data.rlbk,
                });
            })
            .bind("rename.jstree", function (e, data) {
                _this._init_update_seq();
                _this._ajax_call({
                    operation : "rename_node",
                    data : {
                        "doc_id" : tree.attr("data-doc-id"),
                        "id" : data.rslt.obj.attr("id").replace("node_",""),
                        "name" : data.rslt.new_name
                    },
                    success : function (r) {
                        if(!r.status) {
                            _this._rollback_in_order(this.seq, data.rlbk);
                        }
                    },
                    rollback : data.rlbk,
                });
            })
            .bind("move_node.jstree", function (e, data) {
                _this._init_update_seq();
                data.rslt.o.each(function (i) {
                    _this._ajax_call({
                        operation : "move_node",
                        data : {
                            "doc_id" : tree.attr("data-doc-id"),
                            "id" : $(this).attr("id").replace("node_",""),
                            "target_id" : data.rslt.np.attr("id").replace("node_",""),
                            "position" : data.rslt.cp + i,
                            "copy" : data.rslt.cy ? 1 : 0
                        },
                        success : function (r) {
                            if(r.status) {
                                $(data.rslt.oc).attr("id", "node_" + r.id);
                            }
                            else {
                                _this._rollback_in_order(this.seq, data.rlbk);
                            }
                        },
                        rollback : data.rlbk,
                    });
                });
            })
            .bind("remove.jstree", function (e, data) {
                _this._init_update_seq();
                data.rslt.obj.each(function () {
                    _this._ajax_call({
                        operation : "remove_node",
                        data : {
                            "doc_id" : tree.attr("data-doc-id"),
                            "id" : this.id.replace("node_","")
                        },
                        success : function (r) {
                            if(!r.status) {
                                _this._rollback_in_order(this.seq, data.rlbk);
                            }
                        },
                        rollback : data.rlbk,
                    });
                });
            });
        },
        _fn : {
            apply_delta_updates : function () {
                var tree = this.get_container();

                $.each(this.data.delta_updates.pending_updates, function (index, event) {
                    try {
                        var data = $.evalJSON(event.data);
                    } catch (e) {
                        // continue
                        return true;
                    }

                    // only overwrite tree nodes if data is newer
                    var old_seq_nr = parseInt(tree.attr("data-seq-nr"));
                    var new_seq_nr = parseInt(data.seq_nr);
                    if (new_seq_nr > old_seq_nr) {
                        // remember which tree nodes were open
                        var open_nodes = $(".jstree-open");
                        var clicked_node = $(".jstree-clicked").parent();

                        for (var id in data.nodes) {
                            if (data.nodes[id]) {
                                var node = $("#node_" + id);
                                var second_level_childs = node.find("> ul > li:has(ul)");

                                var children = node.children("ul");
                                // replace children if present, else create new children by appending
                                if (children.length)
                                    children.replaceWith(data.nodes[id]);
                                else
                                    node.append(data.nodes[id]);

                                // reattach second level children
                                second_level_childs.each(function () {
                                    var node = $("#" + $(this).attr("id"));
                                    // only append old ul if there is no ul already present, because this was a partial update
                                    if (!node.children("ul").length)
                                        node.append($(this).children("ul"));
                                });
                            }
                        }

                        // all jstree-open classes were lost: restore them
                        open_nodes.each(function () {
                            $("#" + $(this).attr("id")).filter(":has(li)").addClass("jstree-open");
                        });
                        // jstree-clicked class was lost: restore it
                        $("#" + clicked_node.attr("id")).children("a").addClass("jstree-clicked");

                        // fix up remaining jstree classes
                        tree.jstree("clean_node");

                        tree.attr("data-seq-nr", new_seq_nr);
                    }
                });

                this.data.delta_updates.pending_updates = [];
            },        
            _init_update_seq : function () {
                if (!this.data.delta_updates.active_ajax_requests) {
                    this.data.delta_updates.last_rollback = 2147483647;
                    this.data.delta_updates.seq = 0;
                }
            },
            _rollback_in_order : function (seq, rlbk) {
                // only allow rollbacks in correct order
                // in case rollback overwrites a successful update wait for a delta update
                if (seq < this.data.delta_updates.last_rollback) {
                    this.data.delta_updates.last_rollback = seq;
                    $.jstree.rollback(rlbk);
                    // poll newest delta updates to prevent a 3 sec wait if polling is neccessary
                    if (this.data.delta_updates.ws.poll)
                        this.data.delta_updates.ws.poll();
                }
            },
            _ajax_call : function (args) {
                var tree = this.get_container();
                var settings = this.get_settings().delta_updates;
                var _this = this;

                $.ajax({
                    seq : _this.data.delta_updates.seq++,
                    async : true,
                    type: 'POST',
                    url: settings.postURL + args.operation,
                    data : args.data,
                    beforeSend : function () {
                        _this.data.delta_updates.active_ajax_requests += 1;
                    },
                    success : args.success,
                    error : function () {
                        // TODO: untested
                        _this._rollback_in_order(this.seq, args.rollback);
                    },
                    complete : function () {
                        _this.data.delta_updates.active_ajax_requests -= 1;
                        // apply delta updates if this is last outstanding request
                        if (!_this.data.delta_updates.active_ajax_requests) {
                            _this.apply_delta_updates();
                        }
                    }
                });
            },
        },
    });
})(jQuery);


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
            },
        },
    });
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
