<ul id="userlist">
    <?php foreach($this->users as $user) { ?>
        <li>
            <a href="user/<?php html::t($user->name); ?>/">
                <?php html::t($user->fullname); ?>
            </a> / <?php html::t($user->get_useragent()); ?>
        </li>
    <?php } ?>
</ul>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
