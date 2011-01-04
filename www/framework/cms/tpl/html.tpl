<!DOCTYPE html>
<html>
    <head>
        <title><?php 
            html::t($this->title); 
            if ($this->subtitle != null) {
                html::t(" // " . $this->subtitle);
            }
        ?></title>

        <base href="<?php html::base(); ?>">

        <?php $this->include_js("interface", array(
            "framework/shared/jquery-1.4.4.js",
            "framework/shared/jquery.cookie.js",
            "framework/cms/js/interface.js",
        )); ?>
        <?php $this->include_css("interface", array(
            "framework/cms/css/interface.css",
        )); ?>
    </head>
    <body>
        <?php html::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
