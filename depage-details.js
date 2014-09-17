/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-details.js
 *
 * adds details handler to definition-lists
 *
 *
 * copyright (c) 2011-2014 Frank Hellenkamp [jonas@depage.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    }

    $.depage.details = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.details", base);

        base.$activeDetailHead = null;

        base.init = function() {
            base.options = $.extend({},$.depage.details.defaultOptions, options);

            // Put your initialization code here
            $("dt", base.el).each(function() {
                var $head = $(this).css({
                    cursor: "pointer"
                });
                var $detail = $head.nextUntil("dt");
                $head.data("$detail", $detail);

                $(this).prepend("<span class=\"opener\"></span>");

                if ($head.hasClass("active")) {
                    base.$activeDetailHead = $head;
                } else {
                    $detail.hide();
                }
                $head.on("click", base.toggleDetail);
            });
        };

        base.toggleDetail = function() {
            var $head = $(this);

            if (!$head.hasClass("active")) {
                base.showDetail($head);
            } else {
                base.hideDetail($head);
            }
        };

        base.showDetail = function($head) {
            if ($head && $head != base.$activeDetailHead) {
                var $detail = $head.data("$detail");

                base.hideDetail(base.$activeDetailHead);

                $head.addClass("active");
                $detail.addClass("active");
                $detail.slideDown(base.options.speed);

                base.$activeDetailHead = $head;
            }
        };

        base.hideDetail = function($head) {
            if ($head) {
                var $detail = $head.data("$detail");

                $detail.removeClass("active");
                $detail.slideUp(base.options.speed, function() {
                    $head.removeClass("active");
                });
            }
        };

        // Run initializer
        base.init();
    };

    $.depage.details.defaultOptions = {
        speed: "normal",
        correctScroll: false
    };

    $.fn.depageDetails = function(options){
        return this.each(function(){
            (new $.depage.details(this, options));
        });
    };

})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
