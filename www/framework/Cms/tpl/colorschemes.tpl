<div <?php self::attr([
        "class" => "colorschemes",
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
    <div class="colorscheme">
        <header class="info info-colors">
            <h1><?php self::e(_("Colors")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "color-list focus",
            'data-live-help' => _("Available colors"),
            'data-live-help-class' => "icon icon-colors",
        ]); ?>>
        </div>
    </div>
    <div class="colorprops">
        <header class="info info-color-properties">
            <h1><?php self::e(_("Color Properties")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "color-property",
            'data-live-help' => _("Change you color here."),
            'data-live-help-class' => "icon icon-colors",
        ]); ?>>
        </div>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
