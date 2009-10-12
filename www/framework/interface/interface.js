/* {{{ open_edit */
function open_edit(project, page) {
    h = 600;
    w = 770;
    x = screen.availWidth - 20 - w;
    y = screen.availHeight - 60 - h;

    options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=1,scrollbars=0,status=1,titlebar=0,toolbar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
    url = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "framework/interface/interface.php?standalone=false&project_name=" + project + "&page=" + escape(page);
    flashwin = open(url, "tt" + project, options);
    if (!flashwin) {
        // @todo localisation
        alert("Sie müssen Popups für diese Seite zulassen, um depage::cms nutzen zu können.");
    } else {
        flashwin.opener = top;
    }
}
/* }}} */
/* {{{ close_edit */
function close_edit() {
    if (opener) {
        opener.location.href = ".";
        opener.focus();
    } else if (flashwin != null) {
        flashwin.close();
    }
}
/* }}} */
/* {{{ set_title */
function set_title(newtitle) {
    if (opener) {
        opener.set_title(newtitle);
    } else {
        if (document.title != newtitle) {
            document.title = newtitle;
        }
    }
}
/* }}} */
/* {{{ set_status */
function set_status(message) {
    window.status = unescape(message);
}
/* }}} */

/* {{{ preview */
function preview(newURL) {
    if (opener) {
        opener.preview(newURL);
    } else {
        content.location.href = unescape(newURL);
        setTimeout("set_preview_title()", 2000);
    }
}
/* }}} */
/* {{{ set_preview_title */
function set_preview_title() {
    var type = get_preview_pagetype();
    if (type == "preview" && content.document.title) {
        set_title(basetitle + " - [" + content.document.title + "]");
    } else {
        set_title(basetitle);
    }

    toolbarFrame.set_toolbar(type);
}
/* }}} */
/* {{{ get_preview_pagetype */
function get_preview_pagetype() {
    try {
        var url = window.content.document.location.toString();
    } catch(e) {
        var url = "";
    }

    if (url.match(/\/preview\//)) {
        return "preview";
    } else {
        return "home";
    }
}
/* }}} */
/* {{{ set_toolbar */
function set_toolbar(type) {
    if (type == "preview") {
        $("#button_reload, #button_edit").show();
    } else if (type == "home") {
        $("#button_reload, #button_edit").hide();
    }

}
/* }}} */

/* {{{ dlg_publish */
function dlg_publish(project) {
    var html = "";

    html += "<div class=\"dlg\">";
        html += "<span><a class=\"question\"></a></span>";
        html += "Do you want to publish '" + project + "' now?";
        html += "<span></span>";
        html += "<span><a class=\"yes\" href=\"#\"></a></span>";
        html += "<span><a class=\"no\" href=\"#\"></a></span>";
    html += "</div>";

    var dlg = $(html).appendTo("body");

    $(".yes", dlg).click(function() {
        dlg.remove();

        top.publish(project);
    });
    $(".no", dlg).click(function() {
        dlg.remove();
    });
    
}
/* }}} */

/* {{{ msg */
function msg(newmsg) {
    newmsg = unescape(newmsg);
    newmsg = newmsg.replace(/<br>/g, "\n");
    newmsg = newmsg.replace(/&apos;/g, "'");
    newmsg = newmsg.replace(/&quot;/g, "\"");
    newmsg = newmsg.replace(/&auml;/g, "ä");
    newmsg = newmsg.replace(/&Auml;/g, "Ä");
    newmsg = newmsg.replace(/&ouml;/g, "ö");
    newmsg = newmsg.replace(/&Ouml;/g, "Ö");
    newmsg = newmsg.replace(/&uuml;/g, "ü");
    newmsg = newmsg.replace(/&Uuml;/g, "Ü");
    newmsg = newmsg.replace(/&szlig;/g, "ß");
    alert(newmsg);
}
/* }}} */
/* {{{ load_flasherror */
function load_flasherror() {
    if (flashloaded == false) {
        //window.location="msg.php?msg=inhtml_needed_flash&title=inhtml_require_title";
    }	
}
/* }}} */
/* {{{ set_flashloaded */
function set_flashloaded() {
    flashloaded = true;
}
/* }}} */

/* {{{ go_home */
function go_home() {
    window.content.location = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "framework/interface/home.php";

    if (flashwin) {
        flashwin.close();
    }
    flashwin = null;
}
/* }}} */
/* {{{ open_home */
function open_home() {
    try {
        opener.go_home();
        //top.opener.go_home();
    } catch (e) {
    }
}
/* }}} */
/* {{{ open_upload */
function open_upload(sid, wid, path) {
    var h = 400;
    var w = 360;
    var x = (screen.availWidth - w) / 2;
    var y = (screen.availHeight - h) / 2;

    var options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=0,scrollbars=0,status=0,titlebar=0,toolbar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
    var url = "upload.php?sid=" + sid + "&wid=" + wid + "&path=" + path;

    uploadwin = open(url, "tt_upload" + sid, options);
    uploadwin.opener = window;
}
/* }}} */
			
/* {{{ edit_page */
function edit_page(page) {
    if (opener) {
        try {
            $("#flash")[0].SetVariable("/:gotopage",page);
            $("#flash")[0].Play();
        } catch(e) {
            document.embeds[0].SetVariable("/:gotopage",page);
            document.embeds[0].Play();
        }
    } else {
        var url = content.document.location.toString();
        var matches = url.match(/projects\/([^\/]*)\/preview\/[^\/]*\/[^\/]*\/[^\/]*(\/.*)/);

        var project = matches[1];
        var page = matches[2];

        if (flashwin) {
            flashwin.edit_page(page);
            //flashwin.interface.edit_page(page);
            flashwin.focus();
        } else {
            open_edit(project, page);
        }
    }
}
/* }}} */
/* {{{ logout */
function logout() {
    window.location = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "?logout=true";
    //content.location = document.location.protocol + "//" + document.location.host + document.location.pathname.replace(/index\.php/, "") + "?logout=true";
}
/* }}} */
/* {{{ publish */
function publish(project) {
    $("<div></div>").load("framework/interface/status.php", {
        type: "publish",
        project: project
    }, function() {});
}
/* }}} */

/* {{{ update_tasklist */
function update_tasklist() {
    var tasks = $("#tasks");

    if (tasks.length == 1) {
        tasks.load("status.php?type=tasks", null, function() {
            setTimeout("update_tasklist()", 1500);
        });
    }
}
/* }}} */

/* {{{ add_file_chooser */
function add_file_chooser() {
    if (document.getElementById("file" + chooserNum).value != "" && chooserNum < 9) {
        chooserNum++;
        document.getElementById("chooser" + chooserNum).style.visibility = "visible";
    }
    setTimeout("add_file_chooser()", 200);
}
/* }}} */
/* {{{ add_first_chooser */
function add_first_chooser() {
    add_file_chooser();
}
/* }}} */

chooserNum = 1;

flashwin = null;
flashloaded = false;

$(document).ready(function() {
    basetitle = document.title;
    update_tasklist();

    $("a").click( function() {
        this.blur();

        return true;
    });
});

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
