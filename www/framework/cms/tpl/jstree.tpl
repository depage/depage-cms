<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>jsTree v.1.0 - full featured demo</title>

    <base href="<?php html::base(); ?>">

    <?php // TODO: use jquery 1.4.4
        $this->include_js("jquery", array(
        "../framework/shared/jquery-1.4.2.js",
        "../framework/shared/jquery.cookie.js",
        "../framework/shared/jquery.hotkeys.js",
    )); ?>
    <?php $this->include_js("jstree", array(
        "../framework/cms/js/jstree.js",
        "../framework/cms/js/jquery.jstree.js",
        "../framework/cms/js/jquery.jstree.plugins.js",
        "../framework/shared/jquery.json-2.2.js",
        "../framework/shared/jquery.gracefulWebSocket.js",
    )); ?>

</head>
<body>
<div id="container">

<!-- TODO: remove? 
<div id="mmenu" style="height:30px; overflow:hidden;">
<input type="button" id="add_folder" value="add folder" style="display:block; float:left;"/>
<input type="button" id="add_default" value="add file" style="display:block; float:left;"/>
<input type="button" id="rename" value="rename" style="display:block; float:left;"/>
<input type="button" id="remove" value="remove" style="display:block; float:left;"/>
<input type="button" id="cut" value="cut" style="display:block; float:left;"/>
<input type="button" id="copy" value="copy" style="display:block; float:left;"/>
<input type="button" id="paste" value="paste" style="display:block; float:left;"/>
</div>
-->

<div id="notification"></div>
<!-- the tree container (notice NOT an UL node) -->
<div id="demo" class="jstree-container"
    data-doc-id = "<?php echo $this->doc_id; ?>"
    data-seq-nr = "<?php echo $this->seq_nr; ?>"
    data-selected-nodes = ""
    data-open-nodes = ""
    data-theme = "../framework/cms/css/jstree.css"
    data-delta-updates-websocket-url = "ws://127.0.0.1:8000/jstree/"
    data-delta-updates-fallback-poll-url = "./fallback/updates/"
    data-delta-updates-post-url = "./"
>
    <?php echo $this->nodes; ?>
</div>

<!-- TODO: remove? 
<script type="text/javascript">
$(function () { 
	$("#mmenu input").click(function () {
		switch(this.id) {
			case "add_default":
			case "add_folder":
				$("#demo").jstree("create", null, "last", { "attr" : { "rel" : this.id.toString().replace("add_", "") } });
				break;
			default:
				$("#demo").jstree(this.id);
				break;
		}
	});
});
</script>
-->
</div>

</body>
</html>
