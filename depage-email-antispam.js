/**
 * @require framework/shared/jquery-1.8.3.js
 *
 * @file    depage-email-antispam.js
 *
 * replaces anti-spam-modified emails with clickable email-links
 *
 *
 * copyright (c) 2009-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    }
    
    $.depage.antispam = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.antispam", base);
        
        base.init = function(){
            base.options = $.extend({},$.depage.antispam.defaultOptions, options);
            
            // Put your initialization code here
            base.$el.each(function() {
                var $emailLink = $(this);

                // replace attribute
                $emailLink.attr("href", base.replaceEmailChars($emailLink.attr("href")));
                
                //replace content if necessary
                if ($emailLink.text().indexOf(" *at* ") > 0) {
                    $emailLink.text(base.replaceEmailChars($emailLink.text()));
                }
            });
        };
        // {{{ replaceEmailChars()
        base.replaceEmailChars = function(mail) {
            mail = unescape(mail);
            mail = mail.replace(/ \*at\* /g, "@");
            mail = mail.replace(/ \*dot\* /g, ".");
            mail = mail.replace(/ \*punkt\* /g, ".");
            mail = mail.replace(/ \*underscore\* /g, "_");
            mail = mail.replace(/ \*unterstrich\* /g, "_");
            mail = mail.replace(/ \*minus\* /g, "-");
            mail = mail.replace(/mailto: /, "mailto:");

            return mail;
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    $.depage.antispam.defaultOptions = {
        option1: "default"
    };
    
    $.fn.depageAntispam = function(options){
        return this.each(function(){
            (new $.depage.antispam(this, options));
        });
    };
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
