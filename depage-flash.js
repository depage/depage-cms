/**
 * @require framework/shared/jquery-1.4.2.js
 *
 * @file    depage-player.js
 *
 * adds a custom video player
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    };
    
    $.depage.flash = function(el, param1, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.flash", base);
        
        base.init = function(){
            if( typeof( param1 ) === "undefined" || param1 === null ) param1 = "default";
            
            base.param1 = param1;
            
            base.options = $.extend({}, $.depage.flash.defaultOptions, options);
            
            jQuery.extend(jQuery.browser, {
                flash: base.detect()
           });
        };
        
        // local reference to the flash version
        var version = false;
        
        // {{{ detect()
        /**
         * Detect
         * 
         * Determines if the flash version matches the required version.
         * 
         * @return Boolean - true if flash exceeds required version.
         */
        base.detect = function (){
            
            base.version();
            
            if (version) {
                var pv = version.match(/\d+/g);
                var rv = base.options.requiredVersion.match(/\d+/g);
                
                for (var i = 0; i < 3; i++) {
                    pv[i] = parseInt(pv[i] || 0);
                    rv[i] = parseInt(rv[i] || 0);
                    
                    if (pv[i] < rv[i]) {
                        return false;
                    }
                }
                return true;
            }
            
            return false;
        };
        /// }}}
        
        
        // {{{ version()
        /**
         * Version
         * 
         * Calculates the supported flash version in the browser.
         * 
         * @return string - flash version "0,0,0" - major, minor, revision.
         */
        base.version = function (){
            if ( !version ){
                try {
                    // get ActiveX Object for Internet Explorer
                    version = new ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable("$version")
                         .replace(/\D+/g, ",").match(/^,?(.+),?$/)[1];
                } catch(e) {
                    // check plugins for Firefox, Safari, Opera etc.
                    try {
                        if (navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin) {
                            version = (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]).description
                                .replace(/\D+/g, ",").match(/^,?(.+),?$/)[1];
                        }
                    } catch(e) {
                    }
                }
            }
            return version;
        };
        
        
        // {{{ build()
        /**
         * Build
         * 
         * Builds the HTML flash object
         * 
         * @param params - array of flash parameters supplied to the object
         * @return string flash object HTML
         */
        base.build = function(params) {
            var html1 = "";
            var html2 = "";
            var flashParam = [];
            
            for ( var p in params.params) {
                flashParam.push(p + "=" + encodeURI(params.params[p]));
            }
            
            var src = params.src;
            if (flashParam.length > 0) {
                src += "?" + flashParam.join("&amp;");
            }
            
            // object part
            html1 += "<object type=\"application/x-shockwave-flash\" ";
            html1 += "data=\"" + params.src + "?" + flashParam.join("&amp;") + "\" ";
            if (params.width !== undefined) {
                html1 += "width=\"" + params.width + "\" ";
            }
            if (params.height !== undefined) {
                html1 += "height=\"" + params.height + "\" ";
            }
            if (params.className !== undefined) {
                html1 += "class=\"" + params.className + "\" ";
            }
            if (params.id !== undefined) {
                html1 += "id=\"" + params.id + "\" ";
            }
            html1 += "allowFullScreen=\"true\" "; //allowScriptAccess=\"sameDomain\" ";
            
            // param part
            html2 += "<param name=\"movie\" value=\"" + params.src + "?" + flashParam.join("&amp;") + "\" />";
            html2 += "<param name=\"allowFullScreen\" value=\"true\" />";
            //html2 += "<param name=\"allowScriptAccess\" value=\"sameDomain\">";
            
            if (typeof(params.wmode) !== 'undefined') {
                html1 += 'mwmode="' + params.wmode + '"';
                html2 += '<param name="wmode" value="' + params.wmode +'" />';
            }
            
            html1 += ">";
            
            var value = $(html1 + html2 + "</object>");
            value.plainhtml = html1 + html2 + "</object>";
            
            return value;
        };
        // }}}
        
        // Run initializer
        base.init();
        
        return base;
    };
    
    $.depage.flash.defaultOptions = {
        requiredVersion: "0,0,0"
    };
    
    $.fn.depage_flash = function(param1, options){
        return this.each(function(){
            (new $.depage.flash(this, param1, options));
        });
    };
    
})(jQuery);