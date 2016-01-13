<div <?php self::attr([
        "class" => "edit layout layout-left",
        //"data-live-help" => _("Edit interface to edit your pages and the content of your currently selected page."),
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Tree/Edit")); ?></h1>
    </header>
    <iframe id="flashFrame" src="<?php self::e($this->flashUrl); ?>"></iframe>
    <div class="live-help-mock layout-edit-pages">
        <div <?php self::attr([
            "class" => "mock tree-pages",
            "data-live-help" => _("Page tree:\\nHere you can add, rename and delete pages. Select a page to edit it in the content tree below ↓."),
            "data-live-help-class" => "icon-tree",
        ]); ?>></div>
        <div <?php self::attr([
            "class" => "mock tree-page",
            "data-live-help" => _("Content tree:\\nHere you can add content to your pages. Select an element to edit its properties in the pane on the right →."),
            "data-live-help-class" => "icon-tree",
        ]); ?>></div>
        <div <?php self::attr([
            "class" => "mock content-properties",
            "data-live-help" => _("Content properties:\\nHere you can edit all properties of the currently selected element."),
        ]); ?>></div>
    </div>
    <div class="live-help-mock layout-files">
        <div <?php self::attr([
            "class" => "mock tree-pages",
            "data-live-help" => _("File library:\\n"),
            "data-live-help-class" => "icon-tree",
        ]); ?>></div>
    </div>
    <div class="live-help-mock layout-colors">
        <div <?php self::attr([
            "class" => "mock tree-pages",
            "data-live-help" => _("Colors:\\n"),
            "data-live-help-class" => "icon-tree",
        ]); ?>></div>
    </div>
    <div <?php self::attr([
        "class" => "mock preview-button",
        "data-live-help" => _("Preview your current edit in the preview pane on the right →."),
    ]); ?>></div>
</div>
<div <?php self::attr([
        "class" => "preview layout layout-right zoom100",
        "data-live-help" => _("The preview of the currently selected page."),
        "data-live-help-class" => "icon-preview",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Preview")); ?></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="previewFrame" src="<?php self::e($this->previewUrl); ?>"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
