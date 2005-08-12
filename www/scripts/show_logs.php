<?php
	define("IS_IN_CONTOOL", true);
	
	require_once('lib/lib_global.php');
	require_once('lib_html.php');

	//$random = uniqid(rand());
	$random="";
	headerNoCache();
	headerType();
	
?>
<html>
	<head>
		<meta http-equiv=Content-Type content="text/html;  charset=UTF-8">
		<?php
			$maxnum = 20;
			$startnum = (integer) $_GET['startnum'];
			
			if ($_GET['type'] != 'debug' && $_GET['type'] != 'sql' && $_GET['type'] != 'auth' && $_GET['type'] != 'pocket' && $_GET['type'] != 'task') {
			    $show_type = 'debug';
			} else {
			    $show_type = $_GET['type'];
			} 

			if ($_GET['clear'] == "true") {
			    $log->clear($show_type);
			}
			$entrynum = $log->get_entrynum($show_type);
		    $entries = $log->get_entries($startnum, $maxnum, $show_type);

			//echo("<meta http-equiv=\"Refresh\" content=\"10 ;URL=" . $_SERVER["SCRIPT_NAME"] . "?type=" . $show_type . "&startnum=" . $startnum . "" . "\">\n");	
		?>
		<title>geronimo logs</title>
		<style type="text/css">
		<!--
			* {
				font : 13px Tahoma; 
				font-family : 'Tahoma';
			}
			body { 
				background-color : #FFFFFF; 
				color : #000000;
			}
			a {
				text-decoration : none;
				color : #000000;
			}
			a:hover {
				color : #FF9900;
			}
		-->
		</style>
	</head>
	<body>
		<table style="width:100%; background:#EEEEEE;">
			<tr>
				<td align="left">
					<table>
						<tr>
							<td><a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=debug"); ?>" <?php if ($show_type == 'debug') { echo("style=\"font-weight : bold;\""); } ?>>debug log</a></td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td><a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=sql"); ?>" <?php if ($show_type == 'sql') { echo("style=\"font-weight : bold;\""); } ?>>sql log</a></td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td><a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=auth"); ?>" <?php if ($show_type == 'auth') { echo("style=\"font-weight : bold;\""); } ?>>auth log</a></td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td><a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=pocket"); ?>" <?php if ($show_type == 'pocket') { echo("style=\"font-weight : bold;\""); } ?>>pocket log</a></td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td><a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=task"); ?>" <?php if ($show_type == 'task') { echo("style=\"font-weight : bold;\""); } ?>>task log</a></td>
						</tr>
					</table>
				</td>
				<td align="right">
					<table>
						<tr>
							<td>
								<a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=" . $show_type . "&startnum=0&clear=true"); ?>">clear this log</a>
							</td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td>
								<?php if ($startnum > $maxnum - 1) { ?>
									<a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=" . $show_type . "&startnum=" . ($startnum - $maxnum) . ""); ?>">prev</a>
								<?php } else { ?>
									<span style="color:#BBBBBB">prev</span>
								<?php } ?>
							</td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td>
								<?php echo((round($startnum / $maxnum) + 1) . "/" . (round(($entrynum / $maxnum) + 0.5))); ?>
							</td>
							<td>&nbsp;&nbsp;|&nbsp;&nbsp;</td>
							<td>
								<?php if ($startnum < $entrynum - $maxnum) { ?>
									<a href="<?php echo($_SERVER["SCRIPT_NAME"] . "?type=" . $show_type . "&startnum=" . ($startnum + $maxnum) . ""); ?>">next</a>
								<?php } else { ?>
									<span style="color:#BBBBBB">next</span>
								<?php } ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br />
		<br />
		<table>
		<?php
			for ($i = 0; $i < count($entries); $i++) {
			    echo("<tr><td width=\"10\">&nbsp;</td><td width=\"200\" valign=\"top\">[" . str_replace(" ", "&nbsp;", $entries[$i]['times']) . "]</td><td width=\"350\" valign=\"top\"><pre>" . htmlspecialchars($entries[$i]['entry']) . "</pre></td></tr>\n");
			} 
			for ($i = $i; $i < $maxnum; $i++) {
				?>
			    <tr>
					<td width="10">&nbsp;</td>
					<td>
						<?php
							if ($i == 0) {
								echo("[no entries in " . $show_type . " log]");
							} else {
								echo("&nbsp;");
							}
						?>
					</td>
					<td>&nbsp;</td>
				</tr>
				<?php
			}
		?>
		</table>
	</body>
</html>
