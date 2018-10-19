<div <?php self::attr([
    'class' => "scrollable-content top",
]); ?>>
    <div class="box-welcome">
        <div class="content">
            <h1 class="size-XL"><?php self::t(_("Welcome to depage-cms"), true); ?></h1>
            <?php self::e($this->loginForm); ?>
        </div>
    </div>
    <?php self::e($this->content); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
