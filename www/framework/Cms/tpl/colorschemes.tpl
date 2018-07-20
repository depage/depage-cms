<div <?php self::attr([
        "class" => "colors",
    ]); ?>>
    <div class="trees">
        <header class="info info-tree-colors">
            <h1><?php self::e(_("Color Schemes")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "tree colors",
            'data-url' => "project/{$this->projectName}/tree/colors/",
            'data-live-help' => _("A list of all available color schemes"),
            'data-live-help-class' => "icon icon-tree",
        ]); ?>>
            <?php self::e($this->tree); ?>
        </div>
    </div>
    <div class="colors">
        <header class="info info-colors">
            <h1><?php self::e(_("Colors")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "color-list focus doc-properties",
            'data-live-help' => _("Edit you colors here."),
            'data-live-help-class' => "icon icon-colors",
        ]); ?>>
        </div>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
