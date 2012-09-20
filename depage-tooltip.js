/**
 * @require framework/shared/jquery-1.4.2.js
 * 
 * @file    depage-tooltip
 *
 * Depage Tool Tip Plugin.
 *  
 * Display custom tool tips
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
    $.depage.tooltip = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.tooltip", base);
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.tooltip.defaultOptions, options);
            
            base.tooltip();
        };
        // }}}
        
        // {{{ tooltip()
        /**
         * Tool Tip
         * 
         * @return void
         */
        base.tooltip = function(){
            
        };
        
        base.build = function(text){
            base.$el.appendTo("<div />").html(text);
        };
        
        base.init();
    };
    
    /**
     * Options
     * 
     * @param image
     * @param title
     * @param text
     * 
     */
    $.depage.textlimiter.defaultOptions = {
        image : null,
        text : null,
    };
    
    $.fn.depageTextLimiter = function(options){
        return this.each(function(index){
            (new $.depage.textlimiter(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
