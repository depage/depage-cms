<div id="box_<?php html::t($this->id); ?>" class="centered_box <?php html::t($this->class); ?>">
    <div class="content">
        <?php if ($this->icon != null) { ?>
            <div class="icon">
                <img src="<?php html::t($this->icon); ?>">
            </div>
        <?php } ?>

        <?php if ($this->title != null) { ?>
            <h1><?php html::t($this->title); ?></h1>
        <?php } ?>
        <?php html::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
