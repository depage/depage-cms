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
        var keywords = [];
        
        // {{{ init()
        base.init = function() {
            if( typeof( itemSelector ) === "undefined" || itemSelector === null ) itemSelector = "default"; // @todo throw error, not optional!
            if( typeof( searchSelector ) === "undefined" || searchSelector === null ) searchSelector = "default"; // @todo throw error, not optional!
            
            base.itemSelector = itemSelector;
            base.searchSelector = searchSelector;
            
            base.options = $.extend({},$.depage.liveFilter.defaultOptions, options);
            
            // Put your initialization code here
            var $container = $("<div class=\"" + base.options.inputClass +  "\"></div>").insertBefore(base.$el);
            base.$input = $("<input type=\"search\" placeholder=\"" + base.options.placeholder + "\">").prependTo($container);

            base.$input.on("input keyup", function() {
                base.filterBy(this.value);
            });

            base.updateItems();
        };
        // }}}
        // {{{ updateItems
        base.updateItems = function() {
            $items = $(base.itemSelector, base.$el);

            for (var i = 0; i < $items.length; i++) {
                keywords[i] = $(base.searchSelector, $items[i]).text().toLowerCase();
            }
        };
        // }}}
        // {{{ filterBy
        base.filterBy = function(searchVal) {
            var values = searchVal.toLowerCase().split(" ");

            for (var i = 0; i < $items.length; i++) {
                var found = true;

                for (var j = 0; j < values.length && found; j++) {
                    found = found && keywords[i].indexOf(values[j]) != -1;
                }

                if (found) {
                    $items.eq(i).show();
                } else {
                    $items.eq(i).hide();
                }
            }
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    $.depage.liveFilter.defaultOptions = {
        inputClass: "depage-live-filter",
        placeholder: "Search"
    };
    
    $.fn.depageLiveFilter = function(itemSelector, searchSelector, options){
        return this.each(function(){
            (new $.depage.liveFilter(this, itemSelector, searchSelector, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
