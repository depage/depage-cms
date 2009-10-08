<?php
/**
 * @file    index.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
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

    require_once('framework/lib/lib_global.php');
    require_once('lib_auth.php');
    require_once('lib_html.php');
    require_once('lib_files.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
    require_once('lib_tasks.php');

    $project->user->auth_http();

    $html = new html()
?>
<html>
    <?php
        $html->head();
/*
<script language="JavaScript" type="text/javascript">
<!--
    function open_edit(userid) {
        h = 600;
        w = 770;
        x = screen.availWidth - 20 - w;
        y = screen.availHeight - 60 - h;
    
        options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=1,scrollbars=0,status=1,titlebar=0,toolbar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
        url = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "framework/interface/index.php?standalone=false&userid=" + userid;
        //alert("options: " + options);
        flashwin = open(url, "tt" + userid, options);
        if (!flashwin) {
            // @todo localisation
            alert("Sie müssen Popups für diese Seite zulassen, um depage::cms nutzen zu können.");
        } else {
            flashwin.opener = top;
        }
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
 */
        $html->preview_frame();
    ?>
</html>
