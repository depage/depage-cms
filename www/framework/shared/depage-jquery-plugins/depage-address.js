/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-address
 *
 * Depage Address plugin to handle client-side address fields.
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };

    /**
     * datalist
     *
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.address = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.address", base);

        var $state = null;
        var $country = null;

        // {{{ init
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.address.defaultOptions, options);
            $country = $(base.options.country_selector, base.$el);
            $state = $(base.options.state_selector, base.$el);
            base.address();
        };
        // }}}

        // {{{ address()
        /**
         * address
         *
         * @return void
         */
        base.address = function(){
            var $optgroups = $state.find('optgroup');
            var filter = function() {
                $optgroup = $optgroups.filter("[label='" + $country.val() + "']");
                if ($optgroup.length){
                    $(base.options.state_selector, base.$el).html($optgroup);
                    $state.parents('.skin-select').show();
                } else {
                    $state.val([]);
                    $state.parents('.skin-select').hide();
                }
            };
            $country.change(filter);
            filter();
        };
        /// }}}

        base.init();
    };
    // }}}

    /**
     * Default Options
     *
     * city_selector - jQuery selector for the city element
     * state_selector - jQuery selector for the state element
     * country_selector - jQuery selector for the country element
     */
    $.depage.address.defaultOptions = {
        city_selector    : 'input[name="city"]',
        state_selector   : 'select[name="state"]',
        country_selector : 'select[name="country"]'
    };

    $.fn.depageAddress = function(options){
        return this.each(function(index){
            (new $.depage.address(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
