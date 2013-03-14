// {{{ typesfromurl
/**
 *
 * jsTree typesfromurl plugin 1.0 based on original types plugin
 * Adds support types of nodes
 * You can set an attribute on each li node, that represents its type.
 * According to the type setting the node may get custom icon/validation rules
 *
 * @require framework/shared/depage-jquery-plugins/depage-shy-dialogue.js
 *
 */
(function ($) {
    $.jstree.plugin("typesfromurl", {
        __construct : function () {
            this._load_type_settings();

            /* DEPRECATE
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
                */
            },

            defaults : {
                // defines maximum number of root nodes (-1 means unlimited, -2 means disable max_children checking)
                max_children: -2,
                // defines the maximum depth of the tree (-1 means unlimited, -2 means disable max_depth checking)
                max_depth: -2,

                // where is the type stored (the rel attribute of the LI element)
                type_attr: "rel",

                // defines valid node types for the root nodes
                valid_children: {},
                valid_parents: {},
                available_nodes: {},

                // bound functions - you can bind any other function here (using boolean or function)
                "delete_node": true,
                "move_node": true,
                "select_node": true,
                "open_node": true,
                "close_node": true,
                "create_node": true,
                "rename_node": true,
                "copy_node": true,
                "paste_node": true,
                "cut_node": true
            },

            _fn : {
                _load_type_settings : function() {
                    var _this = this;
                    var url = this.get_container().attr("data-types-settings-url");

                    $.getJSON(url, function(new_types_settings) {
                        // get a reference to the settings
                        var s =  _this.get_settings(true).typesfromurl;

                        // write the new settings
                        $.extend(true, s, new_types_settings.typesfromurl);

                        // add create context menu
                        /*
                        $.each(s.available_nodes, function (type, node) {

                            // check create is not disabled
                            if (typeof(node.attributes.create_node) !== "undefined"
                                && !node.attributes.create_node) { return true; }

                            // add 'create' to the context menu items
                            _this.get_settings()['contextmenu']['items'](null, {
                                "create" : {
                                    "separator_before"  : false,
                                    "separator_after"   : true,
                                    "label"             : "Create",
                                    "action"            : function (data) {
                                        var inst = $.jstree._reference(data.reference),
                                            obj = inst.get_node(data.reference);

                                        inst.create_node(obj, {}, "last", function (new_node) {
                                            setTimeout(function () {
                                                inst.edit(new_node);
                                            }, 0);
                                    });
                                }
                            }});
                        });
                        */

                        // build valid children
                        $.each(s.valid_parents, function(parent, children) {
                            $.each(children, function(i, child) {
                                if(typeof(s.valid_children[child]) === 'undefined') {
                                    s.valid_children[child] = {};
                                }
                                if (typeof(s.available_nodes[parent]) !== 'undefined') {
                                    s.valid_children[child][parent] = s.available_nodes[parent];
                                }
                            });
                        });

                        // build icons css
                        icons_css = "";
                        $.each(s.available_nodes, function (type, node) {
                            if( node.icon ) {
                                icons_css = '.jstree-' + _this.get_index() + ' li[' + s.type_attr + '=' + type + '] > a > .jstree-icon {' +
                                        'background-image:url(' + type.icon.image + ');' +
                                        'background-position:0 0;' +
                                    '}';

                            }
                        });

                        // add icons
                        if(icons_css != "") {
                            $.vakata.css.add_sheet({ 'str' : icons_css });
                        }
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

                /**
                 * Check
                 *
                 * @param event - event function
                 * @param element - original
                 * @param target - target
                 * @param index - index position
                 * @return {Boolean}
                 */
                check : function (event, element, target, index) {
                    if(target === -1) {return false;}

                    var s  = this.get_settings().typesfromurl;

                    // event disabled (master)
                    if(typeof(s[event]) === "undefined" || !s[event]) { return false; }

                    // no type defined
                    if(typeof(element.attr("rel")) === "undefined") { return false; }

                    var type = element.attr("rel");

                    // node unavailable
                    if (typeof(s.available_nodes[type]) === "undefined" ) { return false; }

                    // check operation is not disabled for node for node
                    if (typeof(s.available_nodes[type]["attributes"][event]) !== "undefined"
                        && !s.available_nodes[type]["attributes"][event]) { return false; }

                    switch(event) {

                        case "move_node":

                            if ( target.is(element) || $.contains(element[0], target[0]) ) {
                                return false
                            }


                        case "copy_node":
                        case "create_node":

                            // check max children
                            if(s.max_children !== -2 && s.max_children !== -1) {
                                var children = target.children('li').siblings().length;
                                if(children > s.max_children) {
                                    return false;
                                }
                            }

                            // check max depth
                            if(s.max_depth !== -2 && s.max_depth !== -1) {
                                var depth = element.parentsUntil(this.get_container(), 'ul').length;
                                if(depth > s.max_depth) {
                                    return false;
                                }
                            }

                            // type has no available parents defined
                            if(!$.isArray(s.valid_parents[type])) {
                                return false;
                            }

                            // wildcard all
                            if ($.inArray('*', s.valid_parents[type]) === -1) {

                                // the target is not in the available parents
                                if ($.inArray(target.attr("rel"), s.valid_parents[type]) === -1) {
                                    return false;
                                }
                            }
                    }

                    return true;
                },

                /**
                 * create_node
                 *
                 * @param parent - parent node
                 * @param type - type of node to create
                 * @param position - index to create at
                 *
                 * @return node
                 */
                create_node : function (parent, type, position) {

                    parent = this.get_node(parent);
                    position = position || "last";

                    var node =  this.get_settings().typesfromurl.available_nodes[type];

                    var li = $("<li />").attr({rel: type}).append($("<a />").html(node.new).attr({href:"#"}));

                    var new_parent = this.get_parent(parent);

                    if (new_parent === -1) {
                        new_parent = parent.parents('.jstree-0');
                    }

                    switch(position) {
                        case "before":
                            position = parent.index();
                            parent = new_parent;
                            break;
                        case "after" :
                            position = parent.index() + 1;
                            parent = new_parent;
                            break;
                        case "inside":
                        case "first":
                            position = 0;
                            break;
                        case "last":
                            position = parent.children('ul').children('li').length;
                            break;
                        default:
                            position = 0;
                            break;
                    }

                    parent.children("ul").children("li").eq(position).before(li);

                    // fire the callback to send the ajax request
                    this.__callback({ "obj" : li, "parent" : parent, "position" : position });

                    return li;
                }

                /* DEPRECATE

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
                */
            }
        });
    })(jQuery);
// }}}
