/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-event-stream
 *
 * Depage Event Stream Plugin.
 *
 * Handles server sent events
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };

    // save an event stream cache object for the element
    depage.eventstream.cache = {};

    /**
     * Event Stream
     *
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.eventstream = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        base.el = el;
        base.$el = $(el);

        var cache = depage.eventstream.cache;

        // {{{ init
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.eventstream.defaultOptions, options);
            // check for browser support
            if (window.EventSource){
                base.openEventSource();
            }
        };
        // }}}

        // {{{ openEventSource()
        /**
         * Open Event Source
         *
         * @return void
         */
        base.openEventSource = function(){
            if ( cache[base.options.label] ){
                // close first if exists
                cache[base.options.label].close();
            }

            try {
                cache[base.options.label] = new EventSource(base.options.url);
            } catch (ex) {
                // mask errors
               return false;
            }

            if (cache[base.options.label]) {
                cache[options.label].onerror = function(){
                    // try to reconnect if there are problems
                    var callback = function() {
                        base.openEventSource();
                    };
                    // wait 5 seconds then reconnect
                    setTimeout(callback, 5000);
                };

                // setup listeners for the given types - defaults on open events
                for(var i in base.options.types){
                    base.addEventListener(base.options.types[i], base.options.label);
                }
            }
        };

        // }}}

        // {{{ addEventListener()
        /**
         * addEventListener
         *
         * Add a listener to the given event source.
         * Set trigger on the base element when the event is received.
         *
         * @param type - the event type to listen for
         * @param label - the cache label identifier
         * @param the event listener
         * @param use_capture - default false
         *
         * @return void
         */
        base.addEventListener = function(type, label) {
            if (cache[label]) {
                var listener = function(e){
                    base.$el.trigger(e.type, e.data);
                };
                cache[label].addEventListener(type, listener, false);
            }
        };
        // }}}

        /**
         * Close
         *
         * Closes the event source.
         *
         * If label supplied close that cache stream, otherwise close all.
         *
         * @param label
         *
         * @return void
         */
        base.close = function (label) {
            if (label) {
                if (cache[label]) {
                    cache[label].close();
                    cache[label] = null;
                }
            }
            else {
                $.each(cache, function (key, stream) {
                    if (stream !== null) {
                        stream.close();
                        cache[key] = null;
                    }
                });
            }
        };

        base.init();
    };

    /**
     * Options
     *
     * @param string label - event label, unique key for the event in the cache object.
     * @param string url - the url to listen to.
     * @param array types - array of strings representing the server event type to listen for.
     *  - defaults to 'open' and 'message' for events without a specified type.
     *
     */
    $.depage.eventstream.defaultOptions = {
        label:    null,
        url:      null,
        types:    ['open', 'message']
    };

    $.fn.depageEventStream = function(options){
        return this.each(function(index){
            (new $.depage.eventstream(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
