<div <?php self::attr([
        "class" => "edit layout layout-left",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Tree/Edit")); ?></h1>
    </header>
    <div class="trees">
        <div <?php self::attr([
            'class' => "tree pages",
            'data-url' => "project/{$this->projectName}/tree/pages/",
        ]); ?>>
        </div>
        <div <?php self::attr([
            'class' => "tree pagedata",
        ]); ?>>
        </div>
    </div>
    <div <?php self::attr([
        'class' => "edit-properties",
    ]); ?>>
    </div>
</div>
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
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
