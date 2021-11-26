<div <?php self::attr([
        "class" => "layout colorschemes layout-tree layout-tree-full",
    ]); ?>>
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
<div <?php self::attr([
        "class" => "layout colorschemes layout-left",
    ]); ?>>
    <div class="colorscheme">
        <header class="info info-colors">
            <h1><?php self::e(_("Colors")); ?></h1>
        </header>
        <div <?php self::attr([
            'class' => "color-list focus scrollable-content",
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
            'class' => "color-property scrollable-content",
            'data-live-help' => _("Change you color here."),
            'data-live-help-class' => "icon icon-colors",
        ]); ?>>
        </div>
    </div>
</div>
<div <?php self::attr([
        'class' => "preview layout layout-right zoom100",
        'data-live-help' => _("The preview of the currently selected page."),
        'data-live-help-class' => "icon icon-preview",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Preview")); ?> <span class="title" data-tooltip=""></span></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="previewFrame"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
