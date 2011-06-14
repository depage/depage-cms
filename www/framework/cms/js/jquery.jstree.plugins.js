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

							this.data.dnd_placeholder.after		= false;
							this.data.dnd_placeholder.before	= false;
							this.data.dnd_placeholder.inside	= false;
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
						this.data.dnd_placeholder.after		= false;
						this.data.dnd_placeholder.before	= false;
						this.data.dnd_placeholder.inside	= false;
						this.data.dnd_placeholder.off		= false;
						this.data.dnd_placeholder.prepared	= false;
						this.data.dnd_placeholder.w			= false;
						this.data.dnd_placeholder.to1		= false;
						this.data.dnd_placeholder.to2		= false;
						this.data.dnd_placeholder.active	= false;
						this.data.dnd_placeholder.foreign	= false;
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
			copy_modifier	: "ctrl",
			check_timeout	: 200,
			open_timeout	: 500,
			drop_target		: ".jstree-drop",
			drop_check		: function (data) { return true; },
			drop_finish		: $.noop,
			drag_target		: ".jstree-draggable",
			drag_finish		: $.noop,
			drag_check		: function (data) { return { after : false, before : false, inside : true }; }
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

            this.data.delta_updates.ws = $.gracefulWebSocket(settings.webSocketURL, {
                fallbackPollURL: settings.fallbackPollURL,
                fallbackPollParams:  {
                    "seq_nr": function () {
                        return tree.data("seq_nr");
                    },
                    "doc_id": function () {
                        return tree.data("doc_id");
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
                _this._init_update_seq();
                _this._ajax_call({
                    operation : "create_node",
                    data : {
                        "doc_id" : tree.data("doc_id"),
                        "target_id" : data.rslt.parent.attr("id").replace("node_",""), 
                        "position" : data.rslt.position,
                        "node" : {
                            // TODO: include every .data(...) attribute
                            "type" : data.rslt.obj.attr("rel"),
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
                        "doc_id" : tree.data("doc_id"),
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
                            "doc_id" : tree.data("doc_id"),
                            "id" : $(this).attr("id").replace("node_",""),
                            "target_id" : data.rslt.np.attr("id").replace("node_",""),
                            "position" : data.rslt.cp + i,
                            "copy" : data.rslt.cy ? 1 : 0
                        },
                        success : function (r) {
                            if(r.status) {
                                $(data.rslt.oc).attr("id", "node_" + r.id);
                                if(data.rslt.cy && $(data.rslt.oc).children("UL").length) {
                                    data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                                }
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
                            "doc_id" : tree.data("doc_id"),
                            "id" : this.id.replace("node_","")
                        },
                        success : function (r) {
                            if(!r.status) {
                                data.inst.refresh();
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
                    var data = $.evalJSON(event.data);
                    // only overwrite tree nodes if data is newer
                    var old_seq_nr = parseInt(tree.data("seq_nr"));
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
                                    $("#" + $(this).attr("id")).append($(this).children("ul"));
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

                        tree.data("seq_nr", new_seq_nr);
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



