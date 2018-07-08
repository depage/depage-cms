<?php
    $imgSrc = str_replace("libref://", "projects/{$this->project->name}/lib/", $this->file);
    $ext = pathinfo($imgSrc, \PATHINFO_EXTENSION);
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'pdf'])) {
        $thumbSrc = $imgSrc . ".thumb-120x120.png";
    } else if (in_array($ext, ['svg'])) {
        $thumbSrc = $imgSrc;
    } else {
        // @todo add icons for other file types?
        $thumbSrc = "framework/Cms/images/icon-page.svg";
    }
    $formatter = new \Depage\Formatters\FileSize();
    $mediainfo = new \Depage\Media\MediaInfo();
    $info = $mediainfo->getInfo($imgSrc);
?>
<figure class="thumb" <?php self::attr([
    'class' => "thumb",
    'data-libref' => $this->file,
]); ?>>
    <img src="<?php self::t($thumbSrc); ?>">
    <figcaption>
        <?php self::t($info['name']); ?>
        <div class="fileinfo">
            <p><?php self::t($info['name']); ?></p>
            <p><?php self::t(self::format_date($info['date'])); ?></p>
            <p><?php self::t($formatter->format($info['filesize'])); ?></p>
            <p><?php self::t($info['width'] . "x" . $info['height'] . " px"); ?></p>
            <p><?php self::t($info['copyright']); ?></p>
            <p><?php self::t($info['description']); ?></p>
        </div>
    </figcaption>
</figure>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
