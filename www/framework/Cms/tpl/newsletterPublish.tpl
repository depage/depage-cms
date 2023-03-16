<div <?php self::attr([
        'class' => "edit layout layout-left",
        'data-live-help' => _("Edit your newsletter and choose which items to include"),
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Publish Newsletter")); ?></h1>
    </header>
    <?php self::e($this->tabs); ?>
    <div class="dialog-full scrollable-content">
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
