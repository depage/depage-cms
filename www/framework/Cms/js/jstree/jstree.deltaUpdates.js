/**
* ### deltaUpdates plugin
*
* Adds deltaUpdates functionality to jsTree
*/
/*globals jQuery, define, exports, require, document */
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
            define('jstree.deltaUpdates', ['jquery','jstree'], factory);
    }
    else if(typeof exports === 'object') {
            factory(require('jquery'), require('jstree'));
    }
    else {
            factory(jQuery, jQuery.jstree);
    }
}(function ($, jstree, undefined) {
    "use strict";

    if($.jstree.plugins.deltaUpdates) { return; }

    // {{{ deferredWebsocket
    var _ws = [];
    var deferredWebsocket = function(webSocketUrl, fallbackUrl) {
        if (typeof _ws[webSocketUrl] !== 'undefined') {
            return _ws[webSocketUrl];
        }

        _ws[webSocketUrl] = {
            // {{{ init()
            init: function() {
                var that = this;
                this.messageCallbacks = $.Callbacks();
                this.subscriptions = {};

                this.connect();

                $(window).unload(function () {
                    that.subscriptions = {};
                    if (that.ws) {
                        that.ws.close();
                    }
                    that.ws = null;
                });
            },
            // }}}
            // {{{ connect()
            connect: function() {
                var that = this;

                // @todo add http fallback poll
                if (!window.WebSocket) {
                    return this.poll();
                }

                this.ws = null;
                this.ws = new WebSocket(webSocketUrl);
                this.ws.onmessage = function(e) {
                    that.messageCallbacks.fire(e);
                };
                this.ws.onerror = function(e) {
                    that.poll();
                };
                this.ws.onclose = function(e) {
                    setTimeout(that.reconnect, 1000);
                };
            },
            // }}}
            // {{{ reconnect()
            reconnect: function() {
                var keys = Object.keys(this.subscriptions);
                if (keys.length == 0) return;

                this.connect();

                for (var i = 0; i < keys.length; i++) {
                    this.send({
                        action: "subscribe",
                        projectName: this.subscriptions[keys[i]].projectName,
                        docId: this.subscriptions[keys[i]].docId
                    });
                }
            },
            // }}}
            // {{{ send()
            send: function(data) {
                if (!this.ws) {
                    return;
                }
                if (this.ws.readyState == 1) {
                    this.ws.send(JSON.stringify(data));
                } else {
                    var defer = $.Deferred();
                    var that = this;

                    defer.then( function() {
                        if (that.ws.readyState == 1) that.ws.send(JSON.stringify(data));
                    });
                    this.ws.addEventListener('open', function() {
                        console.log("websocket opened");
                        defer.resolve(data);
                    });
                }
            },
            // }}}
            // {{{ poll()
            poll: function() {
                console.log("polling " + fallbackUrl);
                var that = this;

                var keys = Object.keys(this.subscriptions);
                for (var i = 0; i < keys.length; i++) {
                    var sub = this.subscriptions[keys[i]];
                    var params = {
                        projectName: sub.projectName,
                        docId: sub.docId,
                        seqNr: sub.seqNr,
                    };
                    $.ajax({
                        type: "POST",
                        url: fallbackUrl,
                        data: params,
                        dataType: 'text',
                        contentType : "application/x-www-form-urlencoded; charset=utf-8",
                        success: function(data) {
                            if (data == "") return;

                            that.messageCallbacks.fire({"data" : data});
                            try {
                                var result = JSON.parse(data);
                                var id = result.projectName + "_" + result.docId;
                                that.subscriptions[id].seqNr = result.seqNr;
                            } catch (e) {
                                // continue
                                return true;
                            }
                        },
                        error: function (xhr) {
                        }
                    });
                }
                setTimeout(function() {
                    that.reconnect();
                }, 1500);
            },
            // }}}
            // {{{ subscribe()
            subscribe: function(projectName, docId, seqNr) {
                console.log("subscribe " + projectName + " " + docId);
                var id = projectName + "_" + docId;
                this.subscriptions[id] = {
                    projectName: projectName,
                    docId: docId,
                    seqNr: seqNr
                };
                this.send({
                    action: "subscribe",
                    projectName: projectName,
                    docId: docId
                });
            },
            // }}}
            // {{{ unsubscribe()
            unsubscribe: function(projectName, docId) {
                console.log("unsubscribe " + projectName + " " + docId);
                var id = projectName + "_" + docId;
                if (this.subscriptions[id]) {
                    delete this.subscriptions[id];
                }
                this.send({
                    action: "unsubscribe",
                    projectName: projectName,
                    docId: projectName
                });
            },
            // }}}
            // {{{ onmessage()
            onmessage: function( callback ) {
                this.messageCallbacks.add(callback);
            },
            // }}}
            // {{{ offmessage()
            offmessage: function( callback ) {
                this.messageCallbacks.remove(callback);
            }
            // }}}
        };
        _ws[webSocketUrl].init();

        return _ws[webSocketUrl];
    };
    // }}}

    /**
     * deltaUpdates configuration
     *
     * @name $.jstree.defaults.deltaUpdates
     * @plugin deltaUpdates
     */
    $.jstree.defaults.deltaUpdates = null;
    $.jstree.plugins.deltaUpdates = function (options, parent) {
        // {{{ init()
        this.init = function (el, options) {
            parent.init.call(this, el, options);

            this._data.deltaUpdates = {};
            this._data.deltaUpdates.active_ajax_requests = 0;
            this._data.deltaUpdates.pending_updates = [];
            this._data.deltaUpdates.inst = this.element.jstree(true);
            var $tree = this.element;

            var webSocketURL = this.element.attr("data-delta-updates-websocket-url");
            var fallbackURL = this.element.attr("data-delta-updates-fallback-url");
            this._data.deltaUpdates.ws = deferredWebsocket(webSocketURL, fallbackURL);

            this._data.deltaUpdates.ws.subscribe($tree.attr("data-projectname"), $tree.attr("data-doc-id"), $tree.attr("data-seq-nr"));
            this._data.deltaUpdates.ws.onmessage( this.onmessage );

            // @todo reconnect after disconnect or fallback
            // @todo add fallback when websocket is not available or cannot connect
        };
        // }}}
        // {{{
        this.onmessage = $.proxy( function(event) {
            if (event.data) {
                this._data.deltaUpdates.pending_updates.push(event);
                // only apply delta updates if no updates are in progress
                // pending delta updates are applied when local update ajax calls return
                if (!this._data.deltaUpdates.active_ajax_requests) {
                    if (!$(".jstree-rename-input").unbind('end_edit').bind('end_edit', function() { this.applyDeltaUpdates(); }).length) {
                        this.applyDeltaUpdates();
                    }
                }
            }
        }, this);
        // }}}
        // {{{
        this.applyDeltaUpdates = function() {
            var $tree = this.element;
            var inst = this;
            var data;
            $.each(this._data.deltaUpdates.pending_updates, function (index, event) {
                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    // continue
                    return true;
                }
                if ($tree.attr("data-projectname") != data.projectName || $tree.attr("data-doc-id") != data.docId) {
                    return;
                }

                // only overwrite tree nodes if data is newer
                var old_seq_nr = parseInt($tree.attr("data-seq-nr"));
                var new_seq_nr = parseInt(data.seqNr);
                if (new_seq_nr > old_seq_nr) {
                    // remember which tree nodes were open
                    var state = inst.get_state();

                    // @todo fix urls for newly loaded nodes? -> do that in php origin
                    // @todo add reload on error/problem
                    for (var id in data.nodes) {
                        if (data.nodes[id]) {
                            var parentNode = inst.get_node(id);
                            var html = $(data.nodes[id]);

                            if (!parentNode && id == $tree.attr("data-node-id")) {
                                parentNode = inst.element;
                            }
                            if (parentNode) {
                                inst._append_html_data(parentNode, html, function() {});
                            } else {
                                console.log("no no no!!");
                                console.log(data.nodes);
                                inst.refresh();
                            }
                        }
                    }

                    inst.set_state(state);

                    $tree.attr("data-seq-nr", new_seq_nr);

                    inst.trigger("refresh");
                }
            });

            this._data.deltaUpdates.pending_updates = [];
        };
        // }}}
        // {{{ destroy()
        this.destroy = function(keep_html) {
            if (this._data.deltaUpdates.ws) {
                var $tree = this.element;

                this._data.deltaUpdates.ws.unsubscribe($tree.attr("data-projectname"), $tree.attr("data-doc-id"));
                this._data.deltaUpdates.ws.offmessage( this.onmessage );
            }
            this._data.deltaUpdates.ws = null;

            parent.destroy.call(this, keep_html);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
