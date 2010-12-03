<div id="box_users" class="centered_box">
    <div class="content">
        <h1>Users</h1>
        <ul id="userlist">
            <?php foreach($this->users as $user) { ?>
                <li>
                    <a href="mailto:<?php html::t($user->email); ?>">
                        <?php html::t($user->fullname); ?>
                    </a> on <?php html::t($user->get_useragent()); ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
