<h1><?php self::t($this->user->fullname); ?> / <?php self::t($this->user->name); ?></h1>
<p>&nbsp;</p>
<p><a href="mailto:<?php self::t($this->user->email); ?>">
    <?php self::t($this->user->email); ?>
</a></p>
<p><?php self::t(_($this->user->type)); ?></p>

<div class="bottom">
    <a href="users/" class="button"><?php self::t(_("All Users")); ?></a>
</div>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
