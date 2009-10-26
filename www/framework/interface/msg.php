<?php
/**
 * depage::cms
 * R E Q U I R E M E N T S
 *
 * php-script:
 * (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 */

    define("IS_IN_CONTOOL", true);
            
    require_once('../lib/lib_global.php');
    require_once('lib_html.php');
    require_once('lib_auth.php');
            
    $settings = $conf->getScheme($conf->interface_scheme);
    $lang = $conf->getTexts($conf->interface_language, 'inhtml', false);

    $minversion = $_GET["flashplayer_needed"];
    if ($minversion == ".0..0" || $minversion == "") {
            $minversion = "";
    } else {
            $minversion = " " . $minversion;
    }

    $html = new html();

    $html->head();
?>
    <body bgcolor="<?php echo($settings['color_background']); ?>">
        <?php $html->message($lang[$_GET["title"]], str_replace(array("%minversion%", "%app_name%"), array($minversion, $conf->app_name), $lang[$_GET["msg"]])); ?>
    </body>
<?php
    $html->end();
