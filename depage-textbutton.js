/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-textbutton.js
 *
 * replaces buttons with links to apply css-styles more easily
 *
 *
 * copyright (c) 2009-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.textbutton = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.textbutton", base);

        base.init = function(){
            base.options = $.extend({},$.depage.textbutton.defaultOptions, options);
            
            var $inputs = base.$el.filter("input");
            if ($inputs.length == 0) {
                // base.$el is not a input so we search for the children
                $inputs = base.$el.find(base.options.elements);
            }

            $inputs.each( function() {
                var button = this;
                var $button = $(this);

                $button.css({
                    position: "absolute",
                    left: "-10000px",
                    width: "100px"
                });

                // add link and click event to it
                $("<a href=\"#" + button.value + "\" class=\"textbutton\">" + button.value + "</a>").insertAfter(button).click( function() {
                    if ($button.filter(":submit").length == 0) {
                        $button.click();
                    } else {
                        var $form = $button.parents("form");

                        if ($button.parent().hasClass("cancel")) {
                            // dont validate when cancel-button was pressed
                            $("<input type=\"hidden\" class=\"formSubmit\" name=\"formSubmit\" value=\"" + button.value + "\">").appendTo($form);
                            if(typeof($form.data("validator")) !== 'undefined') {
                                // @todo validation should be handled in effects 
                                $form.data("validator").destroy();
                            }
                        } else {
                            $form.find("input.formSubmit:hidden").remove();
                        }

                        $form.submit();
                        $button.click();
                    }

                    return false;
                });
            });
        };
        // Run initializer
        base.init();
    };
    
    $.depage.textbutton.defaultOptions = {
        elements: "input:submit, input:reset, input:button"
    };
    
    $.fn.depageTextbutton = function(options){
        return this.each(function(){
            (new $.depage.textbutton(this, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
