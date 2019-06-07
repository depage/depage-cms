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
            'data-selected-nodes' => $this->pageId,
            'data-live-help' => _("Page tree:\\nHere you can add, rename and delete pages. Select a page to edit it in the content tree below ↓."),
            'data-live-help-class' => "icon icon-tree",
        ]); ?>>
        </div>
        <header class="info info-tree-pagedata">
            <h1><?php self::e(_("Document")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree pagedata",
            'data-live-help' => _("Document tree:\\nHere you can add content to your pages. Select an element to edit its properties in the pane on the right →."),
            'data-live-help-class' => "icon icon-tree",
        ]); ?>>
        </div>
    </div>
    <header class="info info-doc-properties">
        <h1><?php self::e(_("Document Properties")); ?></h1>
    </header>
    <div <?php self::attr([
        'class' => "doc-properties scrollable-content",
        'data-live-help' => _("Document properties:\\nHere you can edit all properties of the currently selected element."),
        'data-live-help-class' => "icon icon-properties",
    ]); ?>>
    </div>
</div>
<div <?php self::attr([
        'class' => "preview layout layout-right zoom100",
        'data-live-help' => _("The preview of the currently selected page."),
        'data-live-help-class' => "icon icon-preview",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Preview")); ?> <span class="title"></span></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="previewFrame"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
