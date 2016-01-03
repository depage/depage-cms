;(function($){
    if(!$.depage){
        $.depage = {};
    }

    $.depage.livehelp = function(el, options){
        // {{{ variables
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        var $html = $("html");
        var $window = $(window);

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.livehelp", base);

        base.$helpPane = false;
        base.$helpElements = false;
        // }}}

        // {{{ init()
        base.init = function(){
            base.options = $.extend({},$.depage.livehelp.defaultOptions, options);

            // Put your initialization code here
            base.$el.on("click", base.toggleHelp);

            base.showHelp();

            $window.on("resize", onResize);
        };
        // }}}

        // {{{ onResize()
        onResize = function(){
            if (!base.$helpPane) return;

            base.$helpElements = $("*[data-live-help]");

            var width = $html.width();
            var height = $html.height();

            if (width < window.innerWidth) {
                width = window.innerWidth;
            }
            if (height < window.innerHeight) {
                height = window.innerHeight;
            }
            base.$helpPane.width(width);
            base.$helpPane.height(height);

            base.$helpElements.each(function(i, el) {
                // original element
                var $el = $(el);

                // current help display
                var $div = base.$helpDivs.eq(i);

                var offset = $el.offset();
                var css = {};
                var isAreaElement = $el.width() > 100 || $el.height() > 100;

                // horizontal position
                if (isAreaElement) {
                    css.left = offset.left + 30;
                } else if (offset.left < width / 2) {
                    css.left = offset.left;
                } else {
                    css.right = width - offset.left - $el.outerWidth();
                }

                // vertical position
                if (isAreaElement) {
                    css.top = offset.top + 30;
                } else if (offset.top < height / 3 * 2) {
                    css.top = offset.top + 30;
                } else {
                    css.bottom = height - offset.top - $el.outerHeight();
                }

                // width
                if (isAreaElement) {
                    css.maxWidth = $el.outerWidth() - 60;
                }

                $div.attr("style", "").css(css);
            });
        };
        // }}}

        // {{{ toggleHelp()
        base.toggleHelp = function(){
            if (!base.$helpPane) {
                base.showHelp();
            } else {
                base.hideHelp();
            }

            base.$el.blur();
        };
        // }}}
        // {{{ showHelp()
        base.showHelp = function(){
            $html.trigger("depage.livehelp.show");

            base.$helpPane = $("<div id=\"depage-live-help\"></div>").appendTo("body");
            base.$helpElements = $("*[data-live-help]");

            base.$helpElements.each(function() {
                var helpText = $(this).attr("data-live-help");
                var $div = $("<div></div>").text(helpText).appendTo(base.$helpPane);

                if (helpText.length > 100) {
                    $div.addClass("big");
                }
            });
            base.$helpDivs = base.$helpPane.children("div");

            base.$helpPane.on("click", base.toggleHelp);

            setTimeout(function() {
                onResize();

                base.$helpPane.addClass("visible");
                base.$el.addClass("active");
            }, 100);
        };
        // }}}
        // {{{ hideHelp()
        base.hideHelp = function() {
            base.$helpPane.removeClass("visible");

            setTimeout(function() {
                base.$el.removeClass("active");

                base.$helpPane.remove();
                base.$helpPane = false;

                $html.trigger("depage.livehelp.hide");
            }, 500);
        };
        // }}}

        // Run initializer
        base.init();
    };

    $.depage.livehelp.defaultOptions = {
        option1: "default"
    };

    $.fn.depageLivehelp = function(options){
        return this.each(function(){
            (new $.depage.livehelp(this, options));
        });
    };

})(jQuery);

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
