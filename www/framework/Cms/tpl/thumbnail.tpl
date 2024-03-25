<?php
    $fallbackThumb = "framework/Cms/images/icon-page.svg";
    $class = $this->class;
    $imgSrc = "projects/{$this->project->name}/lib/{$this->file->fullname}";
    $thumbSrc = $fallbackThumb;
    $thumbSrc2 = null;

    if (in_array($this->file->ext, ['png', 'jpg', 'jpeg', 'gif', 'pdf'])) {
        $thumbSrc = $imgSrc . ".t320x320.png";
        $thumbSrc2 = $imgSrc . ".t320x320.webp";
    } else if (in_array($this->file->ext, ['svg'])) {
        $thumbSrc = $imgSrc;
    }
    $sizeFormatter = new \Depage\Formatters\FileSize();
    $timeFormatter = new \Depage\Formatters\TimeAbsolute();
    $lastPublishDate = $this->project->getLastPublishDateOf("lib/" . $this->file->fullname);

    $target = $this->project->getDefaultTargetUrl() . "/lib/";

    if ($lastPublishDate) {
        $class .= " published";
    } else {
        $class .= " not-published";
    }
?>
<figure <?php self::attr([
    'class' => "thumb " . $class,
    'data-libref' => $this->file->libref,
    'data-libid' => $this->file->libid,
    'data-url' => $target . $this->file->fullname,
    'data-width' => $this->file->width ?? -1,
    'data-height' => $this->file->height ?? -1,
    'data-center-x' => $this->file->centerX,
    'data-center-y' => $this->file->centerY,
    'data-ext' => $this->file->ext,
    'data-fallbackthumb' => $fallbackThumb,
]); ?>>
    <picture>
        <?php if ($thumbSrc2) { ?>
            <source srcset="<?php self::t($thumbSrc2); ?>" type="image/webp" />
        <?php } ?>

        <img src="<?php self::t($thumbSrc); ?>" loading="lazy">
    </picture>
    <figcaption>
        <?php self::t($this->file->filename); ?>
        <div class="fileinfo">
            <p><?php self::t($this->file->filename); ?></p>
            <p><?php self::t($sizeFormatter->format($this->file->filesize)); ?></p>
            <?php if (isset($this->file->width)) { ?>
                <p><?php self::t($this->file->width . " × " . $this->file->height); ?></p>
            <?php } ?>
            <p class="change-date"><?php self::t(_("Changed: ") . self::formatDate($this->file->lastmod)); ?></p>
            <p class="folder"><?php self::t(_("Folder: ") . '/' . dirname($this->file->fullname) . '/'); ?></p>
            <?php if ($lastPublishDate) { ?>
                <p class="publishing-status"><?php self::t(_("Published: ") . self::formatDate($lastPublishDate)); ?></p>
            <?php } else { ?>
                <p class="publishing-status"><?php self::t(_("File has not been published yet.\nPublish your project to make the file available online."), true); ?></p>
            <?php } ?>
            <?php if (isset($this->file->copyright)) { ?>
                <p><?php self::t($this->file->copyright); ?></p>
            <?php } ?>
            <?php if (isset($this->file->description)) { ?>
                <p><?php self::t($this->file->description); ?></p>
            <?php } ?>
            <?php if (isset($this->file->duration)) { ?>
                <p><?php self::t($timeFormatter->format($this->file->duration)); ?></p>
            <?php } ?>
            <?php if (isset($this->file->artist)) { ?>
                <p><?php self::t($this->file->artist); ?></p>
            <?php } ?>
            <?php if (isset($this->file->album)) { ?>
                <p><?php self::t($this->file->album); ?></p>
            <?php } ?>
            <?php if (isset($this->file->title)) { ?>
                <p><?php self::t($this->file->title); ?></p>
            <?php } ?>
        </div>
    </figcaption>
    <?php if ($this->file->libid) { ?>
        <a <?php self::attr([
            'class' => "choose-image-center-button",
            'title' => _("Choose image center"),
        ]); ?>></a>
    <?php } ?>
</figure>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
