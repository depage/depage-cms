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

        // holds available terms
        base.$terms;

        // holds highlighted terms
        base.$highlightedTerms = $();
        
        // }}}
        
        // {{{ init()
        base.init = function(){
            base.options = $.extend({},$.depage.termExtractor.defaultOptions, options);
            
            // Put your initialization code here
            base.getTerms();

            $(window).on(base.options.events, base.onScroll);

            // call with timeout to let calling script add events
            setTimeout(base.onScroll, 300);
        };
        // }}}
        // {{{ getTerms
        base.getTerms = function(){
            base.$terms = $(base.options.selector, base.$el).addClass("term");
            base.$highlightedTerms = $();

            return base.$terms;
        };
        // }}}
        // {{{ onScroll
        base.onScroll = function(e, delta){
            var scrollTop = $(window).scrollTop();
            var topOffset = $(window).height() * 0.1 + scrollTop;
            var bottomOffset = $(window).height() * 0.6 + scrollTop;

            var $addedTerms = $();
            var $removedTerms = $();

            base.$terms.each( function() {
                var $term = $(this);
                var termOffset = $term.offset().top;

                if (termOffset > topOffset && termOffset < bottomOffset) {
                    if (!$term.hasClass("highlighted")) {
                        $term.addClass("highlighted");
                        base.$highlightedTerms = base.$highlightedTerms.add($term);

                        $addedTerms = $addedTerms.add($term);
                    }
                } else {
                    if ($term.hasClass("highlighted")) {
                        $term.removeClass("highlighted");
                        base.$highlightedTerms = base.$highlightedTerms.not($term);

                        $removedTerms = $removedTerms.add($term);
                    }
                }
            });

            if ($removedTerms.length > 0) {
                base.$el.trigger("darken.depageTermExtractor", [$removedTerms]);
            }
            if ($addedTerms.length > 0) {
                base.$el.trigger("highlight.depageTermExtractor", [$addedTerms]);
            }
            if ($addedTerms.length > 0 || $removedTerms.length > 0) {
                base.$el.trigger("change.depageTermExtractor", [base.$highlightedTerms, $addedTerms, $removedTerms]);
            }
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    // {{{ defaultOptions
    $.depage.termExtractor.defaultOptions = {
        selector: "*[data-term-id]",
        events: "scroll.depageTermextractor, resize.depageTermextractor",
        maxTerms: -1
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
