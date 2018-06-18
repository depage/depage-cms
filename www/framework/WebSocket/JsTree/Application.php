<?php

require_once("framework/Depage/Runner.php");
// TODO: convert to autoloader
require_once("framework/WebSocket/lib/WebSocket/Application/Application.php");

class JsTreeApplication extends \Websocket\Application\Application {
    private $clients = [];
    private $deltaUpdates = [];
    private $xmldbs = [];
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        'env' => "development",
        'timezone' => "UST",
    );

    function __construct() {
        parent::__construct();

        $conf = new \Depage\Config\Config();
        $conf->readConfig(__DIR__ . "/../../../conf/dpconf.php");
        $this->options = $conf->getFromDefaults($this->defaults);

        // get database instance
        $this->pdo = new \Depage\Db\Pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        /* get auth object
        $this->auth = \Depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        ); */
    }

    public function onConnect($client)
    {
        // TODO: authentication ? beware of timeouts
        $cid = $client->param;
        list($docId, $projectName) = explode("/", $client->param);
        $prefix = "{$this->pdo->prefix}_proj_{$projectName}";

        if (empty($this->clients[$cid])) {
            $this->clients[$cid] = [];
            // @todo make instance correctly
            $this->xmldbs[$cid] = new \Depage\XmlDb\XmlDb($prefix, $this->pdo, \Depage\Cache\Cache::factory($prefix, array(
                'disposition' => "redis",
                'host' => "127.0.0.1:6379",
            )));
            $this->deltaUpdates[$cid] = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $this->xmldbs[$cid], $docId, $projectName);
        }

        $this->clients[$cid][] = $client;
    }

    public function onDisconnect($client)
    {
        $cid = $client->param;
        $key = array_search($client, $this->clients[$cid]);
        if ($key) {
            unset($this->clients[$cid][$key]);

            if (empty($this->clients[$cid])) {
                unset($this->xmldbs[$cid]);
                unset($this->deltaUpdates[$cid]);
            }
        }
    }

    public function onTick() {
        foreach ($this->clients as $id => $clients) {
            $data = $this->deltaUpdates[$id]->encodedDeltaUpdate();

            if (!empty($data)) {
                // send to clients
                foreach ($clients as $client) {
                    $client->send($data);
                }
            }
        }

        // do not sleep too long, this impacts new incoming connections
        usleep(50 * 1000);
    }

    public function onData($raw_data, $client)
    {
        // do nothing, only send data in onTick() because fallback clients do not support websockets
    }
}

?>
