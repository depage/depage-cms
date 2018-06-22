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
    var deferredWebsocket = function(webSocketUrl) {
        if (typeof _ws[webSocketUrl] !== 'undefined') {
            return _ws[webSocketUrl];
        }

        _ws[webSocketUrl] = {
            init: function() {
                var that = this;
                this.messageCallbacks = $.Callbacks();
                this.ws = new WebSocket(webSocketUrl);
                this.ws.onmessage = function(e) {
                    that.messageCallbacks.fire(e);
                };

                $(window).unload(function () { that.ws.close(); that.ws = null; });
            },
            send: function(data) {
                var defer = $.Deferred();
                var that = this;

                defer.then( function() {
                    that.ws.send($.toJSON(data));
                });
                if (this.ws.readyState == 1) {
                    defer.resolve(data);
                } else {
                    this.ws.onopen = function() {
                        defer.resolve(data);
                    };
                }
            },
            onmessage: function( callback ) {
                this.messageCallbacks.add(callback);
            },
            offmessage: function( callback ) {
                this.messageCallbacks.remove(callback);
            }
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
            var fallbackPollURL = this.element.attr("data-delta-updates-fallback-poll-url");
            this._data.deltaUpdates.ws = deferredWebsocket(webSocketURL);

            this._data.deltaUpdates.ws.send({
                action: "subscribe",
                projectName: $tree.attr("data-projectname"),
                docId: $tree.attr("data-doc-id")
            });
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
                    data = $.evalJSON(event.data);
                } catch (e) {
                    // continue
                    return true;
                }
                if ($tree.attr("data-projectname") !== data.projectName || $tree.attr("data-doc-id") != data.docId) {
                    return;
                }

                // only overwrite tree nodes if data is newer
                var old_seq_nr = parseInt($tree.attr("data-seq-nr"));
                var new_seq_nr = parseInt(data.seq_nr);
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
            var $tree = this.element;
            this._data.deltaUpdates.ws.send({
                action: "unsubscribe",
                projectName: $tree.attr("data-projectname"),
                docId: $tree.attr("data-doc-id")
            });
            this._data.deltaUpdates.ws.offmessage( this.onmessage );
            //this._data.deltaUpdates.ws.close();
            this._data.deltaUpdates.ws = null;

            parent.destroy.call(this, keep_html);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
