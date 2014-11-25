<!DOCTYPE html>
<html lang="<?php self::t($this->lang); ?>">
    <head>
        <title><?php
            if ($this->subtitle != null) {
                self::t($this->subtitle . " // ");
            }
            self::t($this->title);
        ?></title>

        <base href="<?php self::base(); ?>">
        <!--<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">-->

        <?php
            $this->include_css("global", array(
                "framework/htmlform/lib/css/depage-forms.css",
            ));

            $this->include_js("global", array(
                "framework/htmlform/lib/js/effect.js",
                //"modules/screenpitch/lib/global/js/global.js",
            ), "defer");
        ?>

        <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php echo("{$this->favicon}.ico") ?>">
        <link rel="icon" type="image/png" href="<?php echo("{$this->favicon}.png") ?>">
    </head>
    <body>
        <?php self::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
