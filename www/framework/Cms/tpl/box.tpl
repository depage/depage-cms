<div <?php self::attr("id", "$this->id"); self::attr("class", "centered_box $this->class"); ?>>
    <div class="content">
        <?php if ($this->title != null) { ?>
            <h1><?php self::t($this->title); ?></h1>
        <?php } ?>
        <?php self::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
