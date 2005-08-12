<?php
/**
 * G E R O N I M O
 * I N T E R F A C E   I N D E X
 *
 * php-script:
 * (c)2003 jonas [jonas.info@gmx.net]
 */

	define('IS_IN_CONTOOL', true);

	require_once('../scripts/lib/lib_global.php');
	require_once('lib_html.php');
?>
<HTML>
<HEAD>
<meta http-equiv=Content-Type content="text/html; charset=ISO-8859-1">
<TITLE><?php echo($conf->app_name . ' ' . $conf->app_version); ?></TITLE>
			<script language="JavaScript" type="text/javascript">
			<!--
				function open_edit() {
				}
				
				function open_upload() {
				}
				
				function close_edit() {
				}
				
				function set_title(newtitle) {
					opener.set_title(newtitle);
				}
				
				function preview(newURL) {
					opener.preview(newURL);
				}
			//-->	
			</script>
</HEAD>

<frameset rows="100%,0" frameborder="0" border="0"  framespacing="0" onUnload="close_edit()">
    <frame id="contentFrameX" name="contentX" src="interface.php?standalone=<?php echo(isset($_GET['standalone']) ? $_GET['standalone'] : "true"); ?>&userid=<?php echo(isset($_GET['userid']) ? $_GET['userid'] : "null"); ?>" scrolling="no" noresize frameborder="0" border="0"  framespacing="0" marginwidth="0" marginheight="0">
    <frame name="nothing" src="../scripts/pocket_server_start.php" scrolling="no" noresize frameborder="0" border="0" framespacing="0">
</frameset>
</HTML>
