<div <?php self::attr([
        "class" => "library layout layout-tree layout-tree-full",
    ]); ?>>
    <header class="info info-tree-library">
        <h1><?php self::e(_("Library")); ?></h1>
    </header>
    <div class="search active"><a class="open-search"><?php self::t(_("File Search")); ?></a></div>
    <div <?php self::attr([
        'class' => "tree library",
        'data-url' => "project/{$this->projectName}/tree/lib/",
        'data-live-help' => _("File tree:\\nHere you can add, rename and delete folders. Select a folder to show its contents."),
        'data-live-help-class' => "icon icon-tree",
    ]); ?>>
        <?php self::e($this->tree); ?>
    </div>
</div>
<div <?php self::attr([
        "class" => "library layout layout-full",
    ]); ?>>
    <div class="files">
        <header class="info info-library">
            <h1><?php self::e(_("Files")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "file-list focus scrollable-content",
            'data-live-help' => _("Files:\\nHere you can manage the files of the current folder."),
            'data-live-help-class' => "icon icon-files",
        ]); ?>>
            <?php self::e($this->files); ?>
        </div>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
