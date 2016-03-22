<?php if (!empty($this->title) && !empty($this->content)) { ?>
    <div class="info">
        <?php if ($this->title != null) { ?>
            <h1><?php self::t($this->title); ?></h1>
        <?php } ?>
        <p><?php self::t($this->content, true); ?></p>
    </div>
<?php } ?>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
