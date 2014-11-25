<ul id="projectlist">
    <?php foreach($this->projects as $pname => $pid) { ?>
        <li>
            <h2><?php self::t($pname); ?></h2>
            <a href="project/<?php self::t($pname); ?>/edit/" class="button">
                <?php self::t(_('edit')) ?>
            </a>
            <a href="project/<?php self::t($pname); ?>/preview/" class="button">
                <?php self::t(_('preview')) ?>
            </a>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
