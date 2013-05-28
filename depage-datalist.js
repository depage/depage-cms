/**
 * @require framework/shared/jquery-1.8.3.js
 * @require framework/shared/depage-jquery-plugins/depage-autocomplete.js
 * 
 * @file    depage-datalist
 *
 * Depage DataList plugin to supply user with hints or data while filling form text inputs.
 * 
 * Adds backwards compatibility to html5 datalist using the depage-autocomplete.js plugin
 * 
 * Provide an optional url to dynamically load via AJAX
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
     * datalist
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.datalist = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.datalist", base);
        
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
            base.options = $.extend({}, $.depage.datalist.defaultOptions, options);
            
            base.datalist = (typeof(HTMLDataListElement) !== "undefined"); // && false; // DEBUG FALLBACK
            
            // disable browser autocomplete
            base.$el.attr("autocomplete", "off");
            
            if (base.datalist) {
                base.datalist();
            } else {
                base.fallback();
            }
            
        };
        // }}}
        
        // {{{ datalist()
        /**
         * datalist
         * 
         * Binds to keypress and loads list element with options.
         * 
         * Note that the "datalist" paramteter of the ajax request determines the format of returned HTML:
         * i.e. <option> elements for datalist = true, otherwise <li> elements for setup.
         *  
         * @return void
         */
        base.autocomplete = function(){
            if(base.options.url) {
                base.$el.bind("keypress.datalist", function() {
                    
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
         * Fallback
         * 
         * Replace <datalist> with <ul> list.
         * Clicking <li> adds contents to the input element.
         * 
         */
        base.setup =  function(){
            var id = base.$list.attr("id");
            $ul = $("<ul class='autocomplete' />").attr("id", base.$list.attr("id")).hide();
            base.$list.replaceWith($ul);
            
            base.$el.depageAutoComplete({"url":base.options.url, "list_id": id});
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
     * TODO implement fallback for no url
     */
    $.depage.autocomplete.defaultOptions = {
        url : false,
    };
    
    $.fn.depageDataList = function(options){
        return this.each(function(index){
            (new $.depage.autocomplete(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
