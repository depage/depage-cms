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
            "modules/DepageLegacy/js/interface.js",
            "modules/DepageLegacy/js/legacy.js",
        )); ?>
        <?php $this->include_css("interface", array(
            "framework/htmlform/lib/css/depage-forms.css",
            "framework/cms/css/interface.css",
        )); ?>
    </head>
    <body>
        <?php html::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
