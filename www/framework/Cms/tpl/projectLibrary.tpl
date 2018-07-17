<div <?php self::attr([
        "class" => "library",
    ]); ?>>
    <div class="trees">
        <header class="info info-tree-library">
            <h1><?php self::e(_("Library")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree library",
            'data-url' => "project/{$this->projectName}/tree/lib/",
            'data-live-help' => _("File tree:\\nHere you can add, rename and delete pages. Select a page to edit it in the content tree below ↓."),
            'data-live-help-class' => "icon icon-tree",
        ]); ?>>
            <?php self::e($this->tree); ?>
        </div>
    </div>
    <div class="files">
        <header class="info info-library">
            <h1><?php self::e(_("Files")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "file-list focus",
            'data-live-help' => _("Files:\\nHere you can edit all properties of the currently selected element."),
            'data-live-help-class' => "icon icon-files",
        ]); ?>>
            <?php self::e($this->files); ?>
        </div>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
