/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-details.js
 *
 * adds details handler to definition-lists
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depage.net]
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
            $(base.options.headSelector, base.el).each(function() {
                var $head = $(this).css({
                    cursor: "pointer"
                });
                var $detail = $head.nextUntil(base.options.headSelector);

                $(this).prepend("<span class=\"opener\"></span>");

                $detail.hide();

                $head.find("a").on("click", function(e) {
                    // stop links inside header opening/closing details
                    e.stopPropagation();
                });
                $head.on("click", function() {
                    if (!$head.hasClass("active")) {
                        base.closeDetail($(base.options.headSelector, base.el).filter(".active"));
                        base.openDetail($head);

                    } else {
                        base.closeDetail($head);
                    }
                });
                $head.on("depage.filter-hidden", function(e, $detail) {
                    base.closeDetail($detail);
                });
            });
        };

        base.openDetail = function($head) {
            if ($head.length == 0) {
                return;
            }
            var $detail = $head.nextUntil(base.options.headSelector);

            $head.addClass("active");
            $head.siblings(base.options.detailSelector).slideUp();
            $detail.addClass("active");
            $detail.slideDown();
            base.$el.trigger("depage.detail-opened", [$head, $detail]);
        };
        base.closeDetail = function($head) {
            if ($head.length == 0) {
                return;
            }
            var $detail = $head.nextUntil(base.options.headSelector);

            $detail.removeClass("active");
            $detail.slideUp("normal", function() {
                $head.removeClass("active");
            });
            base.$el.trigger("depage.detail-closed", [$head, $detail]);
        };

        // Run initializer
        base.init();
    };

    $.depage.details.defaultOptions = {
        headSelector: "dt",
        detailSelector: "dd",
    };

    $.fn.depageDetails = function(options){
        return this.each(function(){
            (new $.depage.details(this, options));
        });
    };

})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
