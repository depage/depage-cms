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
