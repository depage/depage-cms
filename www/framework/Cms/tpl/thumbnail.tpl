<?php
    $imgSrc = str_replace("libref://", "projects/{$this->project->name}/lib/", $this->file);
    $ext = pathinfo($imgSrc, \PATHINFO_EXTENSION);
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'pdf'])) {
        $thumbSrc = $imgSrc . ".thumb-240x240.png";
    } else if (in_array($ext, ['svg'])) {
        $thumbSrc = $imgSrc;
    } else {
        // @todo add icons for other file types?
        $thumbSrc = "framework/Cms/images/icon-page.svg";
    }
    $sizeFormatter = new \Depage\Formatters\FileSize();
    $timeFormatter = new \Depage\Formatters\TimeAbsolute();
    $mediainfo = new \Depage\Media\MediaInfo();
    $info = $mediainfo->getInfo($imgSrc);
?>
<figure <?php self::attr([
    'class' => "thumb " . $this->class,
    'data-libref' => $this->file,
    'data-width' => $info['width'] ?? -1,
    'data-height' => $info['height'] ?? -1,
    'data-ext' => $ext,
]); ?>>
    <img src="<?php self::t($thumbSrc); ?>">
    <figcaption>
        <?php self::t($info['name']); ?>
        <div class="fileinfo">
            <p><?php self::t($info['name']); ?></p>
            <p><?php self::t(self::format_date($info['date'])); ?></p>
            <p><?php self::t($sizeFormatter->format($info['filesize'])); ?></p>
            <?php if (isset($info['width'])) { ?>
                <p><?php self::t($info['width'] . "x" . $info['height'] . " px"); ?></p>
            <?php } ?>
            <?php if (isset($info['copyright'])) { ?>
                <p><?php self::t($info['copyright']); ?></p>
            <?php } ?>
            <?php if (isset($info['description'])) { ?>
                <p><?php self::t($info['description']); ?></p>
            <?php } ?>
            <?php if (isset($info['duration'])) { ?>
                <p><?php self::t($timeFormatter->format($info['duration'])); ?></p>
            <?php } ?>
            <?php if (isset($info['tag_artist'])) { ?>
                <p><?php self::t($info['tag_artist']); ?></p>
            <?php } ?>
            <?php if (isset($info['tag_album'])) { ?>
                <p><?php self::t($info['tag_album']); ?></p>
            <?php } ?>
            <?php if (isset($info['tag_title'])) { ?>
                <p><?php self::t($info['tag_title']); ?></p>
            <?php } ?>
        </div>
    </figcaption>
</figure>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
