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
    $.depage.shyDialogue = function(el, index, buttons, options){
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
                $wrapper = $('#' + base.options.id);
                if ($wrapper.length == 0) {
                    $wrapper = $('<div />');
                } else {
                    $wrapper.data("depage.shyDialogue").hide(0);
                }
            }
            $wrapper.data("depage.shyDialogue", base);
            $wrapper.attr({
                class: base.options.classes.wrapper,
                id: base.options.id,
                style: 'position: absolute; left:' + left + 'px; top: ' + top + 'px;'
            });

            var $title = $('<h1 />').html(base.options.title);
            var $message = $('<p />').html(base.options.message);
            var $buttonwrapper = $('<div class="buttons" />');
            $wrapper.empty()
                .append($title)
                .append($message)
                .append($buttonwrapper);
            

            for(var i in base.buttons){
                (function() {
                    var button = base.buttons[i];
                    var title = button.title || i;
                    var className = "button";
                    if (button.class) {
                        className += " " + button.class;
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
                    
                    $buttonwrapper.append($btn);
                })();
                
            }
            $("body").append($wrapper);

            $(".button.default", $wrapper).focus().css("border: 1px solid");

            // bind escape key to cancel
            $(document).bind('keyup.shy-dialogue', function(e){
                var key = e.which || e.keyCode;
                if (key == 27) {
                    base.hide();
                    $(document).unbind('keyup.shy-dialogue');
                }
            });
            // hide dialog when clicked outside
            $("html").bind("click.shy-dialogue", function() {
                base.hide();
            });
            $wrapper.click( function(e) {
                e.stopPropagation();
            });
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
            $("html").unbind("click.shy-dialogue");

            if (!$wrapper) return;

            duration = duration || base.options.fadeoutDuration;
            $wrapper.fadeOut(duration, callback);
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
        base.swapContent = function(html, duration) {
            $('#' + base.options.id).empty().html(html);
            if (duration) {
                setTimeout(function(){base.hide(base.options.fadeoutDuration);}, duration);
            }
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
        icon: '',
        title: '',
        message: '',
        fadeoutDuration: 300,
        buttons: {},
        classes : { wrapper : 'depage-shy-dialogue'}
    };
    
    $.fn.depageShyDialogue = function(buttons, options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, buttons, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
