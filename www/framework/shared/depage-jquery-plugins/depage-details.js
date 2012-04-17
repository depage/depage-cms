/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-details.js
 *
 * adds details handler to definition-lists
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.details = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.details", base);
        
        base.init = function(){
            base.options = $.extend({},$.depage.details.defaultOptions, options);
            
            // Put your initialization code here
            $("dt", base.el).each(function() {
                var $head = $(this).css({
                    cursor: "pointer"
                });
                var $detail = $head.nextAll("dd:first");

                $(this).prepend("<span class=\"opener\"></span>");

                $detail.hide();
                $head.click(function() {
                    if (!$head.hasClass("active")) {
                        $("dt", base.el).removeClass("active");
                        $head.addClass("active")
                        $head.siblings("dd").slideUp();
                        $detail.addClass("active")
                        $detail.slideDown();
                    } else {
                        $detail.removeClass("active")
                        $detail.slideUp("normal", function() {
                            $head.removeClass("active");
                        });
                    }
                });
            });
        };
        
        // Run initializer
        base.init();
    };
    
    $.depage.details.defaultOptions = {
        option1: "default"
    };
    
    $.fn.depageDetails = function(options){
        return this.each(function(){
            (new $.depage.details(this, options));
        });
    };
    
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
