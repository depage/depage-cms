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
        
        // reference the wrapper div
        var $wrapper = null;
        
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
                return false;
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
            if (!$wrapper) {
                $wrapper = $('<div/>');
            }
            $wrapper.attr({
                id: base.options.id,
                style: 'position: absolute; left:' + left + '; top: ' + top + ';'
            });
            $span = $('<span />').html(base.options.message);
            $wrapper.empty().append($span);
            for(var i in base.options.buttons){
                (function() {
                    var button = base.options.buttons[i];
                    var $btn = ($('<a href="#" />')
                        .attr('id', base.options.id + '-' + button)
                        .html(button)
                        .click(function(e){
                            switch (button.toLowerCase()) {
                                case 'cancel':
                                    base.hide(500);
                                default :
                                    base.$el.trigger('shy_' + button.toLowerCase(), e);
                            }
                            
                            return false;
                        }));
                    
                    $wrapper.append($btn);
                })();
                
            }
            base.$el.after($wrapper);
        };
        // }}}
        
        // {{{ hide()
        /**
         * Hide
         * 
         * @param duration - gradually fades out default 0
         * @param callback - optional callback function
         * 
         * @return void
         */
        base.hide = function(duration, callback) {
            duration = duration || 0;
            $('#' + base.options.id).fadeOut(duration, callback);
        };
        // }}}
        
        // {{{ swapContent()
        /**
         * swapContent
         * 
         * @param fadeout if set will hide the dialogue
         * 
         * @return void
         */
        base.swapContent = function(html, fadeout) {
            $('#' + base.options.id).empty().html(html);
            if (fadeout) {
                setTimeout(function(){base.hide(fadeout);}, 3000);
            }
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
        buttons: ['OK', 'Cancel'],
    };
    
    $.fn.depageShyDialogue = function(options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
