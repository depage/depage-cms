<!DOCTYPE html>
<html>
    <head>
        <title><?php html::t($this->title); ?></title>

        <base href="<?php html::base(); ?>">

	<script type="text/javascript" src="framework/cms/js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="framework/cms/js/interface.js"></script>
    </head>
    <body>
        <?php html::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
