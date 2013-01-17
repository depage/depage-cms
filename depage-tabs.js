/**
 * @require framework/shared/jquery-1.8.3.js
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
        
        // store the tabs
        var $tabs = [];
        
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
            $tabs = $('ul.' + base.options.classes.ul + ' li a', base.$el);
            // go
            base.tabs();
        };
        // }}}
        
        // {{{ tabs()
        /**
         * Tabs
         * 
         * @return void
         */
        base.tabs = function(){
            base.hide();
            $tabs.each(function(){
                var $tab = $(this);
                var href = $tab.attr('href');
                if (base.isAjaxTab(href)) {
                    base.axTabs.init($tab);
                } else {
                    base.jsTabs.init($tab);
                }
            });
        };
        // }}}
        
        // {{{ isAjaxTab()
        /**
         * isAjaxTab
         * 
         * Determines if this tab is in ajax or js mode:
         * 
         * If the href is different to the page base url use ajax.
         * 
         * If the href contains a hash which matches an id on the page we
         * are loading content dynamically with javascript. 
         * 
         * @return bool
         */
        base.isAjaxTab = function(href) {
            if (!base.options.force_ajax && !href.match('^' + $('base').attr('href'))) {
                var hash = href.match("#[^?&/]+")[0];
                return !(hash && $(hash).length);
            }
            return true;
        };
        // }}}
        
        // {{{ jsTabs()
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
            init : function($tab) {
                /*
                 * show the active tab
                 */
                if ($tab.parent('li').hasClass(base.options.classes.active)) {
                    base.jsTabs.load(null, $tab.attr('href'));
                }
                /*
                 * add tab click handlers
                 */
                $tab.click(function(e) {
                    base.hide();
                    base.jsTabs.load(e, $tab.attr('href'));
                    base.setActive($(this));
                    base.$el.trigger('select', e);
                    return false;
                });
            },
            
            /**
             * Load
             * 
             * @param href - the anchor name to show
             */
            load : function(e, href) {
                // get the anchor name
                href = href.substring(href.indexOf('#'), href.length);
                var $data = $(href).show();
                base.$el.trigger('load', ['js', $data]);
            }
        };
        // }}}
        
        // {{{ axTabs
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
            init : function($tab) {
                /*
                 * Show the active tab
                 */
                $('div.' + base.options.classes.content + ':first').show();
                
                /*
                 * Add Tab click handlers
                 */
                $tab.click(function(e) {
                    base.hide();
                    base.axTabs.load(e, $tab.attr('href'));
                    base.setActive($(this));
                    base.$el.trigger('select', e);
                    return false;
                });
            },
            
            /**
             * Load
             * 
             * @TODO loading gif?
             * 
             * @param href -  the ajax url to fetch content from
             */
            load : function(e, href) {
                // remove url hash component
                href = href.substring(0, href.indexOf('#') > 0 ? href.indexOf('#') : href.length);
                $.get(href, {"ajax":"true"}, function(data) {
                    var $data = $(data);
                    $('div.' + base.options.classes.content + ':first').empty().html($data).show();
                    if(typeof(history.pushState) !== 'undefined' && e.type !== 'popstate') {
                        history.pushState($(e.target).attr('href'), e.target.textContent, href);
                        $(window).unbind('popstate').bind('popstate', function(pop) {
                            var href = pop.originalEvent.state;
                            base.axTabs.load(pop, href);
                            base.setActive($('a[href="' + href + '"]', base.$el));
                            return false;
                        });
                    }
                    base.$el.trigger('load', ['ajax', $data]);
                });
            }
        };
        // }}}
        
        // {{{ setActive()
        /**
         * Set Active
         * 
         * Set the tab Active class
         * 
         * @param $tab - the active tab
         */
        base.setActive = function($tab){
            $tabs.parent('li').removeClass(base.options.classes.active);
            $tab.parent('li').addClass(base.options.classes.active);
        };
        // }}}
        
        // {{{ hide()
        /**
         * Hide
         * 
         * Hide inactive containers
         */
        base.hide = function() {
            $('div.' + base.options.classes.content).hide();
        };
        // }}}
        
        base.init();
    };
    
    /**
     * Options
     * 
     * @param classes styles to apply to tabs, active tab, and content containers 
     * 
     */
    $.depage.tabs.defaultOptions = {
        classes : {
            ul      : 'nav',
            active  : 'active',
            content : 'content'
        },
        force_ajax : false
    };
    
    $.fn.depageTabs = function(options){
        return this.each(function(index){
            (new $.depage.tabs(this, index, options));
        });
    };
    
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
