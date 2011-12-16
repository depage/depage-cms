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
    <div
        id="node_<?php echo $this->root_id; ?>"
        class="jstree-container"
        data-doc-id = "<?php echo $this->doc_id; ?>"
        data-seq-nr = "<?php echo $this->seq_nr; ?>"
        data-selected-nodes = ""
        data-open-nodes = ""
        data-theme = "framework/cms/css/jstree.css"
        data-delta-updates-websocket-url = ""
        data-delta-updates-fallback-poll-url = "jstree/fallback/updates/"
        data-delta-updates-post-url = "jstree/"
        data-types-settings-url = "jstree/types_settings/"
        data-add-marker-special-children = "folder separator"
    >
        <!--data-delta-updates-websocket-url = "ws://127.0.0.1:8000/jstree/"-->
        <?php echo $this->nodes; ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
