<?php
	define('IS_IN_CONTOOL', true);

	require_once('lib/lib_global.php');
	require_once('lib_pocket_server.php');
	
	headerNoCache();
	
	if ($conf->get_tt_env('pocket_server_running') == 0) {
		$conf->execInBackground($conf->path_server_root . $conf->path_base . '/scripts/', 'pocket_server.php');
	}
?>
<html>
	<head>
		<title>starting pocketServer</title>
	</head>
	<body>
	</body>	
</html>
