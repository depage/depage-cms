/**
 * @require framework/shared/jquery-1.4.2.js
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

        // @todo check if element is input/button and make it also work if element is form
        base.init = function(){
            base.options = $.extend({},$.depage.textbutton.defaultOptions, options);
            
            // Put your initialization code here
            base.$el.css({
                position: "absolute",
                left: "-10000px",
                width: "100px"
            });

            // add link and click event to it
            $("<a href=\"#" + this.value + "\" class=\"textbutton\">" + base.el.value + "</a>").insertAfter(base.el).click( function() {
                if (base.$el.filter(":submit").length == 0) {
                    base.$el.click();
                } else {
                    var $form = base.$el.parents("form");

                    if (base.$el.parent().hasClass("cancel")) {
                        // dont validate when cancel-button was pressed
                        $("<input type=\"hidden\" class=\"formSubmit\" name=\"formSubmit\" value=\"" + base.el.value + "\">").appendTo($form);
                        $form.data("validator").destroy();
                    } else {
                        $form.find("input.formSubmit:hidden").remove();
                    }

                    $form.submit();
                }

                return false;
            });
        };
        // Run initializer
        base.init();
    };
    
    $.depage.textbutton.defaultOptions = {
        option1: "default"
    };
    
    $.fn.depageTextbutton = function(options){
        return this.each(function(){
            (new $.depage.textbutton(this, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
