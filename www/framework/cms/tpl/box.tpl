<div id="box_<?php html::t($this->id); ?>" class="centered_box">
    <div class="content">
        <?php if (isset($this->icon)) { ?>
            <div class="icon">
                <img src="<?php html::t($icon); ?>">
            </div>
        <?php } ?>

        <h1><?php html::t($this->title); ?></h1>
        <?php html::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
