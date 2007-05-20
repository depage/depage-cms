<?php
/**
 * depage::cms
 * R E Q U I R E M E N T S
 *
 * php-script:
 * (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 */

	define("IS_IN_CONTOOL", true);
		
	require_once('../lib/lib_global.php');
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
			if ($minversion == ".0..0") {
				$minversion = "";
			} else {
				$minversion = " " . $minversion;
			}
		?>
		<title><?php echo($lang["inhtml_require_title"]); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<?php htmlout::echoStyleSheet(); ?>
	</head>
	<body bgcolor="<?php echo($settings['color_background']); ?>">
		<?php htmlout::echoMsg($lang["inhtml_require_title"], str_replace(array("%minversion%", "%app_name%"), array($minversion, $conf->app_name), $lang["inhtml_needed_flash"])); ?>
	</body>
</html>
