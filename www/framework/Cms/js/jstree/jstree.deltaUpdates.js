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

            this._data.deltaUpdates.ws = $.gracefulWebSocket(webSocketURL, {
                fallbackPollURL: fallbackPollURL,
                fallbackPollParams:  {
                    "seqNr": function () {
                        return $tree.attr("data-seq-nr");
                    },
                    "docId": function () {
                        return $tree.attr("data-doc-id");
                    }
                }
            });

            this._data.deltaUpdates.ws.onmessage = $.proxy(function (event) {
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

            this._data.deltaUpdates.ws.onopen = function() {
                this.send({
                    action: "subscribe",
                    projectName: $tree.attr("data-projectname"),
                    docId: $tree.attr("data-doc-id")
                });
            };
        };
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

                // only overwrite tree nodes if data is newer
                var old_seq_nr = parseInt($tree.attr("data-seq-nr"));
                var new_seq_nr = parseInt(data.seq_nr);
                if (new_seq_nr > old_seq_nr) {
                    // remember which tree nodes were open
                    var state = inst.get_state();

                    // @todo check order of nodes (if subsequent update is parent of previous)
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
            this._data.deltaUpdates.ws.close();
            this._data.deltaUpdates.ws = null;

            parent.destroy.call(this, keep_html);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
