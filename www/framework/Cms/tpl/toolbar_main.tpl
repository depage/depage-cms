<div id="toolbarmain" class="toolbar">
    <h1>depage::cms</h1>
    <menu class="left">
        <li><a href="" class="button">home</a></li>
    </menu>
    <menu class="right">
        <li><a href="user/<?php self::t($this->username); ?>/" class="button"><?php self::t($this->username); ?></a></li>
        <li><a href="logout/" id="logout" class="button">logout</a></li>
    </menu>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
