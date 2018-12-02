<div
    id="<?php self::t($this->rootId); ?>"
    class="jstree-container scrollable-content"
    rel="<?php self::t($this->rootNodeType); ?>"
    data-projectname="<?php self::t($this->projectName); ?>"
    data-docname="<?php self::t($this->docName); ?>"
    data-node-id="<?php self::t($this->rootId); ?>"
    data-doc-id="<?php self::t($this->docId); ?>"
    data-seq-nr="<?php self::t($this->seqNr); ?>"
    data-selected-nodes="<?php self::t($this->selected ? $this->selected : ""); ?>"
    data-open-nodes=""
    data-tree-url="<?php self::a($this->treeUrl, "auto"); ?>"
    data-delta-updates-websocket-url="<?php self::t($this->wsUrl); ?>"
    data-delta-updates-fallback-url="<?php self::a($this->treeUrl . "fallback/updates/", "auto"); ?>"
    data-delta-updates-post-url="<?php self::a($this->treeUrl, "auto"); ?>"
    data-tree-settings="<?php self::t(json_encode($this->settings)); ?>"
    data-previewlang="<?php self::t($this->previewLang); ?>"
>
    <?php self::e($this->nodes); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
