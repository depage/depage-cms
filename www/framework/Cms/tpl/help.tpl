<div <?php self::attr([
        'class' => "preview help layout layout-full zoom100",
        'data-live-help' => _("Inline help"),
        'data-live-help-class' => "icon icon-help",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Help")); ?></h1>
    </header>
    <div class="zoomwrapper">
        <iframe id="helpFrame" src="<?php self::t($this->url); ?>"></iframe>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
