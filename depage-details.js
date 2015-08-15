/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-details.js
 *
 * adds details handler to definition-lists
 *
 *
 * copyright (c) 2011-2015 Frank Hellenkamp [jonas@depage.net]
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

        base.$heads = null;
        base.$activeDetailHead = null;

        // {{{ init()
        base.init = function() {
            base.options = $.extend({},$.depage.details.defaultOptions, options);

            base.$heads = $(base.options.head, base.el).each(function() {
                var $head = $(this).css({
                    cursor: "pointer"
                });
                var $detail = $head.nextUntil(base.options.head);
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
        // }}}
        // {{{ toggleDetail()
        base.toggleDetail = function() {
            var $head = $(this);

            if (!$head.hasClass("active")) {
                base.showDetail($head);
            } else {
                base.hideDetail($head);
            }
        };
        // }}}
        // {{{ showDetail()
        base.showDetail = function($head, customSpeed) {
            if ($head && $head != base.$activeDetailHead) {
                customSpeed = (typeof customSpeed === "undefined") ? base.options.speed : customSpeed;
                var $detail = $head.data("$detail");

                if (base.options.correctScroll && customSpeed !== 0) {
                    base.correctScroll(base.$activeDetailHead, $head);
                }
                base.hideDetail(base.$activeDetailHead);

                $head.addClass("active");
                $detail.addClass("active");
                $detail.slideDown(customSpeed);

                base.$activeDetailHead = $head;

                base.$el.trigger("depage.detail-opened", [$head, $detail]);
            }
        };
        // }}}
        // {{{ hideDetail
        base.hideDetail = function($head, customSpeed) {
            if ($head) {
                customSpeed = (typeof customSpeed === "undefined") ? base.options.speed : customSpeed;
                var $detail = $head.data("$detail");

                $detail.removeClass("active");
                $detail.slideUp(customSpeed, function() {
                    $head.removeClass("active");
                });

                base.$el.trigger("depage.detail-closed", [$head, $detail]);
            }
        };
        // }}}
        // {{{ correctScroll()
        base.correctScroll = function($oldHead, $newHead) {
            var oldHeight = 0;
            var oldIndex = -1;
            var newIndex = base.$heads.index($newHead);
            var scrollY = 0;

            if ($(window).height() < base.$el.height()) {
                if ($oldHead) {
                    // get height of old detail
                    oldHeight = $oldHead.data("$detail").height();
                    oldIndex = base.$heads.index($oldHead);
                }

                // calculate height to animate scroll
                if (newIndex > oldIndex) {
                    scrollY = $newHead.offset().top - oldHeight - base.options.scrollOffset;
                } else {
                    scrollY = $newHead.offset().top - base.options.scrollOffset;
                }

                $('html, body').animate({
                    scrollTop: scrollY
                }, base.options.speed);
            }
        };
        // }}}

        // Run initializer
        base.init();
    };

    $.depage.details.defaultOptions = {
        head: "dt",
        speed: 400,
        correctScroll: false,
        scrollOffset: 0
    };

    $.fn.depageDetails = function(options){
        return this.each(function(){
            (new $.depage.details(this, options));
        });
    };

})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
