<div <?php self::attr([
        "class" => "edit layout layout-left",
    ]); ?>>
    <div class="trees">
        <header class="info info-tree-pages">
            <h1><?php self::e(_("Pages")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree pages",
            'data-url' => "project/{$this->projectName}/tree/pages/",
        ]); ?>>
        </div>
        <header class="info info-tree-pagedata">
            <h1><?php self::e(_("Document")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree pagedata",
        ]); ?>>
        </div>
    </div>
    <header class="info info-doc-properties">
        <h1><?php self::e(_("Document Properties")); ?></h1>
    </header>
    <div <?php self::attr([
        'class' => "doc-properties",
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
