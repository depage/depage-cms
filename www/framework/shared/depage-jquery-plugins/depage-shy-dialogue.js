/**
 * @file    depage-shy-dialogue
 * @require framework/shared/depage-jquery-plugins/depage-markerbox.js
 *
 * Unobstrusive jQuery dialogue box, extends marker box.
 *
 * Builds the dialogue around an element, when clicked the dialgoue appears.
 *
 * Plugin takes a 'buttons' argument which is defines the buttons to display.
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    }

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
        var $inputWrapper = null;

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

            // if no element is specified to bind the click handler to, default to the base element
            if (base.options.bind_el === null) {
                base.options.bind_el = base.$el;
            }

            if (base.options.bind_el) {
                base.bind();
            }

            base.buttons = buttons;

        };
        // }}}

        // {{{ bind()
        /**
         * Dialogue
         *
         * @return void
         */
        base.bind = function(){
            base.options.bind_el.bind('click.shy', function(e) {
                base.showDialogue(e.pageX, e.pageY);
                return false;
            });
        };
        /// }}}

        // {{{ showDialogue()
        /**
         * showDialogue
         *
         * This is the action call to show the dialogue
         *
         * @param e = triggering event (need to stop propagation of marker click)
         * @param x
         * @param y
         *
         * @return void
         */
        base.showDialogue = function(x, y){
            setTimeout(function() {
                base.show(x, y);
                base.showButtons();
            }, 50);
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
            $inputWrapper = $('<div class="inputs" />');
            this.$wrapper.append($inputWrapper);
            this.$wrapper.append($buttonWrapper);

            base.setInputs(base.options.inputs);
            base.setButtons(base.buttons);

            this.$wrapper.find('input, a').eq(0).focus().select();
        };
        // }}}

        // {{{ setInputs()
        /**
         * setInputs
         *
         * @param buttons
         *
         * @return void
         */
        base.setInputs = function(inputs) {
            $inputWrapper.empty();

            for(var i in inputs) {
                (function() {
                    var input = base.options.inputs[i];
                    var placeholder = input.placeholder || "";
                    var inputType = input.type || "text";
                    var value = input.value || "";
                    var className = "input";
                    if (input.classes) {
                        className += " " + input.classes;
                    }
                    var $input = $('<input class="' + className + '" />')
                        .attr('id', base.options.id + '-i' + i)
                        .attr('placeholder', placeholder)
                        .attr('type', inputType)
                        .attr('value', value)
                        .data('depage.shyDialogue', base);

                    $input
                        .on("keyup", function(e) {
                            if (e.which == 13) {
                                $wrapper.find('a').eq(0).click();
                            }
                        });

                    $inputWrapper.append($input);
                })();
            }

            // allow chaining
            return this;
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
     * buttons - buttons to supply (with corresponding event triggered) { button_text: {click: function() {}}, ...}
     * classes - css classes to supply to the wrapper and content elements
     * bind_el: override to specify a different element to bind the onclick to. false means no click handler -
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
        inputs: {},
        buttons: {},
        bind_el: null
    };

    $.fn.depageShyDialogue = function(buttons, options){
        return this.each(function(index){
            (new $.depage.shyDialogue(this, index, buttons, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
