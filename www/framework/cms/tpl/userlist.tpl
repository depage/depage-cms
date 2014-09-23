<ul id="userlist">
    <?php foreach($this->users as $user) { ?>
        <li>
            <a href="user/<?php html::t($user->name); ?>/">
                <?php html::t($user->fullname); ?>
            </a> / <?php html::t($user->getUseragent()); ?>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
