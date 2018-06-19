<?php

require_once("framework/Depage/Runner.php");

class JsTreeApplication implements \Wrench\Application\DataHandlerInterface,
    Wrench\Application\ConnectionHandlerInterface,
    Wrench\Application\UpdateHandlerInterface
{
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

    // {{{ name()
    protected function getCid($client)
    {
        $docId = $client->getQueryParams()['docId'];
        $projectName = $client->getQueryParams()['projectName'];

        return "{$projectName}_{$docId}";
    }
    // }}}

    public function onConnect(Wrench\Connection $client): void
    {
        $docId = $client->getQueryParams()['docId'];
        $projectName = $client->getQueryParams()['projectName'];
        $cid = $this->getCid($client);
        $prefix = "{$this->pdo->prefix}_proj_{$projectName}";

        if (empty($this->clients[$cid])) {
            $this->clients[$cid] = [];
            $xmldbCache = \Depage\Cache\Cache::factory($prefix, array(
                'disposition' => "redis",
                'host' => "127.0.0.1:6379",
            ));
            $project = \Depage\Cms\Project::loadByName($this->pdo, $xmldbCache, $projectName);
            $this->xmldbs[$cid] = $project->getXmlDb();
            $this->deltaUpdates[$cid] = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $this->xmldbs[$cid], $docId, $projectName);
        }

        $this->clients[$cid][] = $client;
    }

    public function onDisconnect(Wrench\Connection $client): void
    {
        $cid = $this->getCid($client);
        $key = array_search($client, $this->clients[$cid]);
        if ($key) {
            unset($this->clients[$cid][$key]);

            if (empty($this->clients[$cid])) {
                unset($this->clients[$cid]);
                unset($this->xmldbs[$cid]);
                unset($this->deltaUpdates[$cid]);
            }
        }
    }

    public function onUpdate() {
        foreach ($this->clients as $id => $clients) {
            $data = $this->deltaUpdates[$id]->encodedDeltaUpdate();

            if (!empty($data)) {
                // send to clients
                foreach ($clients as $client) {
                    try {
                        $client->send($data);
                    } catch (\Wrench\Exception\SocketException $e) {
                        $this->onDisconnect($client);
                    }
                }
            }
        }

        // do not sleep too long, this impacts new incoming connections
        usleep(50 * 1000);
    }

    public function onData(string $data, Wrench\Connection $client):void
    {
        // do nothing, only send data in onUpdate() because fallback clients do not support websockets
    }
}

?>
