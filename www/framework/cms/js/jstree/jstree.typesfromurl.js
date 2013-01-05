// {{{ typesfromurl
/* 
 * jsTree typesfromurl plugin 1.0 based on original types plugin
 * Adds support types of nodes
 * You can set an attribute on each li node, that represents its type.
 * According to the type setting the node may get custom icon/validation rules
 */
(function ($) {
    $.jstree.plugin("typesfromurl", {
        __construct : function () {
            this._load_type_settings();
            this.data.typesfromurl.attach_to = [];
            this.get_container()
                .bind("before.jstree", $.proxy(function (e, data) { 
                    if($.inArray(data.func, this.data.typesfromurl.attach_to) !== -1) {
                        var s = this.get_settings().typesfromurl.types,
                            t = this.get_type(data.args[0]);
                        if(( 
                                (s[t] && typeof s[t][data.func] !== "undefined") || 
                                (s["default"] && typeof s["default"][data.func] !== "undefined")
                            ) && !this.check(data.func, data.args[0])
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
                valid_children: [],
                valid_parents: [],
                available_node: [],

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
                        new_types_settings.typesfromurl.attach_to = [];
                        this.settings = $.extend(true, {}, this.settings, new_types_settings);

                        var s = _this.get_settings().typesfromurl;
                        var types = s.available_nodes, 
                            icons_css = ""; 

                        console.log(this.settings);
                        console.log(types);
                        //console.log(_this.data.typesfromurl);
                        /*
                        $.each(types, function (i, tp) {
                            $.each(tp, function (k, v) { 
                                if(!/^(max_depth|max_children|icon|valid_parents|available_nodes)$/.test(k)) { _this.data.typesfromurl.attach_to.push(k); }
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
                        */
                    });
                },
                get_type : function (obj) {
                    obj = this.get_node(obj);
                    return (!obj || !obj.length) ? false : obj.attr(this.get_settings().typesfromurl.type_attr) || "default";
                },
                set_type : function (str, obj) {
                    obj = this.get_node(obj);
                    return (!obj.length || !str) ? false : obj.attr(this.get_settings().typesfromurl.type_attr, str);
                },
                //check : function (chk, obj, par, pos) {
                check : function (rule, obj, opts) {
                    var v = false, t = this.get_type(obj), d = 0, _this = this, s = this.get_settings().typesfromurl;
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
                        this.get_node(obj).children("a:eq(0)").parentsUntil(".jstree","li").each(function (i) {
                            // check if current depth already exceeds global tree depth
                            if(s.max_depth !== -1 && s.max_depth - (i + 1) <= 0) { v = 0; return false; }
                            d = (i === 0) ? v : _this.check(rule, this, false);
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
                        s  = m.rt.get_settings().typesfromurl,
                        mc = m.rt.check("max_children", m.cr),
                        md = m.rt.check("max_depth", m.cr),
                        vc = m.rt.check("valid_children", m.cr),
                        ch = 0, d = 1, t;

                    if(vc === "none") { return false; } 
                    if($.isArray(vc) && m.ot && m.ot.get_type) {
                        m.o.each(function () {
                            if($.inArray(m.ot.get_type(this), vc) === -1) { d = false; return false; }
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
                        var p  = (position && position.match(/^before|after$/i) && obj !== -1) ? this.get_parent(obj) : this.get_node(obj),
                            s  = this.get_settings().typesfromurl,
                            mc = this.check("max_children", p),
                            md = this.check("max_depth", p),
                            vc = this.check("valid_children", p),
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
// }}}
