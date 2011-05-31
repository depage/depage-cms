<?php

require_once("lib/WebSocket/Application/Application.php");
require_once("delta_updates.php");

class JsTreeApplication extends \Websocket\Application\Application {
    private $clients = array();

    function __construct() {
        parent::__construct();
        
        $db = new PDO("mysql:host=localhost;dbname=jstree", "root", "");
        $this->delta_updates = new DeltaUpdates($db);
    }

    public function onConnect($client)
    {
        $this->clients[] = $client;
    }

    public function onDisconnect($client)
    {
        $key = array_search($client, $this->clients);
        if ($key) {
            unset($this->clients[$key]);
        }
    }

    public function onTick() {
        $data = $this->delta_updates->encodedDeltaUpdate();
        
        if (!empty($data)) {
            // send to clients
            foreach ($this->clients as $client) {
                $client->send($data);
            }
        }

        // do not sleep too long, this impacts new incoming connections
        usleep(50 * 1000);
    }

    public function onData($raw_data, $client)
    {
        // TODO
        $data = json_decode($raw_data);
        foreach ($this->clients as $sendto) {
            $sendto->send($data);
        }
    }
}

?>
