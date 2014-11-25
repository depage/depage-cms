<!DOCTYPE html>
<html>
    <head>
        <title><?php
            self::t($this->title);
            if ($this->subtitle != null) {
                self::t(" // " . $this->subtitle);
            }
        ?></title>

        <base href="<?php self::base(); ?>">

        <?php $this->include_js("interface", array(
            "framework/Cms/js/interface.js",
            "framework/shared/jquery.cookie.js",
            //"framework/shared/jquery.hotkeys.js",
            //"framework/Cms/js/jstree.js",
        )); ?>
        <?php $this->include_css("interface", array(
            "framework/htmlform/lib/css/depage-forms.css",
            //"framework/Cms/css/jstree.css",
            "framework/Cms/css/interface.css",
        )); ?>
    </head>
    <body>
        <?php self::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
