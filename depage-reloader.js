/**
 * @require framework/shared/jquery-1.8.3.js
 * 
 *  @file    depage-reloader.js
 *
 * Adds a dynamic AJAX content reloader to elements.
 * 
 * Continually reloads an element by wrapping the jquery load method with a timer.
 * 
 * Options include:
 *   - url - url to reload: ?ajax=true auto appended
 *   - interval - repeat interval in seconds
 *   - selector - load a page fragment by appending a selector - http://api.jquery.com/load/
 *   
 * Triggers an "on_reload" event
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    }
    
    var timer = null;
    
    /**
     * Reloader
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.reloader = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.reloader", base);
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * Build URL.
         * Init reload.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.reloader.defaultOptions, options);
            // build the url
            base.options.url = base.options.url + "?ajax=true";
            if (base.options.selector) {
                base.options.url = base.options.url + ' ' + base.options.selector;
            }
            base.reload();
        };
        // }}}
        
        // {{{ reload
        /**
         * Reload
         * 
         * Create timer polling on elements with ajax reload-url.
         * 
         * @return void
         */
        base.reload = function(){
            base.$el.load(base.options.url, null, function() {
                base.$el.trigger('on_reload');
            });
            
            timer = setTimeout(function() {
                base.reload();
            }, base.options.interval * 1000);
        };
        /// }}}
        
        // {{{ clear
        /**
         * Clear
         * 
         * Public function to clear the timer.
         * 
         * @return void
         */
        base.clear = function(){
            clearInterval(timer);
            timer = null;
        };
        // }}}
        
        base.init();
        return base;
    };
    // }}}
    
    /**
     * Options
     * 
     * url - url to reload: ?ajax=true auto appended
     * interval - repeat interval in seconds
     * selector - load a page fragment by appending a selector - http://api.jquery.com/load/
     */
    $.depage.reloader.defaultOptions = {
        url: null,
        interval: 10,
        selector: null
    };
    
    $.fn.depageReloader = function(options){
        return this.each(function(index){
            (new $.depage.reloader(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
