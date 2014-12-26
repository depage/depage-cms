<ul id="userlist">
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
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
