<div id="toolbarmain" class="toolbar">
    <h1>depage::cms</h1>
    <menu class="left">
        <li><a href="" class="button">home</a></li>
    </menu>
    <menu class="preview">
        <!-- empty placeholder - content is added with javascript -->
    </menu>
    <menu class="right">
        <?php if(!empty($this->projectname)) { ?>
            <!-- @todo add submenu for project -->
            <li><a href="" class="button icon-projects"><?php self::t($this->projectname); ?></a>
                <menu class="popup projects">
                    <?php foreach($this->projects as $project) { ?>
                        <li>
                            <a href="project/<?php self::t($project->name); ?>/">
                                <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                                    <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
                                <?php } ?>
                                <?php self::t($project->fullname); ?>
                            </a>
                            <a href="project/<?php self::t($project->name); ?>/settings/" class="right"><?php self::t(_("Settings")); ?></a>
                        </li>
                    <?php } ?>
                </menu>
            </li>
        <?php } ?>
        <!-- @todo add submenu for user -->
        <li><a href="user/<?php self::t($this->user->name); ?>/" class="button icon-user"><?php self::t($this->user->fullname); ?></a>
            <menu class="popup">
                <li><a href="user/<?php self::t($this->user->name); ?>/"><?php self::t(_("Account settings")); ?></a></li>
                <li><hr></li>
                <li><a href="logout/" id="logout">logout</a></li>
            </menu>
        </li>
        <li><a id="help" class="button icon-help">?</a></li>
    </menu>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
