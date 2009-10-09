<?php
/**
 * depage::cms
 * I N T E R F A C E
 *
 * php-script:
 * (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 */

    define('IS_IN_CONTOOL', true);

    require_once('../lib/lib_global.php');
    require_once('lib_project.php');
    require_once('lib_html.php');
    require_once('lib_auth.php');

    $project->user->auth_http();

    $settings = $conf->getScheme($conf->interface_scheme);
    $lang = $conf->getTexts($conf->interface_language, 'inhtml');
    $phost = parse_url("http://" . $_SERVER["HTTP_HOST"]);
    
    $params = "nsrpc=" . urlencode($conf->ns['rpc']['ns']) 
            . "&nsrpcuri=" . urlencode($conf->ns['rpc']['uri'])
            . "&phost=" . urlencode($phost['host'])
            . "&pport=" . urlencode($conf->pocket_port)
            . "&puse=" . urlencode($conf->pocket_use ? "true" : "false")
            . "&standalone=" . urlencode($_GET["standalone"])
            . "&project=" . urlencode($_GET["project_name"])
            . "&page=" . urlencode($_GET["page"])
            . "&userid=" . urlencode($project->user->sid);
    $flashfile = "main.swf?" . $params;

    $html = new html();

    $html->head();
    ?>
	<body bgcolor="<?php echo($settings['color_background']); ?>" onUnload="open_home();">
		<script language="JavaScript" type="text/javascript">
		<!--
			document.write('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http:\/\/download.macromedia.com\/pub\/shockwave\/cabs\/flash\/swflash.cab#version=6,0,0,0" WIDTH="100%" HEIGHT="100%" id="flash" ALIGN=""><param name=movie value="<?php echo($flashfile) ?>"><param name=quality value=best><param name=bgcolor value=<?php echo($settings['color_background']); ?>><embed src="<?php echo($flashfile); ?>" swliveconnect=true quality=best bgcolor=<?php echo($settings['color_background']); ?>  WIDTH="100%" HEIGHT="100%" NAME="flash" ALIGN="" TYPE="application\/x-shockwave-flash" PLUGINSPAGE="http:\/\/www.macromedia.com\/go\/getflashplayer"><\/embed><\/object>');
			<?php if ($_GET['sid'] == "null") {
				echo("setTimeout(\"load_flasherror()\", 12000);"); 
			} ?>
		//-->	
		</script>
		<noscript>
			<?php htmlout::echoMsg($lang["inhtml_require_title"], str_replace("%app_name%", $conf->app_name, $lang["inhtml_require_javascript"])); ?>
		</noscript>
	</body>
<?php
    $html->end();
