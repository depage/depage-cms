<ul id="projectlist">
    <?php foreach($this->projects as $project) { ?>
        <li><?php html::t($project); ?></li>
    <?php } ?>
</ul>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
