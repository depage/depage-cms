/**
 * @require framework/shared/jquery-1.4.2.js
 * 
 * @file    depage-autocomplete
 *
 * Depage AutoComplete plugin to supply user with hints or data while filling forms.
 * 
 * Provide a url in the options to dynamically load via AJAX into the corresponding unordered list.
 * 
 * Fires a "selected" event when the item is picked
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    /**
     * autocomplete
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.autocomplete = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.autocomplete", base);
        
        // List element associated with input
        base.$list = null;
        
        var $body = $('body');
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.autocomplete.defaultOptions, options);
            
            base.options.list_id = base.options.list_id
                || (base.$el.parents("p.input-text").attr("id") + "-list");
            
            // disable browser autocomplete
            base.$el.attr("autocomplete", "off");
            
            base.setup();
            base.autocomplete();
        };
        // }}}
        
        // {{{ autocomplete()
        /**
         * autocomplete
         * 
         * Binds to keypress and loads list element with options.
         * 
         * @return void
         */
        base.autocomplete = function(){
            if(base.options.url) {
                base.$el.bind("keyup.autocomplete", function(e) {
                    var code = e.keyCode ? e.keyCode : e.which;
                    if(!(code == 40 || code == 38 || code == 13 ||  code == 27)) { // ignore arrow and enter keys
                        var url = $("base").attr("href")
                            + $('html').attr('lang')
                            + base.options.url
                            + "?ajax=true"
                            + "&value=" + $(this).val();
                        $.get(url , null, function(data) {
                            var $items = $(data);
                            base.$el.trigger("load.autocomplete", [$items]);
                        });
                    }
                });
            }
        };
        /// }}}
        
        // {{{ setup()
        /**
         * Setup UL
         * 
         * Clicking <li> adds contents to the input element.
         * 
         */
        base.setup =  function(){
            base.$list = $("#" + base.options.list_id);
            if (!base.$list.length){
                // add a hidden <ul> for the autocomplete list if it doesn not already exist
                base.$list = $("<ul class='autocomplete' />")
                    .attr({
                        "id" : base.options.list_id,
                    })
                    .css({
                        "position" : "absolute",
                        "left" : base.$el.offset().left,
                        "top" : base.$el.offset().top + base.$el.height(),
                        "z-index" : "1000",
                        "background-color" : "#FFF",
                        "width" : base.$el.width()
                    })
                    .hide();
                
                $body.prepend(base.$list);
            }
            
            /*
             * Select
             * 
             * Set the list-item as selected, and hide the autocompelete list.
             * 
             * @param $item - $('li') 
             */
            var select = function(e, $content) {
                $content.removeClass("hover");
                base.$el.val($content.find('.content').text());
                base.hide();
                base.$el.trigger("selected", [$content]);
            };
            
            /*
             * Load
             * 
             * Bind to the autocomplete load event and setup the dynamic functionality.
             */
            base.$el.bind("load.autocomplete", function(e, $items) {
                $items = $items.children("li");
                // truncate the list
                if(base.options.max_items){
                    $items = $items.slice(0,base.options.max_items -1);
                }
                // append the list items...
                base.$list.empty().append($items);
                // on click select the list item.
                $items.children("a").click(function(e) {
                    select(e, $(this).parent("li"));
                    return false;
                });
                
                if($items.length) {
                     // add hover class on mouse over
                    $items.hover(function(){
                        $items.filter(".hover").removeClass("hover");
                        $(this).addClass("hover");
                    });
                    
                    // Bind to keyup events on the input
                    base.$el.bind("keyup.autocomplete", function(e) {
                            // find the selected list item
                            var $item =  $items.filter(".hover").removeClass("hover");
                            if ($item.length){
                                var code = e.keyCode ? e.keyCode : e.which;
                                switch (code) {
                                    case 40 : // arrow down
                                        $item = $item.next();
                                        break;
                                    case 38 : // arrow up
                                        $item = $item.prev();
                                        break;
                                    case 13 : // enter key
                                        select(e, $item);
                                        break;
                                    case 27 : // escape key
                                        base.hide();
                                        break;
                                } 
                            } else {
                                // default to the first item
                                $item = $($items[0]);
                            }
                            // show the hover class on the selected itm
                            $item.addClass("hover");
                        });
                    
                    // we have items so position and show the list
                    base.$list
                        .css({
                            "left" : base.$el.offset().left,
                            "top" : base.$el.offset().top + base.$el.height(),
                        })
                        .show();
                    
                    /**
                     * Remove menu on click out 
                     */
                    $body.bind('click.autocomplete', function(e) {
                        if (e.target.type !== 'submit') {
                            $body.unbind('click.autocomplete');
                            base.hide();
                        }
                    });
                }
            });
        };
        // }}}
        
        // {{{ base.hide()
        /**
         * Base Hide 
         */
        base.hide = function() {
            base.$list.hide();
            return false;
        };
        // }}}
        
        base.init();
    };
    // }}}
    
    /**
     * Default Options
     * 
     * url - the ajax lander url
     * html5 - if false this will force the autoloader to work via ajax
     */
    $.depage.autocomplete.defaultOptions = {
        url : false,
        list_id : false,
        max_items : 8
    };
    
    $.fn.depageAutoComplete = function(options){
        return this.each(function(index){
            (new $.depage.autocomplete(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
