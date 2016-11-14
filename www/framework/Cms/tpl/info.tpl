<?php if (!empty($this->title) || !empty($this->content)) { ?>
    <div class="info">
        <?php if (!empty($this->title)) { ?>
            <h1><?php self::t($this->title); ?></h1>
        <?php } ?>
        <?php if (!empty($this->content)) { ?>
            <p><?php self::t($this->content, true); ?></p>
        <?php } ?>
    </div>
<?php } ?>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
