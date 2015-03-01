<div class="edit layout layout-left">
    <header class="info">
        <h1><?php self::e(_("Tree/Edit")); ?></h1>
    </header>
    <iframe id="flashFrame" src="<?php self::e($this->flashUrl); ?>"></iframe>
</div>
<div class="preview layout layout-right zoom100">
    <header class="info">
        <h1><?php self::e(_("Preview")); ?></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="previewFrame" src="<?php self::e($this->previewUrl); ?>"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
