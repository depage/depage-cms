/**
 * @require framework/shared/jquery-1.8.3.js
 * 
 * @file    depage-text-limiter
 *
 * Depage Text Limiter Plugin.
 * 
 * Limit the max length of textbox and text area input fields.
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    /**
     * tabs
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.textlimiter = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.textlimiter", base);
        
        // add a reference to an optional selector to store the number of remaining chars available.
        var $remaining = null;
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.textlimiter.defaultOptions, options);
            
            if (!base.options.max) {
                base.options.max = base.$el.attr("maxlength");
            }
            
            if (base.options.remaining) {
                $remaining = $(base.options.remaining);
            }
            
            base.limiter();
        };
        // }}}
        
        // {{{ limiter()
        /**
         * Text Limiter
         * 
         * @return void
         */
        base.limiter = function(){
            base.filter(null);
            if (base.options.max) {
                base.$el.bind('keyup keypress paste', function(e) {
                    base.filter(e);
                });
            }
        };
        // }}}
        
        // {{{ filter()
        /**
         * Filter
         * 
         * Filter the imput set the remaining chars
         * 
         * @return void
         */
        base.filter = function(e) {
            var val = base.$el.val();
            if (val.length >= base.options.max) {
                val = val.substr(0, base.options.max);
                base.$el.val(val);
            }
            if ($remaining) {
                $remaining.html(base.options.max - val.length);
            }
        };
        // }}}
        
        base.init();
    };
    
    /**
     * Options
     * 
     * @param max - max chars to display otherwise taken from maxlength attribute
     * @param showChars - display the remaining characters in the field
     * 
     */
    $.depage.textlimiter.defaultOptions = {
        max : 0,
        remaining : false
    };
    
    $.fn.depageTextLimiter = function(options){
        return this.each(function(index){
            (new $.depage.textlimiter(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
