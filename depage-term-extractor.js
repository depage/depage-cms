;(function($){
     "use strict";

    if(!$.depage){
        $.depage = {};
    }
    
    $.depage.termExtractor = function(el, options){
        // {{{ variables
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.termExtractor", base);
        // }}}
        
        // {{{ init()
        base.init = function(){
            base.options = $.extend({},$.depage.termExtractor.defaultOptions, options);
            
            // Put your initialization code here
            base.getTerms();

            $(window).on(base.options.events, base.onScroll);

            base.onScroll();
        };
        // }}}
        // {{{ getTerms
        base.getTerms = function(){
            base.$terms = $(base.options.selector, base.$el);
        };
        // }}}
        // {{{ onScroll
        base.onScroll = function(e, delta){
            var scrollTop = $(window).scrollTop();
            var topOffset = $(window).height() * 0.1 + scrollTop;
            var bottomOffset = $(window).height() * 0.6 + scrollTop;
        
            base.$terms.each( function() {
                var termOffset = $(this).offset().top;

                if (termOffset > topOffset && termOffset < bottomOffset) {
                    $(this).addClass("highlighted");
                } else {
                    $(this).removeClass("highlighted");
                }
            });
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    // {{{ defaultOptions
    $.depage.termExtractor.defaultOptions = {
        selector: "*[data-term-id]",
        events: "scroll.depageTermextractor, resize.depageTermextractor"
    };
    // }}}
    
    // {{{ $.fn.depageTermExtractor()
    $.fn.depageTermExtractor = function(options){
        return this.each(function(){
            (new $.depage.termExtractor(this, options));
        });
    };
    // }}}
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
