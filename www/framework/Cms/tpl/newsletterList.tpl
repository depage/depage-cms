<?php
    $formatter = new \Depage\Formatters\DateNatural();
    $headerShown = 0;
    $class = "";
?>
<table class="recent-changes newsletter">
    <?php foreach($this->newsletters as $newsletter) {
        $newsletterUrl = DEPAGE_BASE . "project/" . $newsletter->project->name . "/newsletter/" . $newsletter->name . "/";

        if ($headerShown == 0) {
            $headerShown++;
            if ($newsletter->released === false) {
                ?>
                    <tr>
                        <td class="lastchange" colspan="2">— <?php self::t(_("Unreleased Newsletters")); ?> —</td>
                    </tr>
                <?php
            }
        }
        if ($headerShown == 1 && $newsletter->released === true) {
            $headerShown++;
            $class = "released";
            ?>
                <tr>
                    <td class="lastchange" colspan="2">— <?php self::t(_("Released Newsletters")); ?> —</td>
                </tr>
            <?php
        }
    ?>
        <tr <?php self::attr([
            "data-project" => $newsletter->project->name,
            "data-newsletter" => $newsletter->name,
        ]); ?>>
            <td class="url <?php self::t($class); ?>"><a href="<?php self::t("{$newsletterUrl}edit/"); ?>"><?php self::t($newsletter->getTitle()); ?></a></td>
            <td class="date <?php self::t($class); ?>"><?php self::t($formatter->format($newsletter->document->getDocInfo()->lastchange, true)); ?></td>
        </tr>
    <?php } ?>
</table>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
