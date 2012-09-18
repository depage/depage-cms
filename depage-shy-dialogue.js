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
        var $dialogue = null;
        var $wrapper = null;
        var $contentWrapper = null;
        var $buttonWrapper = null;
        var $directionMarker = null;
        
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

            base.addWrapper();

            base.setContent(base.options.title, base.options.message, base.options.icon);
            base.setButtons(base.buttons);
            base.setPosition(top, left, base.options.direction);

            // set focus to default button when available
            $(".button.default", $wrapper).focus();

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
            
            // allow chaining
            return this;
        };
        // }}}
        
        // {{{ hide()
        /**
         * Hide
         * 
         * @param duration - gradually fades out default 300
         * @param callback - optional callback function
         * 
         * @return void
         */
        base.hide = function(duration, callback) {
            $("html").unbind("click.shy-dialogue");

            if (!$dialogue) return;

            duration = duration || base.options.fadeoutDuration;
            $wrapper.fadeOut(duration, callback);
            
            // allow chaining
            return this;
        };
        // }}}
        // {{{ hideAfter()
        /**
         * HideAfter
         *
         * hides dialog automatically after a duration
         *
         * @param duration - duration after
         * @param callback - optional callback function
         * 
         * @return void
         */
        base.hideAfter = function(duration, callback) {
            setTimeout(function(){
                base.hide(base.options.fadeoutDuration, callback);
            }, duration);

            // allow chaining
            return this;
        };
        // }}}
        
        // {{{ addWrapper()
        /**
         * removes old and adds the new html wrapper
         * 
         * @return void
         */
        base.addWrapper = function() {
            // remove old wrapper (also with multiple dialogues)
            $('#' + base.options.id).remove();

            $dialogue = $('<div />');

            $wrapper = $('<div class="wrapper" />');
            $dialogue.append($wrapper);

            if (base.options.direction) {
                // add direction marker
                $directionMarker = $('<span class="direction-marker" />');
                $wrapper.append($directionMarker);
            }
            
            $contentWrapper = $('<div class="message" />');
            $wrapper.append($contentWrapper);

            $buttonWrapper = $('<div class="buttons" />');
            $wrapper.append($buttonWrapper);

            $("body").append($dialogue);

            $wrapper.data("depage.shyDialogue", base);
            $dialogue.attr({
                class: "depage-shy-dialogue " + base.options.class,
                id: base.options.id
            });

            // allow chaining
            return this;
        };
        // }}}
        // {{{ setPosition()
        /**
         * set the position of the dialogue including the direction marker
         * 
         * @return void
         */
        base.setPosition = function(newTop, newLeft, direction) {
            $dialogue.attr("style", "position: absolute; top: " + newTop + "px; left: " + newLeft + "px; z-index: 10000");

            direction = direction.toLowerCase();

            var dHeight = $directionMarker.height();
            var dWidth = $directionMarker.width();
            var wrapperHeight = $wrapper.height();
            var wrapperWidth = $wrapper.width();
            var paddingLeft = parseInt($wrapper.css("padding-left"), 10);
            var paddingRight = parseInt($wrapper.css("padding-right"), 10);
            var paddingTop = parseInt($wrapper.css("padding-top"), 10);
            var paddingBottom = parseInt($wrapper.css("padding-bottom"), 10);

            var wrapperPos = {};
            var markerPos = {};

            // to which side will the direction-marker attached to
            switch (direction[0]) {
                case 't': // top
                    wrapperPos.top = dHeight / 2;
                    markerPos.top = -dHeight;
                    break;
                case 'b': // bottom
                    wrapperPos.bottom = dHeight / 2;
                    markerPos.bottom = -dHeight;
                    break;
                case 'l': // left
                    wrapperPos.left = dWidth / 2;
                    markerPos.left = -dWidth;
                    break;
                case 'r': // right
                    wrapperPos.right = dWidth / 2;
                    markerPos.right = -dWidth;
                    break;
            }

            // on which position will it be displayed 
            switch (direction[1]) {
                case 'l': // left
                    wrapperPos.left = -paddingLeft - dWidth / 2;
                    markerPos.left = paddingLeft;
                    break;
                case 'r': // right
                    wrapperPos.right = -paddingRight - dWidth / 2;
                    markerPos.right = paddingRight;
                    break;
                case 'c': // center
                    if (direction[0] == "t" || direction[0] == "b") { // horizontal
                        wrapperPos.left = - (wrapperWidth + paddingLeft + paddingRight) / 2;
                        markerPos.left = (wrapperWidth + paddingLeft + paddingRight) / 2 - dWidth / 2;
                    } else { // vertical
                        wrapperPos.top = - (wrapperHeight + paddingTop + paddingBottom) / 2;
                        markerPos.top = (wrapperHeight + paddingTop + paddingBottom) / 2 - dHeight / 2;
                    }
                    break;
                case 't': // top
                    wrapperPos.top = -paddingTop - dHeight / 2;
                    markerPos.top = paddingTop;
                    break;
                case 'b': // bottom
                    wrapperPos.bottom = -paddingBottom - dHeight / 2;
                    markerPos.bottom = paddingBottom;
                    break;
            }

            $wrapper.css(wrapperPos);
            $directionMarker.css(markerPos);
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
                    
                    $buttonWrapper.append($btn);
                })();
            }

            // allow chaining
            return this;
        };
        // }}}
        // {{{ setContent()
        /**
         * setContent
         * 
         * @param title
         * @param message
         * @param icon (optional)
         * 
         * @return void
         */
        base.setContent = function(title, message, icon) {
            var $title = $('<h1 />').text(title);
            var $message = $('<p />').text(message);

            $contentWrapper.empty()
                .append($title)
                .append($message);

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
        class : '',
        icon: '',
        title: '',
        message: '',
        direction : '',
        directionMarker : '',
        fadeoutDuration: 300,
        buttons: {},
    };
    
    $.fn.depageShyDialogue = function(buttons, options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, buttons, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
