<?php
    $fallbackThumb = "framework/Cms/images/icon-page.svg";
    $class = $this->class;
    $file = str_replace("libref://", "", $this->file);
    $imgSrc = "projects/{$this->project->name}/lib/{$file}";
    $ext = pathinfo($imgSrc, \PATHINFO_EXTENSION);
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'pdf'])) {
        $thumbSrc = $imgSrc . ".thumb-240x240.png";
    } else if (in_array($ext, ['svg'])) {
        $thumbSrc = $imgSrc;
    } else {
        // @todo add icons for other file types?
        $thumbSrc = $fallbackThumb;
    }
    $sizeFormatter = new \Depage\Formatters\FileSize();
    $timeFormatter = new \Depage\Formatters\TimeAbsolute();
    $mediainfo = new \Depage\Media\MediaInfo();
    $info = $mediainfo->getInfo($imgSrc);
    $lastPublishDate = $this->project->getLastPublishDateOf("lib/" . $file);

    if ($lastPublishDate) {
        $class .= " published";
    } else {
        $class .= " not-published";
    }
?>
<figure <?php self::attr([
    'class' => "thumb " . $class,
    'data-libref' => $this->file,
    'data-width' => $info['width'] ?? -1,
    'data-height' => $info['height'] ?? -1,
    'data-ext' => $ext,
    'data-fallbackthumb' => $fallbackThumb,
]); ?>>
    <img src="<?php self::t($thumbSrc); ?>">
    <figcaption>
        <?php self::t($info['name']); ?>
        <div class="fileinfo">
            <p><?php self::t($info['name']); ?></p>
            <p><?php self::t($sizeFormatter->format($info['filesize'])); ?></p>
            <?php if (isset($info['width'])) { ?>
                <p><?php self::t($info['width'] . " × " . $info['height']); ?></p>
            <?php } ?>
            <p class="change-date"><?php self::t(_("Changed: ") . self::format_date($info['date'])); ?></p>
            <?php if ($lastPublishDate) { ?>
                <p class="publishing-status"><?php self::t(_("Published: ") . self::format_date($lastPublishDate)); ?></p>
            <?php } else { ?>
                <p class="publishing-status"><?php self::t(_("File has not been published yet.\nPublish your project to make the file available online."), true); ?></p>
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
