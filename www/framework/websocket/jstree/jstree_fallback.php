<?php
require_once("delta_updates.php");

class WebSocketFallback {
    function __construct() {
        $this->db = new PDO("mysql:host=localhost;dbname=jstree", "root", "");
    }

    public function updates($seq_nr = 0) {
        // TODO: cleanup old recorded changes bases on logged in users
        $delta_updates = new DeltaUpdates($this->db, $seq_nr);
        return $delta_updates->encodedDeltaUpdate();
    }
}

header("HTTP/1.0 200 OK");
header('Content-type: text/json; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

$wsf = new WebSocketFallback();
echo $wsf->updates($_REQUEST["seq_nr"]);
die();

?>
