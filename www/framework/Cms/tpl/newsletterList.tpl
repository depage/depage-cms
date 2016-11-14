<?php
    $formatter = new \Depage\Formatters\DateNatural();
    $headerShown = 0;
    $class = "";
?>
<div class="buttons">
    <a href="project/<?php self::t($this->project->name); ?>/newsletter/+/" class="button icon-add">
        <?php self::t(_('Add new newsletter')) ?>
    </a>
</div>
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
            <td class="actions">
                <div class="buttons">
                    <a href="<?php self::t("{$newsletterUrl}edit/"); ?>" class="button"><?php self::t(_("Edit")); ?></a>
                    <a href="<?php self::t("{$newsletterUrl}publish/"); ?>" class="button"><?php self::t(_("Publish")); ?></a>
                </div>
            </td>
        </tr>
    <?php } ?>
</table>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
