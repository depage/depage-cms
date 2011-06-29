/**
 * @file    depage-base.js
 *
 *
 * copyright (c) 2009-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
(function( $ ){
    $.depage = $.depage || { fnMethods: [] };

    $.fn.depage = $.fn.depage || function(method) {
        if ( $.depage.fnMethods[method] ) {
            return $.depage.fnMethods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return $.depage.fnMethods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
        }  
    }
})( jQuery );

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
