/*
 * delta updates
 *   expects JSON strings from Server, mapping ids to html code 
 *   config params:
 *     url
 *     fallbackPollURL
 */
(function ($) {
    $.jstree.plugin("deltaupdates", {
        __construct : function () {
            var tree = this.get_container();
            var settings = this.get_settings().deltaupdates;
            this.data.deltaupdates.active_ajax_requests = 0;
            this.data.deltaupdates.pending_updates = [];

            var webSocketURL = "";
            if (settings.webSocketURL != "") {
                webSocketURL = settings.webSocketURL + tree.attr("data-doc-id");
            }

            this.data.deltaupdates.ws = $.gracefulWebSocket(webSocketURL, {
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

            this.data.deltaupdates.ws.onmessage = $.proxy(function (event) {
                if (event.data) {
                    this.data.deltaupdates.pending_updates.push(event);
                    // only apply delta updates if no updates are in progress
                    // pending delta updates are applied when local update ajax calls return
                    if (!this.data.deltaupdates.active_ajax_requests) {
                        if (!$(".jstree-rename-input").unbind('end_edit').bind('end_edit', function() { _this.apply_delta_updates(); }).length) {
                            _this.apply_delta_updates();
                        }
                    }
                }
            }, this);

            var _this = this;

            // {{{ event: create_node.jstree
            tree.bind("create_node.jstree", function (e, data) {
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
                            _this.refresh();
                        }
                    },
                    rollback : data.rlbk
                });
            });
            // }}}
            // {{{ event: rename_node.jstree
            tree.bind("rename_node.jstree", function (e, data) {
                _this._init_update_seq();
                var d = {
                    "doc_id" : tree.attr("data-doc-id"),
                    "id" : data.rslt.obj.attr("id").replace("node_",""),
                    "name" : data.rslt.title
                };
                _this._ajax_call({
                    operation : "rename_node",
                    data : d,
                    success : function (r) {
                        if(!r.status) {
                            _this.refresh();
                        }
                    },
                    rollback : data.rlbk
                });
            });
            // }}}
            // {{{ event: move_node.jstree
            tree.bind("move_node.jstree", function (e, data) {
                _this._init_update_seq();
                data.rslt.obj.each(function (i) {
                    var d = {
                        "doc_id" : tree.attr("data-doc-id"),
                        "id" : $(this).attr("id").replace("node_",""),
                        "target_id" : data.rslt.parent !== -1 ? data.rslt.parent.attr("id").replace("node_","") : -1,
                        "position" : data.rslt.position
                    };

                    _this._ajax_call({
                        operation : "move_node",
                        data : d,
                        success : function (r) {
                            if(r.status) {
                                $(data.rslt.oc).attr("id", "node_" + r.id);
                            }
                            else {
                                _this.refresh();
                            }
                        },
                        rollback : data.rlbk
                    });
                });
            });
            // }}}
            // {{{ event: copy_node.jstree
            tree.bind("copy_node.jstree", function (e, data) {
                _this._init_update_seq();
                data.rslt.obj.each(function (i) {
                    var d = {
                        "doc_id" : tree.attr("data-doc-id"),
                        "id" : $(this).attr("id").replace("copy_node_",""),
                        "target_id" : data.rslt.parent.attr("id").replace("node_",""),
                        "position" : data.rslt.position
                    };

                    _this._ajax_call({
                        operation : "copy_node",
                        data : d,
                        success : function (r) {
                            if(r.status) {
                                $(data.rslt.oc).attr("id", "node_" + r.id);

                                var $node = $(data.rslt.obj);
                                $node.attr("id", "node_" + r.id);

                                var inst = $.jstree._reference($node);

                                // TODO maybe not here?
                                var $tmp = $node.clone();
                                $tmp.find('a').children().remove();
                                var text = $tmp.find('a').text();

                                inst.edit($node);
                            }
                            else {
                                _this.refresh();
                            }
                        },
                        rollback : data.rlbk
                    });
                });
            });
            // }}}
            // {{{ event: duplicate_node.jstree
            tree.bind("duplicate_node.jstree", function (e, data) {
                _this._init_update_seq();
                data.rslt.obj.each(function (i) {
                    var d = {
                        "doc_id" : tree.attr("data-doc-id"),
                        "id" : $(this).attr("id").replace("duplicate_node_","")
                    };

                    _this._ajax_call({
                        operation : "duplicate_node",
                        data : d,
                        success : function (r) {
                            if(r.status) {
                                var $node = $(data.rslt.obj);
                                $node.attr("id", "node_" + r.id);

                                var inst = $.jstree._reference($node);

                                // TODO maybe not here?
                                var $tmp = $node.clone();
                                $tmp.find('a').children().remove();
                                var text = $tmp.find('a').text();

                                if (text.match(/\d+$/)) {
                                    text = text.replace(/\d+$/, function(n){ return ++n });  // increment if last char numeric
                                } else {
                                    text += ' 1';
                                }

                                inst.edit($node, text);
                            }
                            else {
                                _this.refresh();
                            }
                        },
                        rollback : data.rlbk
                    });
                });
            });
            // }}}
            // {{{ event: delete_node.jstree
            tree.bind("delete_node.jstree", function (e, data) {
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
                                _this.refresh();
                            }
                        },
                        rollback : data.rlbk,
                    });
                });
            });
            // }}}
        },
        _fn : {
            // {{{ apply_delta_updates
            apply_delta_updates : function () {
                var tree = this.get_container();

                $.each(this.data.deltaupdates.pending_updates, function (index, event) {
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
                                if (children.length) {
                                    children.replaceWith(data.nodes[id]);
                                } else {
                                    node.append(data.nodes[id]);
                                }

                                // reattach second level children
                                second_level_childs.each(function () {
                                    var node = $("#" + $(this).attr("id"));
                                    // only append old ul if there is no ul already present, because this was a partial update
                                    if (!node.children("ul").length)
                                        node.append($(this).children("ul"));
                                });

                                // fix up remaining jstree classes
                                tree.jstree("clean_node", node);
                            }
                        }

                        // all jstree-open classes were lost: restore them
                        open_nodes.each(function () {
                            $("#" + $(this).attr("id")).filter(":has(li)").removeClass("jstree-closed").addClass("jstree-open");
                        });

                        // jstree-clicked class was lost: restore it
                        $("#" + clicked_node.attr("id")).children("a").click();

                        tree.attr("data-seq-nr", new_seq_nr);
                    }
                });

                this.data.deltaupdates.pending_updates = [];
            },        
            // }}}
            // {{{ _init_update_seq
            _init_update_seq : function () {
                if (!this.data.deltaupdates.active_ajax_requests) {
                    this.data.deltaupdates.last_rollback = 2147483647;
                    this.data.deltaupdates.seq = 0;
                }
            },
            // }}}
            // {{{ _rollback_in_order
            _rollback_in_order : function (seq, rlbk) {
                // only allow rollbacks in correct order
                // in case rollback overwrites a successful update wait for a delta update
                if (seq < this.data.deltaupdates.last_rollback) {
                    this.data.deltaupdates.last_rollback = seq;
                    $.jstree.rollback(rlbk);
                    // poll newest delta updates to prevent a 3 sec wait if polling is neccessary
                    if (this.data.deltaupdates.ws.poll)
                        this.data.deltaupdates.ws.poll();
                }
            },
            // }}}
            // {{{ _ajax_call
            _ajax_call : function (args) {
                var tree = this.get_container();
                var settings = this.get_settings().deltaupdates;
                var _this = this;

                $.ajax({
                    seq : _this.data.deltaupdates.seq++,
                    async : true,
                    type: 'POST',
                    url: settings.postURL + args.operation,
                    data : args.data,
                    beforeSend : function () {
                        _this.data.deltaupdates.active_ajax_requests += 1;
                    },
                    success : args.success,
                    error : function () {
                        // TODO: untested
                        _this._rollback_in_order(this.seq, args.rollback);
                    },
                    complete : function () {
                        _this.data.deltaupdates.active_ajax_requests -= 1;
                        // apply delta updates if this is last outstanding request
                        if (!_this.data.deltaupdates.active_ajax_requests) {
                            if (!$(".jstree-rename-input").unbind('end_edit').bind('end_edit', function() { _this.apply_delta_updates(); }).length) {
                                _this.apply_delta_updates();
                            }
                        }
                    }
                });
            }
            // }}}
        }
    });
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
