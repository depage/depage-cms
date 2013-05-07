/**
 * @require framework/shared/jquery-1.8.3.js
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
    // add focus expression for jquery > 1.6
    $.expr[':'].focus = function( elem ) {
        return elem === document.activeElement && ( elem.type || elem.href );
    };
    
    if(!$.depage){
        $.depage = {};
    }
    
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
        base.$form = null;
        base.$items = null;
        
        var $body = $('body');
        base.visible = false;
        
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
            
            base.options.list_id = base.options.list_id || (base.$el.parents("p.input-text").attr("id") + "-list");
            
            // disable browser autocomplete
            base.$el.attr("autocomplete", "off");
            
            base.setup();
            base.autocomplete();
        };
        // }}}

        // {{{ select
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
                    // @TODO check search term if changed from last search
                    // @TODO add timer so that we do not search for every keypress, when someone types fast
                    var code = e.keyCode ? e.keyCode : e.which;
                    if(!(code == 40 || code == 38 || code == 13 ||  code == 27)) { // ignore arrow and enter keys
                        var url = $("base").attr("href") + $('html').attr('lang') + base.options.url + "?ajax=true" + "&value=" + $(this).val();

                        base.$items = null;
                        $.get(url , null, function(data) {
                            base.$el.trigger("load.autocomplete", [$(data)]);
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
            base.$form = base.$el.parents("form");
            if (!base.$list.length){
                // add a hidden <ul> for the autocomplete list if it doesn not already exist
                base.$list = $("<ul class='autocomplete' />")
                    .attr({
                        "id" : base.options.list_id
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
             * Load
             * 
             * Bind to the autocomplete load event and setup the dynamic functionality.
             */
            base.$el.bind("load.autocomplete", function(e, $newItems) {
                base.$list.empty();
                base.$items = null;
                if (!$newItems) {
                    return;
                }
                $newItems = $newItems.children("li");
                
                // truncate the list
                if(base.options.max_items){
                    $newItems = $newItems.slice(0,base.options.max_items -1);
                }
                base.$items = $newItems;

                // append the list items...
                base.$list.append(base.$items);
                // on click select the list item.
                base.$items.click(function(e) {
                    select(e, $(this));
                    return false;
                });
                
                if(base.$items.length) {
                    if (base.$items.filter(".hover").length === 0) {
                        $(base.$items[0]).addClass("hover");
                    }
                    // add hover class on mouse over
                    base.$items.hover(function(){
                        base.$items.filter(".hover").removeClass("hover");
                        $(this).addClass("hover");
                    });
                    base.show();
                } else {
                    base.hide();
                }
            });
            
            // Bind to keyup events on the input
            base.$el.bind("keyup.autocomplete", function(e) {
                var code = e.keyCode ? e.keyCode : e.which;

                if (code == 27) {
                    // escape key
                    base.hide();
                    base.$el.val("");
                } 
                if (base.$items) {
                    // find the selected list item
                    var $item = base.$items.filter(".hover").removeClass("hover");
                    if ($item.length){
                        switch (code) {
                            case 40 : // arrow down
                                $item = $item.next();
                                if (!$item.length) $item = base.$items.first();
                                break;
                            case 38 : // arrow up
                                $item = $item.prev();
                                if (!$item.length) $item = base.$items.last();
                                break;
                            case 13 : // enter key
                                select(e, $item);
                                base.$el.val("");
                                break;
                        } 
                    } else {
                        // default to the first item
                        $item = $(base.$items[0]);
                    }
                    // show the hover class on the selected itm
                    $item.addClass("hover");
                }
                return false;
            });

            base.$form.bind("submit.autocomplete", function(e) {
                if (base.$el.is("input:focus")) {
                    // stop submission when the input has the focus to capture submission on enter
                    e.stopPropagation();
                    e.preventDefault();
                    return false;
                }
            });
        };
        // }}}
        
        // {{{ base.show()
        /**
         * Base Show 
         */
        base.show = function() {
            if (base.visible) {
                return;
            }
                    
            // we have items so position and show the list
            base.$list
                .css({
                    "left" : base.$el.offset().left,
                    "top" : base.$el.offset().top + base.$el.height()
                })
                .show();

            /**
             * Remove menu on click out 
             */
            $body.bind('click.autocomplete', function(e) {
                if (e.target.type !== 'submit') {
                    base.hide();
                }
            });

            base.visible = true;

            return false;
        };
        // }}}
        
        // {{{ base.hide()
        /**
         * Base Hide 
         */
        base.hide = function() {
            if (!base.visible) {
                return false;
            }

            $body.unbind('click.autocomplete');
            base.$list.hide();

            base.visible = false;

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
