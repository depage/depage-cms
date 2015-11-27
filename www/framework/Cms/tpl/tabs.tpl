<?php
    use \Depage\Html\Html;
?>
<ul class="tabs">
    <?php
        foreach ($this->tabs as $id => $title) {
            $className = "tab";

            if ($id == $this->activeTab) {
                $className .= " active";
            }
            $href = "{$this->baseUrl}$id/";

            ?><li class="<?php html::t($className); ?>"><a href="<?php html::t($href); ?>"><?php html::t($title); ?></a></li><?php
        }
    ?>
</ul>

<?php /* vim:set ft=php sw=4 sts=4 fdm=marker et : */
