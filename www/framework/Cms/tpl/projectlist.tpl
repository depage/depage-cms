<dl class="projectlist">
    <?php foreach($this->projects as $project) { ?>
        <dt data-project="<?php self::t($project->name); ?>">
            <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
            <?php } ?>

            <strong><?php self::t($project->fullname); ?></strong>

            <div class="buttons">
                <a href="project/<?php self::t($project->name); ?>/edit/" class="button">
                    <?php self::t(_('edit')) ?>
                </a>
                <a href="project/<?php self::t($project->name); ?>/preview/" class="button">
                    <?php self::t(_('preview')) ?>
                </a>
                <a href="project/<?php self::t($project->name); ?>/settings/" class="button">
                    <?php self::t(_('settings')) ?>
                </a>
            </div>
        </dt>
        <dd>
            loading...
        </dd>
    <?php } ?>
</dl>
<div class="bottom">
    <a href="project/+/" class="button">
        <?php self::t(_('add new project')) ?>
    </a>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
