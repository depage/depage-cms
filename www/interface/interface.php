<?php
/**
 * G E R O N I M O
 * I N T E R F A C E
 *
 * php-script:
 * (c)2003 jonas [jonas.info@gmx.net]
 */

	define('IS_IN_CONTOOL', true);

	require_once('../scripts/lib/lib_global.php');
	require_once('lib_html.php');
	require_once('lib_auth.php');
?>
<html>
	<head>
		<meta http-equiv=Content-Type content="text/html;  charset=ISO-8859-1">
		<title><?php echo($conf->app_name . ' ' . $conf->app_version); ?></title>
		<?php 
			$settings = $conf->getScheme($conf->interface_scheme);
			$lang = $conf->getTexts($conf->interface_language, 'inhtml');
			
			$params = "nsrpc=" . urlencode($conf->ns['rpc']['ns']) 
				. "&nsrpcuri=" . urlencode($conf->ns['rpc']['uri'])
				. "&phost=" . $_SERVER["HTTP_HOST"]
				. "&pport=" . urlencode($conf->pocket_port)
				. "&standalone=" . $_GET["standalone"];
			if ($_GET['userid'] != "null") {
			    $flashfile = "main.swf?userid=" . $_GET['userid'] . "&" . $params;
			} else if ($conf->interface_autologin) {
				$user = new ttUser();
				$userid = $user->login($conf->interface_autologin_user, $conf->interface_autologin_pass, $conf->interface_autologin_project, $_SERVER["REMOTE_ADDR"]);
			    $flashfile = "index.swf?userid=" . ($userid == false ? "null" : $userid) . "&" . $params;
			} else {
			    $flashfile = "index.swf?userid=null&" . $params;
			}
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			function open_edit(userid) {
				top.open_edit(userid);
			}
			
			function open_login() {
				top.opener.location = top.opener.location;
			}
			
			function open_upload(sid, wid, path) {
				h = 360;
				w = 320;
				x = (screen.availWidth - w) / 2;
				y = (screen.availHeight - h) / 2;
			
				options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=0,scrollbars=0,status=0,titlebar=0,toolbar=0,screenX=" + x + ",screenY=" + y + ",left=" + x + ",top=" + y;
				url = "upload.php?sid=" + sid + "&wid=" + wid + "&path=" + path;
				uploadwin = open(url, "tt_upload" + sid, options);
				uploadwin.opener = top;
			}
			
			function set_title(newtitle) {
				top.set_title(newtitle);
			}
			
			function set_status(message) {
				top.window.status = unescape(message);
			}
			
			function preview(newURL) {
				top.preview(newURL);
			}
			
			function msg(newmsg) {
				newmsg = unescape(newmsg);
				newmsg = newmsg.replace(/<br>/g, "\n");
				newmsg = newmsg.replace(/&apos;/g, "'");
				newmsg = newmsg.replace(/&quot;/g, "\"");
				newmsg = newmsg.replace(/&auml;/g, "ä");
				newmsg = newmsg.replace(/&Auml;/g, "Ä");
				newmsg = newmsg.replace(/&ouml;/g, "ö");
				newmsg = newmsg.replace(/&Ouml;/g, "Ö");
				newmsg = newmsg.replace(/&uuml;/g, "ü");
				newmsg = newmsg.replace(/&Uuml;/g, "Ü");
				newmsg = newmsg.replace(/&szlig;/g, "ß");
				alert(newmsg);
			}
			
			function load_flasherror() {
				if (flashloaded == false) {
					window.location="msg.php?msg=inhtml_needed_flash&title=inhtml_require_title";
				}	
			}
			
			function set_flashloaded() {
				flashloaded = true;
			}
			
			function start_pocketServer() {
				parent.nothing.location = "../scripts/pocket_server_start.php";
				parent.contentX.location = "interface.php";
			}
			
			flashloaded = false;
		//-->	
		</script>
		<?php htmlout::echoStyleSheet(); ?>
	</head>
	<body bgcolor="<?php echo($settings['color_background']); ?>" onUnload="open_login();">
		<script language="JavaScript" type="text/javascript">
		<!--
			document.write('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http:\/\/download.macromedia.com\/pub\/shockwave\/cabs\/flash\/swflash.cab#version=6,0,0,0" WIDTH="100%" HEIGHT="100%" id="screensize" ALIGN=""><param name=movie value="<?php echo($flashfile) ?>"><param name=quality value=best><param name=bgcolor value=<?php echo($settings['color_background']); ?>><embed src="<?php echo($flashfile); ?>" quality=best bgcolor=<?php echo($settings['color_background']); ?>  WIDTH="100%" HEIGHT="100%" NAME="screensize" ALIGN="" TYPE="application\/x-shockwave-flash" PLUGINSPAGE="http:\/\/www.macromedia.com\/go\/getflashplayer"><\/embed><\/object>');
			<?php if ($_GET['userid'] == "null") {
				echo("setTimeout(\"load_flasherror()\", 12000);"); 
			} ?>
		//-->	
		</script>
		<noscript>
			<?php htmlout::echoMsg($lang["inhtml_require_title"], str_replace("%app_name%", $conf->app_name, $lang["inhtml_require_javascript"])); ?>
		</noscript>
	</body>
</html>
