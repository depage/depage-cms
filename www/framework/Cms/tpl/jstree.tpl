<div
    id="node_<?php self::t($this->root_id); ?>"
    class="jstree-container"
    data-projectname="<?php self::t($this->projectName); ?>"
    data-docname="<?php self::t($this->docName); ?>"
    data-doc-id="<?php self::t($this->docId); ?>"
    data-node-id="<?php self::t($this->rootId); ?>"

    data-seq-nr="<?php self::t($this->seqNr); ?>"
    data-selected-nodes=""
    data-open-nodes=""
    data-tree-url="<?php self::a($this->treeUrl, "auto"); ?>"
    data-delta-updates-websocket-url=""
    data-delta-updates-fallback-poll-url="<?php self::a($this->treeUrl . "fallback/updates/", "auto"); ?>"
    data-delta-updates-post-url="<?php self::a($this->treeUrl, "auto"); ?>"
    data-types-settings-url="<?php self::a($this->treeUrl . "types-settings/", "auto"); ?>"
>
    <!--data-delta-updates-websocket-url="ws://127.0.0.1:8000/jstree/"-->
    <?php self::e($this->nodes); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
