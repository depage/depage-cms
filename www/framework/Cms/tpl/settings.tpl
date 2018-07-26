<div <?php self::attr([
        "class" => "edit layout layout-left",
    ]); ?>>
    <header class="info">
        <h1><?php self::e(_("Settings")); ?></h1>
    </header>
    <div class="scrollable-content">
        <?php self::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
