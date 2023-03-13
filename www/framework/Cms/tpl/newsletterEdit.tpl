<div <?php self::attr([
        "class" => "edit layout layout-tree layout-tree-full",
    ]); ?>>
        <header class="info info-tree-pagedata">
            <h1><?php self::e(_("Newsletter")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree pagedata newsletter",
            'data-docref' => $this->newsletterName,
            'data-live-help' => _("Page tree:\\nHere you can add, rename and delete pages. Select a page to edit it in the content tree below ↓."),
            'data-live-help-class' => "icon icon-tree",
        ]); ?>>
        </div>
</div>
<div <?php self::attr([
        'class' => "edit layout layout-left",
        'data-live-help' => _("Edit your newsletter and choose which items to include"),
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Newsletter")); ?></h1>
    </header>
    <div class="doc-properties scrollable-content">
        <?php self::e($this->content); ?>
    </div>
</div>
<div <?php self::attr([
        "class" => "preview layout layout-right zoom100",
        "data-live-help" => _("The preview of the current newsletter."),
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
