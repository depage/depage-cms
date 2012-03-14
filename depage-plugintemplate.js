;(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.slideshow = function(el, param1, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.slideshow", base);
        
        base.init = function(){
            if( typeof( param1 ) === "undefined" || param1 === null ) param1 = "default";
            
            base.param1 = param1;
            
            base.options = $.extend({},$.depage.slideshow.defaultOptions, options);
            
            // Put your initialization code here
        };
        
        // Sample Function, Uncomment to use
        // base.functionName = function(paramaters){
        // 
        // };
        
        // Run initializer
        base.init();
    };
    
    $.depage.slideshow.defaultOptions = {
        option1: "default"
    };
    
    $.fn.depage_slideshow = function(param1, options){
        return this.each(function(){
            (new $.depage.slideshow(this, param1, options));
        });
    };
    
})(jQuery);
