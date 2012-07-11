/**
 * @require framework/shared/jquery-1.4.2.js
 * 
 * @file    depage-shy-dialogue
 *
 * Unobstrusive jQuery dialogue box.
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
     * shyDialogue
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.shyDialogue = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.shyDialogue", base);
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.shyDialogue.defaultOptions, options);
            base.dialogue();
        };
        // }}}
        
        // {{{ dialogue()
        /**
         * Dialogue
         * 
         * @return void
         */
        base.dialogue = function(){
            base.$el.bind('click.shy', function(e) {
                base.show(e);
            });
        };
        /// }}}
        
        // {{{ show()
        /**
         * Show
         * 
         * @return void
         */
        base.show = function(e) {
            var left = e.pageX || 0;
            var top = e.pageY || 0;
            var $wrapper = $('<div/>').attr({
                id: base.options.id,
                style: 'position: absolute; left:' + left + '; top: ' + top + ';'
            });
            $wrapper.append('<span />').html(base.options.message);
            for(var button in base.option.buttons){
                var $btn = ($('<a href="#" />')
                    .attr('id', base.options.id + '-' + button)
                    .html(button)
                    .click(function(e){
                        base.$el.trigger(button, e);
                        base.options.hide();
                        return false;
                    }));
                
                $wrapper.append($btn);
            }
            base.$el.after(wrapper);
        };
        // }}}
        
        // {{{ hide()
        /**
         * Hide
         * 
         * @return void
         */
        base.hide = function() {
            $(base.options.id).hide();
        };
        // }}}
        
        base.init();
        return base;
    };
    // }}}
    
    /**
     * Options
     * 
     * - dialogue_id - the id of the dialogue element wrapper to display
     * 
     */
    $.depage.shyDialogue.defaultOptions = {
        id : 'depage-shy-dialogue',
        message: '',
        buttons: ['accept', 'cancel']
    };
    
    $.fn.depageShyDialogue = function(options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
