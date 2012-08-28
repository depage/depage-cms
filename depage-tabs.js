/**
 * @require framework/shared/jquery-1.4.2.js
 * 
 * @file    depage-tabs
 *
 * Depage Tabs Plugin.
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
     * tabs
     * 
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.tabs = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.tabs", base);
        
        // {{{ init
        /**
         * Init
         * 
         * Get the plugin options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.tabs.defaultOptions, options);
            base.tabs();
        };
        // }}}
        
        // {{{ tags()
        /**
         * Tabs
         * 
         * @return void
         */
        base.tabs = function(){
            $('li a', base.$el).each(function(){
                var $this = $(this);
                var href = $this.attr('href');
                if (href.substring(0,1) === '#') {
                    base.jsTabs.init();
                } else {
                    base.axTabs.init();
                }
            });
        };
        
        /**
         * jsTabs
         * 
         * In this mode the plugin is operating by showing / hiding .content divs. 
         * 
         */
        base.jsTabs = {
                
            /**
             * Init
             */
            init : function() {
                base.jsTabs.hide();
                /*
                 * show the active tab
                 */
                if ($this.hasClass(base.options.classes.active)) {
                    base.jsTabs.show($this.attr('href'));
                }
                /*
                 * add tab click handlers
                 */
                $this.click(function() {
                    base.jsTabs.show($(this).attr('href'));
                    return false;
                });
            },
            
            /**
             * Hide
             */
            hide : function() {
                $('div.' + base.options.classes.content).each(function() {
                    $(this).hide();
                });
            },
            
            /**
             * Show
             * 
             * @param href - the anchor name to show
             */
            show : function(href) {
                var href = $(this).attr('href');
                // get the anchor name
                href = href.substring(1,href.length);
                $('a[name=' + href + ']').parent('div.' + base.options.classes.content).show();
                base.$el.trigger('tab_show');
            }
        };
        
        
        /**
         * axTabs
         * 
         * In this mode the plugin is loading new contact into the main .content div via AJAX.
         * 
         */
        base.axTabs = {
                
            /**
             * Init
             */
            init : function() {
                var $this = $(this);
                base.axTabs.hide();
                /*
                 * Show the active tab
                 */
                if ($this.hasClass(base.options.classes.active)) {
                    base.axTabs.show(href);
                }
                /*
                 * Add Tab click handlers
                 */
                $this.click(function() {
                    base.axTabs.show($(this).attr('href'));
                    return false;
                });
            },
            
            /**
             * Hide
             * 
             * Hide inactive containers
             */
            hide : function() {
                $('div.' + base.options.classes.content + '.not(:first)').each(function() {
                    $(this).hide();
                });
            },
            
            /**
             * Show
             * 
             * @param href -  the ajax url to fetch content from
             */
            show : function(href) {
                $.get(href, null, function(data){
                    $('div.' + base.options.classes.content + ':first').replaceWith($(data).find('div.' + base.options.classes.content));
                    history.pushState({}, 'title', href);
                    base.$el.trigger('tab_show');
                });
            }
        };
        
        base.init();
    };
    
    /**
     * Options
     * 
     */
    $.depage.tabs.defaultOptions = {
        classes : {
            active  : 'active',
            content : 'content'
        }
    };
    
    $.fn.depageTabs = function(options){
        return this.each(function(index){
            (new $.depage.tabs(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
