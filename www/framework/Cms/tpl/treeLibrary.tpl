<?php
    $level = function($path = "") use (&$level) {
        $pattern = trim($path . "/*", '/');
        $dirs = $this->fs->lsDir($pattern);

        if (count($dirs) == 0) return;

        ?><ul><?php
        foreach($dirs as $dir) {
            if ($dir == "cache") continue;

            ?><li>
                <a <?php self::attr([
                    'href' => "libref://{$dir}",
                ]); ?>><?php self::t(pathinfo($dir, \PATHINFO_FILENAME)); ?></a>
                <?php $level($dir); ?>
            </li><?php
        }
        ?></ul><?php
    };

    // @todo update to show root level
?>
<div class="jstree-container">
    <?php $level(); ?>
</div>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
