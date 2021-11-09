<!DOCTYPE html>
<html <?php self::attr("lang", DEPAGE_LANG); ?>>
    <head>
        <title><?php
            if ($this->subtitle != null) {
                self::t($this->subtitle . " / ");
            }
            self::t($this->title);
        ?></title>

        <base href="<?php self::base(); ?>">

        <?php $this->includeJs("interface", [
            "framework/Cms/js/interface.js",
            "framework/HtmlForm/lib/js/effect.js",
        ]); ?>
        <?php $this->includeCss("interface", [
            "framework/Cms/css/interface.css",
        ]); ?>
        <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="framework/Cms/images/favicon.ico">
        <link rel="icon" type="image/png" href="framework/Cms/images/favicon.png">
    </head>
    <body>
        <?php self::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
