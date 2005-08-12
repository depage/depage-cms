<?php
/**
 * G E R O N I M O
 * R E Q U I R E M E N T S
 *
 * php-script:
 * (c)2003 jonas [jonas.info@gmx.net]
 */

	define("IS_IN_CONTOOL", true);
		
	require_once('../scripts/lib/lib_global.php');
	require_once('lib_html.php');
	require_once('lib_auth.php');
		
	$settings = $conf->getScheme($conf->interface_scheme);
	$lang = $conf->getTexts($conf->interface_language, 'inhtml', false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<?php
			$minversion = $_GET["flashplayer_needed"];
			if ($minversion == ".0..0" || $minversion == "") {
				$minversion = "";
			} else {
				$minversion = " " . $minversion;
			}
		?>
		<title><?php echo($lang[$_GET["title"]]); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<?php htmlout::echoStyleSheet(); ?>
	</head>
	<body bgcolor="<?php echo($settings['color_background']); ?>">
		<?php htmlout::echoMsg($lang[$_GET["title"]], str_replace(array("%minversion%", "%app_name%"), array($minversion, $conf->app_name), $lang[$_GET["msg"]])); ?>
	</body>
</html>
