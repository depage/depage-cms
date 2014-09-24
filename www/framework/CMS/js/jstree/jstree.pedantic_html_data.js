// {{{ pedantic_html_data
/* 
 * jsTree pedantic HTML data 1.0
 * The pedantic HTML data store. No automatic processing of given html structure. Uses the same settings as the original html data store. 
 */
(function ($) {
    $.jstree.plugin("pedantic_html_data", {
        __construct : function () { 
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
                obj = this.get_node(obj); 
                return obj == -1 || !obj || !this._get_settings().pedantic_html_data.ajax || obj.is(".jstree-open, .jstree-leaf") || obj.children("ul").children("li").size() > 0;
            },
            load_node_html : function (obj, s_call, e_call) {
                var d,
                    s = this.get_settings().pedantic_html_data,
                    error_func = function () {},
                    success_func = function () {};
                obj = this.get_node(obj);
                if(obj && obj !== -1) {
                    if(obj.data("jstree-is-loading")) { return; }
                    else { obj.data("jstree-is-loading",true); }
                }
                switch(!0) {
                    case (!s.data && !s.ajax):
                        if(!obj || obj == -1) {
                                /*
                                console.log(obj);
                                console.log(this.data);
                                console.log(this.data.pedantic_html_data);
                                console.log(this.data.pedantic_html_data.original_container_html);
                                */
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
                        obj = this.get_node(obj);
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
// }} }}}
