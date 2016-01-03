;(function($){
    if(!$.depage){
        $.depage = {};
    }

    $.depage.livehelp = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.livehelp", base);

        base.init = function(){
            base.options = $.extend({},$.depage.livehelp.defaultOptions, options);

            // Put your initialization code here
            base.$el.on("click", base.showHelp);
        };

        // {{{ showHelp()
        base.showHelp = function(){

        };
        // }}}
        // {{{ hideHelp()
        base.hideHelp = function() {

        };
        // }}}

        // Run initializer
        base.init();
    };

    $.depage.livehelp.defaultOptions = {
        option1: "default"
    };

    $.fn.depageLivehelp = function(options){
        return this.each(function(){
            (new $.depage.livehelp(this, options));
        });
    };

})(jQuery);

// vim:set ft=javascript sw=4 sts=4 fdm=marker :
