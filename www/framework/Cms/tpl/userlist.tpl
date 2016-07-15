<ul>
    <?php foreach($this->users as $user) { ?>
        <?php
            $userAgent = $user->getUseragent();

            if ($userAgent == "Other/Other") {
                $userAgent = "";
            } else {
                $userAgent = " / " . $userAgent;
            }
        ?>
        <li>
            <a href="user/<?php self::t($user->name); ?>/">
                <?php self::t($user->fullname); ?>
            </a><?php self::t($userAgent); ?>
        </li>
    <?php } ?>
</ul>
<?php if ($this->showCurrent) { ?>
    <div class="bottom">
        <a href="users/" class="button"><?php self::t(_("All Users")); ?></a>
    </div>
<?php } else { ?>
    <div class="bottom">
        <a href="user/+/" class="button new"><?php self::t(_("Add New User")); ?></a>
    </div>
<?php } ?>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
