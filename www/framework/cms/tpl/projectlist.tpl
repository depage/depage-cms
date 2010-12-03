<div id="box_projects" class="centered_box">
    <div class="content">
        <h1>Projects</h1>
        <ul id="projectlist">
            <?php foreach($this->projects as $pname => $pid) { ?>
                <li><?php html::t($pname); ?></li>
            <?php } ?>
        </ul>
    </div>
</div>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
