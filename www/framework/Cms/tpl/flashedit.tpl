<div <?php self::attr([
        "class" => "edit layout layout-left",
        "data-live-help" => _("Edit interface to edit your pages and the content of your currently selected page."),
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Tree/Edit")); ?></h1>
    </header>
    <iframe id="flashFrame" src="<?php self::e($this->flashUrl); ?>"></iframe>
</div>
<div <?php self::attr([
        "class" => "preview layout layout-right zoom100",
        "data-live-help" => _("The preview of the currently selected page."),
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Preview")); ?></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="previewFrame" src="<?php self::e($this->previewUrl); ?>"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
