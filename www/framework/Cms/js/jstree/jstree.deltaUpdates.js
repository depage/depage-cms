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

                    for (var id in data.nodes) {
                        if (data.nodes[id]) {
                            var parentNode = inst.get_node(id);
                            if (!parentNode) {
                                inst._append_html_data(inst.element, $(data.nodes[id]), function() {});
                            } else {
                                inst._append_html_data(inst.get_node(id), $(data.nodes[id]), function() {});
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
            this._data.deltaUpdates.ws.close();
            this._data.deltaUpdates.ws = null;

            parent.destroy.call(this, keep_html);
        };
        // }}}
    };
}));

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
