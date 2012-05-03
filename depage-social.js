;(function($) {
    if (!$.depage) {
        $.depage = {};
    }
    
    $.depage.socialButtons = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.socialButtons", base);

        // {{{ init
        base.init = function(){
            base.options = $.extend({}, $.depage.socialButtons.defaultOptions, options);

            $.each(base.options.services, function(i, name) {
                // normalize name
                name = name.toLowerCase();
                name = name.charAt(0).toUpperCase() + name.slice(1);
                
                var functionName = 'add' + name;

                if (typeof base[functionName] === 'function') {
                    base[functionName](name, location);
                } else if (window.console) {
                    console.log("social button type '" + name + "' unknown.");
                }
            });
        };
        // }}}
        
        // {{{ addSocialButton
        base.addSocialButton = function(name, link, text) {
            var html = "";

            html += "<a href=\"" + link + "\" class=\"" + name + "\">";
            if (base.options.assetPath !== "") {
                // image link
                html += "<img src=\"" + base.options.assetPath + name.toLowerCase() + ".png\" alt=\"" + name + "\">";
            } else {
                // text link
                html += "<span class=\"" + name.toLowerCase() + "\">" + text + "</span>";
            }
            html += "</a>";

            base.$el.append(html);
        };
        // }}}
        
        // {{{ addTwitter
        base.addTwitter = function(name) {
            var link = "twitter";
            base.addSocialButton(name, link, "t");
        };
        // }}}
        // {{{ addFacebookshare
        base.addFacebookshare = function(name) {
            var link = "facebookShare";
            base.addSocialButton(name, link, "f");
        };
        // }}}
        // {{{ addFacebooklike
        base.addFacebooklike = function(name) {
            var link = "facebookLike";
            base.addSocialButton(name, link, "♥");
        };
        // }}}
        // {{{ addGoogleplusshare
        base.addGoogleplusshare = function(name) {
            var link = "googleplusShare";
            base.addSocialButton(name, link, "+1");
        };
        // }}}
        // {{{ addDigg
        base.addDigg = function(name) {
            var link = "digg";
            base.addSocialButton(name, link, "digg");
        };
        // }}}
        // {{{ addReddit
        base.addReddit = function(name) {
            var link = "reddit";
            base.addSocialButton(name, link, "reddit");
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    /**
     * Options
     * 
     * @param assetPath - path to the asset-folder (with flash-player and images for buttons)
     * @param twitter - add twitter buztton
     * @param facebookShare - add facebook share button
     * @param googleplusShare - add google+ share button
     * @param facebookLike - add facebook like button
     */
    $.depage.socialButtons.defaultOptions = {
        assetPath: '',
        location: document.location.href,
        title: $("head title").text(),
        services: [
            'twitter',
            'facebookShare',
            'googleplusShare',
            'facebookLike',
            'digg',
            'reddit'
        ]
    };
    
    $.fn.depageSocialButtons = function(options) {
        return this.each(function(){
            (new $.depage.socialButtons(this, options));
        });
    };
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
