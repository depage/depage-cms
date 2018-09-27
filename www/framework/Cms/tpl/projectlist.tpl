<div class="projectlist">
    <?php foreach($this->projectGroups as $group) {
        $projects = array_filter($this->projects, function($project) use ($group) {
            return $project->groupId == $group->id;
        });

        if (count($projects) > 0)  {
    ?>
        <div class="projectgroup">
            <h2><?php self::t($group->name); ?></h2>
            <dl>
                <?php foreach($projects as $project) { ?>
                    <dt data-project="<?php self::t($project->name); ?>">
                        <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                            <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
                        <?php } ?>

                        <strong><?php self::t($project->fullname); ?></strong>

                        <div class="buttons">
                            <a href="project/<?php self::t($project->name); ?>/edit/" class="button" title="<?php self::t(_("Edit this project")); ?>">
                                <?php self::t(_('Edit')) ?>
                            </a>
                            <a href="project/<?php self::t($project->name); ?>/preview/" class="button preview" target="previewFrame" title="<?php self::t(_("Preview your project")); ?>">
                                <?php self::t(_('Preview')) ?>
                            </a>
                            <?php if ($this->user->canPublishProject()) { ?>
                                <a href="project/<?php self::t($project->name); ?>/publish/" class="button" title="<?php self::t(_("Publish all changes to the live server")); ?>">
                                    <?php self::t(_('Publish')) ?>
                                </a>
                            <?php } ?>
                            <?php if ($project->hasPageShortcuts()) { ?>
                                <a class="button shortcuts icon-add" aria-label="<?php self::t(_("Project shortcuts")); ?>">
                                    +
                                </a>
                            <?php } ?>
                            <a href="project/<?php self::t($project->name); ?>/library/" class="button icon-library" aria-label="<?php self::t(_("Manage file library")); ?>">
                                <?php self::t(_('Library')) ?>
                            </a>
                            <a href="project/<?php self::t($project->name); ?>/colors/" class="button icon-colors" aria-label="<?php self::t(_("Add and edit colors")); ?>">
                                <?php self::t(_('Colors')) ?>
                            </a>
                            <a href="project/<?php self::t($project->name); ?>/settings/" class="button icon-settings" aria-label="<?php self::t(_("Change project settings")); ?>">
                                <?php self::t(_('Settings')) ?>
                            </a>
                        </div>
                    </dt>
                    <?php // @todo add project shortcut for sorted elements (news/blogentry etc.)?>
                    <dd>
                        <?php self::t(_('loading...')) ?>
                    </dd>
                    <?php if ($project->hasNewsletter() && $this->user->canEditNewsletter()) { ?>
                        <dt data-project-newsletter="<?php self::t($project->name); ?>">
                            <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                                <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
                            <?php } ?>

                            <strong><?php self::t($project->fullname . " – " . _("newsletter")); ?></strong>
                        </dt>
                        <dd>
                            <?php self::t(_('loading...')) ?>
                        </dd>
                    <?php } ?>
                <?php } ?>
            </dl>
        </div>
    <?php }} ?>
</div>
<div class="bottom">
    <a href="project/+/" class="button new">
        <?php self::t(_('add new project')) ?>
    </a>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
