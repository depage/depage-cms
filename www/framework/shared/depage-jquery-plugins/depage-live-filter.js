;(function($){
    if(!$.depage){
        $.depage = {};
    }

    $.depage.liveFilter = function(el, itemSelector, searchSelector, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        if (base.$el.data("depage.liveFilter") !== undefined) {
            // test if this is already a liveFilter object
            // @todo remove and re-add liveFilter when called with different options
            console.log("is already a liveFilter");
            return;
        }

        // Add a reverse reference to the DOM object
        base.$el.data("depage.liveFilter", base);

        var $items = null;
        var $topItem = null;
        var keywords = [];

        // {{{ init()
        base.init = function() {
            if( typeof( itemSelector ) === "undefined" || itemSelector === null ) itemSelector = "default"; // @todo throw error, not optional!
            if( typeof( searchSelector ) === "undefined" || searchSelector === null ) searchSelector = "default"; // @todo throw error, not optional!

            base.itemSelector = itemSelector;
            base.searchSelector = searchSelector;

            base.options = $.extend({},$.depage.liveFilter.defaultOptions, options);

            var extraAttr = ' autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"';
            if (base.options.autofocus) {
                extraAttr += ' autofocus';
            }

            // Put your initialization code here
            var $container;

            if (base.options.attachInputInside) {
                $container = $("<div class=\"" + base.options.inputClass +  "\"></div>").prependTo(base.$el);
            } else {
                $container = $("<div class=\"" + base.options.inputClass +  "\"></div>").insertBefore(base.$el);
            }
            base.$input = $("<input type=\"search\" placeholder=\"" + base.options.placeholder + "\"" + extraAttr + ">").prependTo($container);

            if (base.options.autofocus) {
                base.$input.focus();
            }

            base.$input.on("input keyup", function(e) {
                var key = e.which || e.keyCode;
                if (key == 27) {
                    // clear filter on escape key
                    this.value = "";

                    e.stopPropagation();
                } else if (key == 13) {
                    if ($topItem !== null) {
                        // leave input on enter
                        this.blur();

                        if (typeof base.options.onSelect == 'function') {
                            base.options.onSelect($topItem);
                        }
                    }

                    e.stopPropagation();
                }
                base.filterBy(this.value);
            });

            base.updateItems();
        };
        // }}}
        // {{{ updateItems
        base.updateItems = function() {
            $items = $(base.itemSelector, base.$el);
            keywords = [];

            for (var i = 0; i < $items.length; i++) {
                keywords[i] = $(base.searchSelector, $items[i]).text().toLowerCase();
            }

            if ($items.length > 1) {
                base.$input.removeAttr("disabled");
            } else {
                base.$input.attr("disabled", "disabled");
            }
        };
        // }}}
        // {{{ filterBy
        base.filterBy = function(searchVal) {
            var values = searchVal.toLowerCase().split(" ");
            $topItem = null;

            for (var i = 0; i < $items.length; i++) {
                var found = true;

                for (var j = 0; j < values.length && found; j++) {
                    found = found && keywords[i].indexOf(values[j]) != -1;
                }

                var $item = $items.eq(i);

                if (found) {
                    if ($item.is(":hidden")) {
                        $item.show();
                        $item.trigger("depage.filter-shown", [$item]);
                    }

                    if ($topItem === null) {
                        $topItem = $item;
                    }
                } else {
                    if ($item.is(":visible")) {
                        $item.hide();
                        $item.trigger("depage.filter-hidden", [$item]);
                    }
                }
            }
            // @todo add placeholder message when list is empty
        };
        // }}}

        // Run initializer
        base.init();
    };

    $.depage.liveFilter.defaultOptions = {
        inputClass: "depage-live-filter",
        placeholder: "Search",
        attachInputInside: false,
        onSelect: null,
        autofocus: false
    };

    $.fn.depageLiveFilter = function(itemSelector, searchSelector, options){
        return this.each(function(){
            (new $.depage.liveFilter(this, itemSelector, searchSelector, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
