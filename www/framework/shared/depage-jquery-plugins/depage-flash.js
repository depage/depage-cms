(function( $ ){
$.extend($.browser, {
    // {{{ jquery.browser.flash
    /**
     * @function jquery.browser.flash()
     *
     * checks browser for flash support
     *
     * @param neededVersion     version-number of flash to test for
     *
     * @return                  true, when version of flash is supported, false if not
     */
    flash: (function (neededVersion) {
        var found = false;
	var version = "0,0,0";

	try {
	    // get ActiveX Object for Internet Explorer
	    version = new ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable('$version').replace(/\D+/g, ',').match(/^,?(.+),?$/)[1];
	} catch(e) {
	    // check plugins for Firefox, Safari, Opera etc.
	    try {
		if (navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin) {
		    version = (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]).description.replace(/\D+/g, ",").match(/^,?(.+),?$/)[1];
		}
	    } catch(e) {
		return false;
	    }		
	}

	var pv = version.match(/\d+/g);
	var rv = neededVersion.match(/\d+/g);

	for (var i = 0; i < 3; i++) {
	    pv[i] = parseInt(pv[i] || 0);
	    rv[i] = parseInt(rv[i] || 0);

	    if (pv[i] < rv[i]) {
		// player is less than required
	       	return false;
	    } else if (pv[i] > rv[i]) {
		// player is greater than required
		return true;
	    }
	}
	// major version, minor version and revision match exactly
	return true;
    })
    // }}}
});
$.fn.extend($, {
    // {{{ jquery.flash
    /**
     * @function jquery.flash()
     *
     * adds a flash object to dom
     *
     * @param param     paramater for flash object
     *
     * @return          html-code to add to dom
     */
    flash: (function(param) {
        var html1 = "";
        var html2 = "";
        var flashParam = [];

        for (var p in params.params) {
            flashParam.push(p + "=" + encodeURI(params.params[p]));
        }

        //object part
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

        //param part
        html2 += "<param name=\"movie\" value=\"" + params.src + "?" + flashParam.join("&amp;") + "\" />";

        if (params.transparent === true) {
            html1 += "mwmode=\"transparent\"";
            html2 += "<param name=\"wmode\" value=\"transparent\" />";
        }
        html1 += ">";

        return $(html1 + html2 + "</object>");
    })
    // }}}
});
})( jQuery );

// {{{ replaceFlashContent()
function replaceFlashContent() {
    $("img.flash_repl").each(function() {
	var parent = $(this).parent().prepend( 
	    $().flash({
		src:		this.src.replace(/\.jpg|\.gif|\.png/, ".swf").replace(/\&/, "&amp;"),
		width:		this.width,
		height:		this.height,
		className:	"flash",
		id:		this.id ? this.id + "_flash" : null,
		transparent:    $(this).hasClass("trans")
	    }) 
	);
	if (parent[0].nodeName == "A") {
	    // deactivate link for surrounding a-node in safari
	    parent[0].href = "javascript:return false;";
	}
    });
}
// }}}

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
