<h1><?php self::t(_("File Library")); ?></h1>
<p><?php self::t(_("Please search for a file or choose a folder on the left.")); ?></p>
<p><?php self::t(_("To upload a new file, please choose a folder to upload to first.")); ?></p>

<?php self::e($this->form); ?>
<ul class="results">
    <?php
        //var_dump($this->query);
        //var_dump($this->files);
    ?>

    <?php foreach($this->files as $file) { ?>
        <li>
            <?php
                $thumb = new \Depage\Html\Html("thumbnail.tpl", [
                    'file' => $file,
                    'project' => $this->project,
                ], $this->param);
            ?>
            <?php self::e($thumb); ?>
        </li>
    <?php } ?>
    <?php if (!empty($this->query['query']) && count($this->files) == 0) { ?>
        <li><?php self::t(_("No files found")); ?></li>
    <?php } ?>
</ul>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
