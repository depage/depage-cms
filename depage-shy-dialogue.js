/**
 * @file    depage-shy-dialogue
 * @require framework/shared/depage-jquery-plugins/depage-markerbox.js
 *
 * Unobstrusive jQuery dialogue box, extends marker box
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
    $.depage.shyDialogue = function(el, index, buttons, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.shyDialogue", base);
        
        // enable buttons in the dialogue
        var $buttonWrapper = null;
        
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
            $.extend(base, $.depage.markerbox(base.options));
            base.buttons = buttons;
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
                base.show(e.pageX, e.pageY);
                base.showButtons();
                return false;
            });
        };
        /// }}}
        
        // {{{ showButtons()
        /**
         * Show Buttons
         * 
         * @return void
         */
        base.showButtons = function() {
            $buttonWrapper = $('<div class="buttons" />');
            $wrapper.append($buttonWrapper);
            base.setButtons(base.buttons);
        };
        // }}}
        
        // {{{ setButtons()
        /**
         * setButtons
         * 
         * @param buttons
         * 
         * @return void
         */
        base.setButtons = function(buttons) {
            $buttonWrapper.empty();
            
            for(var i in buttons){
                (function() {
                    var button = base.buttons[i];
                    var title = button.title || i;
                    var className = "button";
                    if (button.classes) {
                        className += " " + button.classes;
                    }
                    var $btn = $('<a href="#" class="' + className + '" />')
                        .attr('id', base.options.id + '-' + i)
                        .text(title)
                        .data('depage.shyDialogue', base) 
                        .click(function(e){
                            if (typeof(button.click) !== 'function' || button.click(e) !== false) {
                                base.hide();
                            }
                            return false;
                        });
                    
                    $buttonWrapper.append($btn);
                })();
            }
            
            // allow chaining
            return this;
        };
        // }}}
        
        base.init();
        return base;
    };
    // }}}
    
    /**
     * Default Options
     * 
     * id - the id of the dialogue element wrapper to display
     * message - message the dialouge will display
     * buttons - buttons to supply (with corresponding event triggered)
     * classes - css classes to supply to the wrapper and content elements
     * 
     */
    $.depage.shyDialogue.defaultOptions = {
        id : 'depage-shy-dialogue',
        classes : 'depage-shy-dialogue',
        icon: '',
        title: '',
        message: '',
        direction : 'TL',
        directionMarker : null,
        fadeoutDuration: 300,
        buttons: {}
    };
    
    $.fn.depageShyDialogue = function(buttons, options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, buttons, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
