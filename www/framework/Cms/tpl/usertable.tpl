<table class="users">
    <thead>
        <tr>
            <th class="user-name"><?php self::t(_("Name")); ?></th>
            <th class="user-type"><?php self::t(_("Type")); ?></th>
            <th class="user-projects"><?php self::t(_("Projects")); ?></th>
        </tr>
    </thead>
    <?php foreach($this->users as $user) { ?>
        <?php
            $userAgent = $user->getUseragent();

            if ($userAgent == "Other/Other") {
                $userAgent = "";
            } else {
                $userAgent = " / " . $userAgent;
            }
        ?>
        <tr>
            <td class="user-name">
                <a href="user/<?php self::t($user->name); ?>/">
                    <?php self::t($user->fullname); ?>
                </a>
            </td>
            <td class="user-type">
                <?php self::t($user->type); ?>
            </td>
            <td class="user-projects">
                <?php foreach (($this->projectsByUser[$user->id] ?? []) as $p) { ?>
                    <p><?php self::t($p->fullname); ?></p>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>
<div class="bottom">
    <a href="user/+/" class="button new"><?php self::t(_("Add New User")); ?></a>
</div>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
