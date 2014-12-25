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
            <a href="project/<?php self::t($project->name); ?>/settings/" class="button">
                <?php self::t(_('settings')) ?>
            </a>
        </li>
    <?php } ?>
</ul>
<div class="bottom">
    <a href="project/+/" class="button">
        <?php self::t(_('add new project')) ?>
    </a>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
