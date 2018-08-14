<div <?php self::attr([
        "id" => $this->id,
        "class" => "$this->class",
        "data-ajax-update-url" => $this->updateUrl,
    ]); ?>>
    <div <?php self::attr([
        "class" => "content",
        "data-live-help" => $this->liveHelp,
    ]); ?>>
        <?php if ($this->title != null) { ?>
            <h1><?php self::t($this->title); ?></h1>
        <?php } ?>
        <?php self::e($this->content); ?>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
