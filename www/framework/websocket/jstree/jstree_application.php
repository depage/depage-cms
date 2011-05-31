<?php

// TODO: convert to autoloader
require_once("../lib/WebSocket/Application/Application.php");
require_once("../../depage/depage.php");

class JsTreeApplication extends \Websocket\Application\Application {
    private $clients = array();
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        'env' => "development",
        'timezone' => "UST",
    );

    function __construct() {
        parent::__construct();

        $conf = new config();
        $conf->readConfig(__DIR__ . "/../../../conf/dpconf.php");
        $this->options = $conf->getFromDefaults($this->defaults);

        // get database instance
        $this->pdo = new \db_pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        // TODO init correctly
        $this->prefix = "dp_proj_{$this->pdo->prefix}";
        $this->xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, \depage\cache\cache::factory($this->prefix));

        // get auth object
        $this->auth = \auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        $this->delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, PHP_INT_MAX);
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
