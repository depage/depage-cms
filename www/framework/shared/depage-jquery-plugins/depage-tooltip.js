/**
 * @require framework/shared/jquery-1.12.3.min.js
 * @require framework/shared/depage-jquery-plugins/depage-markerbox.js
 *
 * @file    depage-tool-tip
 *
 * Tooltip box extends Marker box
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
     * tooltip
     *
     * @param el - file input
     * @param index
     * @param options
     */
    $.depage.tooltip = function(el, index, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        var showTimeout = null;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.tooltip", base);

        // {{{ init
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.tooltip.defaultOptions, options);
            $.extend(base, $.depage.markerbox(base.options));
            base.tip();
        };
        // }}}

        // {{{ tip()
        /**
         * tip
         *
         * @return void
         */
        base.tip = function(){
            base.$el
                .bind('mouseenter.tooltip', function(e) {
                    clearTimeout(showTimeout);

                    showTimeout = setTimeout(function() {
                        base.show();
                    }, base.options.fadeinTimeout);
                    return false;
                })
                .bind('mouseleave.tooltip', function(e) {
                    clearTimeout(showTimeout);

                    var hideIfOut = function(e) {
                        // FF does not have toElement, and only relatedTarget on mouseleave - e.target is for mousemove
                        if ($(e.toElement || e.relatedTarget || e.target).parents('#depage-tooltip').length === 0) {
                            base.hide();
                            return true;
                        }
                        return false;
                    };

                    if (!hideIfOut(e)) {
                        var $document = $(document);
                        // workaround for mouseleave events caused by the dialogue appearance
                        $document.bind('mousemove.tooltip', function(e){
                            if(hideIfOut(e)){
                                $document.unbind('mousemove.tooltip');
                            }
                        });
                    }
                    return false;
                });
        };
        /// }}}

        base.init();
        return base;
    };
    // }}}

    /**
     * Default Options
     *
     * id - the id of the dialogue element wrapper to display
     * message - message the dialouge will display
     * buttons - buttons to supply (with corresponding event triggered)
     * classes - css classes to supply to the wrapper and content elements
     *
     */
    $.depage.tooltip.defaultOptions = {
        id : 'depage-tooltip',
        classes : 'depage-tooltip',
        icon: '',
        title: '',
        message: '',
        direction : 'TL',
        directionMarker : null,
        fadeinTimeout: 500,
        fadeinDuration: 200,
        fadeoutDuration: 200
    };

    $.fn.depageTooltip = function(options){
        return this.each(function(index){
            (new $.depage.tooltip(this, index, options));
        });
    };

})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
