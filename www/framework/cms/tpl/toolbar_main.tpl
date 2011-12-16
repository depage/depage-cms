<div id="toolbarmain" class="toolbar">
    <h1>depage::cms</h1>
    <menu>
        <li><a href="">home</a></li>
    </menu>
    <menu class="right">
        <li><a href="user/<?php html::t($this->username); ?>/"><?php html::t($this->username); ?></a></li>
        <li><a href="logout/" id="logout">logout</a></li>
    </menu>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
