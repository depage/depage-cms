/**
 * @require framework/shared/jquery-1.4.2.js
 * 
 * @file    depage-autocomplete
 *
 * Depage AutoComplete plugin to supply user with hints or data while filling forms.
 * 
 * Provide a url in the options to dynamically load via AJAX an HTML5 datalist
 * filtered on the value of the input element. 
 * 
 * If the browser does not support datalists an unordered list of hyperlinks is built with
 * functionality to mimic the datalist behaviour.
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    // shiv {{{ 
    /**
     * Shiv DataList
     * 
     * Adds datalist element to the DOM to enable IE < 9.
     * 
     * @return void
     */
    if ($.browser.msie && $.browser.version < 9) {
        $('head').append('<style>datalist{display:none}</style>');
        document.createElement("datalist");
    }
    // }}}
    
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
        
        // HTML5 datalist element is supported 
        base.datalist = true;
        
        // List element associated with input
        base.$list = $(base.$el.attr('list'));
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.shyDialogue.defaultOptions, options);
            
            base.datalist =  (typeof(HTMLDataListElement) !== "undefined"); // && false; // DEBUG fallback
            
            if (!base.datalist) {
                base.fallback();
            }
            
            base.autocomplete();
        };
        // }}}
        
        // {{{ autocomplete()
        /**
         * autocomplete
         * 
         * Binds to keypress and loads list element with options.
         * 
         * Note that the "datalist" paramteter of the ajax request determines the format of returned HTML.
         * i.e. <option> elements for datalist = true, otherwise <li> elements for fallback.
         *  
         * @return void
         */
        base.autocomplete = function(){
            if(base.options.url) {
                base.$el.bind("keypress.autocomplete", function() {
                    
                    var url = $("base").attr("href")
                        + $('html').attr('lang')
                        + base.options.url
                        + "?ajax=true"
                        + "&datalist=" + base.datalist
                        + "&value=" + $(this).val();
                    
                    $.get(url , null, function(data) {
                        var $data = $(data);
                        base.$list.empty().append($data);
                        base.$el.trigger("load", [$data]);
                    });
                });
            }
        };
        /// }}}
        
        // {{{ fallback()
        /**
         * Setup DataList Fallback
         * 
         * Replace <datalist> with <ul> list.
         * Clicking <li> adds contents to the input element.
         * 
         */
        base.fallback =  function(){
            $ul = $("<ul class='autocomplete' />").attr("id", base.$list.attr("id")).hide();
            base.$list.replaceWith($ul);
            base.$list = $ul;
            
            /*
             * Select
             * 
             * Set the list-item as selected, and hide the autocompelete list.
             * 
             * @param $item - $('li') 
             */
            var select = function($item) {
                base.$el.val($item.text());
                base.$list.hide();
            };
            
            /*
             * Load
             * 
             * Bind to the autocomplete load event and setup the dynamic functionality.
             */
            base.$el.bind("load", function(e, $data) {
                var $items = $data.children("a");
                
                // on click select the list item.
                $items.click(function() {
                    select($(this).text());
                    return false;
                });
                
                if($items.length) {
                    $items = $data.filter("li");
                    
                     // add hover class on mouse over
                    $items.hover(function(){
                        $items.filter(".hover").removeClass("hover");
                        $(this).addClass("hover");
                    });
                    
                    // Bind to keyup events on the input
                    base.$el.bind('keyup.autocomplete', function(e) {
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
                                    select($item);
                                    break;
                            } 
                        } else {
                            // default to the first item
                            $item = $($items[0]);
                        }
                        // show the hover class on the selected itm
                        $item.addClass("hover");
                    });
                    // we have items so show the list
                    base.$list.show();
                }
            });
        };
        // }}}
        
        base.init();
    };
    // }}}
    
    /**
     * Default Options
     * 
     * url - the ajax lander url
     * 
     */
    $.depage.autocomplete.defaultOptions = {
        url : false,
    };
    
    $.fn.depageAutoComplete = function(options){
        return this.each(function(index){
            (new $.depage.autocomplete(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
