<div class="projectlist">
    <?php foreach($this->projectGroups as $group) {
        $projects = array_filter($this->projects, function($project) use ($group) {
            return $project->groupId == $group->id;
        });

        if (count($projects) > 0)  {
    ?>
        <h2><?php self::t($group->name); ?></h2>
        <dl>
            <?php foreach($projects as $project) { ?>
                <dt data-project="<?php self::t($project->name); ?>">
                    <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                        <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
                    <?php } ?>

                    <strong><?php self::t($project->fullname); ?></strong>

                    <div class="buttons">
                        <a href="project/<?php self::t($project->name); ?>/edit/" class="button">
                            <?php self::t(_('edit')) ?>
                        </a>
                        <a href="project/<?php self::t($project->name); ?>/preview/" class="button preview" target="previewFrame">
                            <?php self::t(_('preview')) ?>
                        </a>
                        <a href="project/<?php self::t($project->name); ?>/publish/" class="button">
                            <?php self::t(_('publish')) ?>
                        </a>
                        <a href="project/<?php self::t($project->name); ?>/settings/" class="button icon-settings">
                            <?php self::t(_('settings')) ?>
                        </a>
                    </div>
                </dt>
                <dd>
                    <?php self::t(_('loading...')) ?>
                </dd>
            <?php } ?>
        </dl>
    <?php }} ?>
</div>
<div class="bottom">
    <a href="project/+/" class="button new">
        <?php self::t(_('add new project')) ?>
    </a>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
