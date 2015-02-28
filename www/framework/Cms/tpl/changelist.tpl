<?php
    $formatter = new \Depage\Formatters\DateNatural();
?>
<table class="recent-changes">
    <?php foreach($this->pages as $page) {
    ?>
        <tr>
            <td class="url"><a href="<?php self::t($this->previewPath . $page->url); ?>" class="preview" target="previewFrame"><?php self::t($page->url); ?></a></td>
            <td class="date"><?php self::t($formatter->format($page->lastchange, true)); ?></td>
        </tr>
    <?php } ?>
</table>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
