<?php
    $formatter = new \Depage\Formatters\DateNatural();
    $headerShown = !$this->lastPublishDate;
    $class = "";
?>
<table class="recent-changes">
    <?php foreach($this->pages as $page) {
        if (!$headerShown && $page->lastchange->getTimestamp() < $this->lastPublishDate->getTimestamp()) {
            $headerShown = true;
            $class = "published";
            ?>
                <tr>
                    <td class="lastchange" colspan="2">— <?php self::t(_("Last published") . " " . $formatter->format($this->lastPublishDate, true)); ?> —</td>
                </tr>
            <?php
        }
        // @todo add styling for published/unpublished pages
    ?>
        <tr>
            <td class="url <?php self::t($class); ?>"><a href="<?php self::t($this->previewPath . $page->url); ?>" class="preview" target="previewFrame"><?php self::t($page->url); ?></a></td>
            <td class="date <?php self::t($class); ?>"><?php self::t($formatter->format($page->lastchange, true)); ?></td>
        </tr>
    <?php } ?>
</table>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
