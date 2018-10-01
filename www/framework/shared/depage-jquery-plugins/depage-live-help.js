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
        base.$helpSvg = false;
        // }}}

        // {{{ init()
        base.init = function(){
            base.options = $.extend({},$.depage.livehelp.defaultOptions, options);

            // Put your initialization code here
            base.$el.on("click", base.toggleHelp);

            //base.showHelp();

            $window.on("resize", onResize);
        };
        // }}}

        // {{{ onResize()
        onResize = function(){
            if (!base.$helpPane) return;

            if (base.$helpSvg) {
                base.$helpSvg.remove();
            }

            var width = $html.width();
            var height = $html.height();
            var svg = "<svg width=\"100%\" height=\"100%\">";

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
                var isAreaElement = $el.width() > 200 || $el.height() > 50;
                var isVisible = $el.is(":visible");

                // width
                if (isAreaElement) {
                    css.maxWidth = $el.outerWidth() - 40;

                    $div.addClass("area");
                }
                $div.attr("style", "").css(css);

                // horizontal position
                if (isAreaElement) {
                    //css.left = offset.left + 10;
                    css.left = offset.left + $el.outerWidth() / 2 - $div.outerWidth() / 2;
                } else if (offset.left < width / 2) {
                    css.left = offset.left;
                } else {
                    css.right = width - offset.left - $el.outerWidth();
                }

                if (css.left < 10) {
                    css.left = 10;
                }

                // vertical position
                if (isAreaElement) {
                    //css.top = offset.top + 10;
                    css.top = offset.top + $el.outerHeight() / 3 - $div.outerHeight() / 2;
                } else if (offset.top < height / 3 * 2) {
                    css.top = offset.top + $el.outerHeight() + 10;
                } else {
                    css.top = offset.top - $div.outerHeight() - 10;
                }

                if (!isVisible) {
                    css.top = 0;
                    css.left = -1000;
                }

                // calculate bounds
                var bounds = {};
                if (css.left) {
                    bounds.left = css.left;
                    bounds.right = bounds.left + $div.outerWidth();
                }
                if (css.right) {
                    bounds.right = width - css.right;
                    bounds.left = bounds.right - $div.outerWidth();
                }
                bounds.top = css.top;
                bounds.bottom = bounds.top + $div.outerHeight();

                // handle collisions
                for (var j = 0; j < i; j++) {
                    var $compare = base.$helpDivs.eq(j);
                    var compare = $compare.offset();

                    compare.right = compare.left + $compare.outerWidth();
                    compare.bottom = compare.top + $compare.outerHeight();

                    if (!(compare.right < bounds.left ||
                        compare.left > bounds.right ||
                        compare.bottom < bounds.top ||
                        compare.top > bounds.bottom)) {

                        // @todo change move direction depending on closeness to window borders
                        // @todo fix that elements never move out of content area
                        if (isAreaElement) {
                            bounds.top = compare.bottom + 30;
                        } else {
                            bounds.top = compare.bottom + 5;
                        }
                        bounds.bottom = bounds.top + $div.outerHeight();
                        css.top = bounds.top;
                    }
                }

                $div.css(css);

                // draw line
                if (!isAreaElement && isVisible) {
                    var x1, y1, x2, y2;
                    var o = 10;

                    if (offset.left < width / 2) {
                        x1 = offset.left + o;
                        x2 = bounds.left + o;
                    } else {
                        x1 = offset.left + $el.outerWidth() - o;
                        x2 = bounds.right - o;
                    }
                    if (offset.top < height / 3 * 2) {
                        y1 = offset.top + $el.outerHeight() - o;
                        y2 = bounds.top + o;
                    } else {
                        y1 = offset.top + o;
                        y2 = bounds.top + o;
                    }
                    svg += '<line x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '"/>';
                }

            });

            svg += "</svg>";
            base.$helpSvg = $(svg).appendTo(base.$helpPane);
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
            base.$helpPane = $("<div id=\"depage-live-help\"></div>").appendTo("body");
            base.$helpElements = $("*[data-live-help]").filter(function() {
                return !$(this).hasClass("no-live-help") && $(this).parents(".no-live-help").length == 0;
            });

            base.$helpElements.each(function() {
                var $this = $(this);

                var helpTexts = $this.attr("data-live-help").split("\\n");
                var helpHtml = $this.attr("data-live-help-html") || "";
                var classes = $this.attr("data-live-help-class") || "";
                var $div = $("<div class=\"" + classes + "\"></div>");

                for (var i = 0; i < helpTexts.length; i++) {
                    $("<p></p>").text(helpTexts[i]).appendTo($div);
                }
                if (helpHtml) {
                    $(helpHtml).appendTo($div);
                }
                $div.appendTo(base.$helpPane);

                if (helpTexts.join().length > 100) {
                    $div.addClass("big");
                }
                var $links = $div.find("a");

                if ($links.length == 1) {
                    $div.on("click", function(e) {
                        if (e.target.tagName != "A") {
                            $links.eq(0).click();
                        }
                    });
                }
            });

            base.$helpDivs = base.$helpPane.children("div");
            base.$helpPane.on("click", base.toggleHelp);

            $html.trigger("depage.livehelp.show");

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

                if (base.$helpPane) {
                    base.$helpPane.remove();
                }
                base.$helpPane = false;
                base.$helpElements = false;
                base.$helpSvg = false;

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
