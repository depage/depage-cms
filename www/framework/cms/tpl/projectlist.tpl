<ul id="projectlist">
    <?php foreach($this->projects as $pname => $pid) { ?>
        <li>
            <h2><?php html::t($pname); ?></h2>
            <a href="project/<?php html::t($pname); ?>/" class="button">
                <?php echo(_('edit')) ?>
            </a>
            <a href="preview/<?php html::t($pname); ?>/" class="button">
                <?php echo(_('preview')) ?>
            </a>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
