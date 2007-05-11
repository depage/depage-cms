<?php
/**
 * @file    index.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
 
/**
 * @mainpage
 *
 * Welcome to unepexted experiences!
 *
 * @li @ref todo "Todo List"
 * @li @ref bug "Bug List"
 */
 
define('IS_IN_CONTOOL', true);

require_once('scripts/lib/lib_global.php');
require_once('lib_html.php');

?>
<html>
<head>
<meta http-equiv=Content-Type content="text/html;  charset=ISO-8859-1">
<title><?php echo($conf->app_name . ' ' . $conf->app_version); ?></title>
<script language="JavaScript" type="text/javascript">
<!--
    function open_edit(userid) {
        h = 400;
        w = 770;
        x = screen.availWidth - 20 - w;
        y = screen.availHeight - 60 - h;
    
        options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=1,scrollbars=0,status=1,titlebar=0,toolbar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
        url = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "interface/index.php?standalone=false&userid=" + userid;
        flashwin = open(url, "tt" + userid, options);
        flashwin.opener = top;
    }
    
    function close_edit() {
        if (opener) {
            opener.location.href = ".";
            opener.focus();
        } else if (flashwin != null) {
            flashwin.close();
        }
    }
    
    function set_title(newtitle) {
        if (document.title != newtitle) {
            document.title = newtitle;
        }
    }
                
    function preview(newURL) {
        content.location.href = unescape(newURL);
        setTimeout("set_preview_title()", 2000);
    }
    
    function set_preview_title() {
        if (content.document.title) {
            if (content.document.title != "") {
                set_title("<?php echo($conf->app_name); ?> preview - [" + content.document.title + "]");
            } else {
                set_title("<?php echo($conf->app_name); ?> preview");
            }
        }
        setTimeout("set_preview_title()", 2000);
    }
    
    flashwin = null;
//-->    
</script>

</head>

<frameset rows="100%,*" frameborder="0" border="0"  framespacing="0" onUnload="close_edit()">
    <frame id="contentFrame" name="content" src="interface/index.php?standalone=false" scrolling="auto" noresize frameborder="0" border="0"  framespacing="0">
    <!--frame name="nothing" src="nothing.html" scrolling="no" noresize frameborder="0" border="0" framespacing="0"-->
</frameset>
</html>
