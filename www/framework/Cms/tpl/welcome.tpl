<div <?php self::attr([
    'class' => "scrollable-content {$this->class}",
]); ?>>
    <div class="box-welcome">
        <div class="content">
            <h1 class="size-XL"><?php self::t($this->title, true); ?></h1>
            <p class="size-L"><a href="<?php self::t($this->login_link); ?>" class="button"><?php self::t($this->login); ?></a></p>
        </div>
    </div>
    <?php self::e($this->content); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
