<ul id="projectlist">
    <?php foreach($this->projects as $project) { ?>
        <li>
            <h2><?php self::t($project->fullname); ?></h2>
            <a href="project/<?php self::t($project->name); ?>/edit/" class="button">
                <?php self::t(_('edit')) ?>
            </a>
            <a href="project/<?php self::t($project->name); ?>/preview/" class="button">
                <?php self::t(_('preview')) ?>
            </a>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
