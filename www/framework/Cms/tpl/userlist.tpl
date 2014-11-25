<ul id="userlist">
    <?php foreach($this->users as $user) { ?>
        <li>
            <a href="user/<?php self::t($user->name); ?>/">
                <?php self::t($user->fullname); ?>
            </a> / <?php self::t($user->getUseragent()); ?>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
