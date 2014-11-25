<div id="box_<?php self::t($this->id); ?>" class="centered_box <?php self::t($this->class); ?>">
    <div class="content">
        <?php if ($this->icon != null) { ?>
            <div class="icon">
                <img src="<?php self::t($this->icon); ?>">
            </div>
        <?php } ?>

        <?php if ($this->title != null) { ?>
            <h1><?php self::t($this->title); ?></h1>
        <?php } ?>
        <?php self::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
