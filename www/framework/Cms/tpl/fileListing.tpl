<h1><?php self::t($this->path); ?></h1>
<ul>
    <?php foreach($this->files as $file) { ?>
        <li>
            <?php
                $thumb = new \Depage\Html\Html("thumbnail.tpl", [
                    'file' => "libref://{$file}",
                    'project' => $this->project,
                ], $this->param);
            ?>
            <a>
                <?php self::e($thumb); ?>
            </a>
        </li>
    <?php } ?>

</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
