<div <?php self::attr([
        "class" => "edit layout layout-left",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Publish")); ?></h1>
    </header>
    <?php self::e($this->content); ?>
</div>
<?php if (!empty($this->previewUrl)) { ?>
    <div <?php self::attr([
            "class" => "preview layout layout-right zoom100",
            "data-live-help" => _("The preview of the currently selected page."),
            "data-live-help-class" => "icon icon-preview",
        ]); ?>>
        <header class="info">
            <h1><?php self::e(_("Preview")); ?></h1>
        </header>
        <div class="zoomwrapper">
            <iframe id="previewFrame" src="<?php self::e($this->previewUrl); ?>"></iframe>
        </div>
    </div>
<?php } ?>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
