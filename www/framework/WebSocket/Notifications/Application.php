<?php

namespace Depage\WebSocket\Notifications;

use \Depage\Notifications\Notification;

class Application implements \Wrench\Application\DataHandlerInterface,
    \Wrench\Application\ConnectionHandlerInterface,
    \Wrench\Application\UpdateHandlerInterface
{
    // {{{ variables
    private $clients = [];
    private $deltaUpdates = [];
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        'env' => "development",
        'timezone' => "UST",
    );
    // }}}

    // {{{ __construct
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
    }
    // }}}
    // {{{ onConnect
    public function onConnect(\Wrench\Connection $client): void
    {
        $id = $client->getId();
        if (empty($this->clients[$id])) {
            $this->clients[$id] = $client;
        }
    }
    // }}}
    // {{{ onDisconnect
    public function onDisconnect(\Wrench\Connection $client): void
    {
        $id = $client->getId();
        if (isset($this->clients[$id])) {
            unset($this->clients[$id]);
        }
    }
    // }}}
    // {{{ onUpdate
    public function onUpdate() {
        foreach ($this->clients as $cid => $client) {
            list($key, $sid) = explode("=", $client->getHeaders()['cookie']);
            $nn = Notification::loadBySid($this->pdo, $sid, "depage.%");

            foreach ($nn as $n) {
                $data = json_encode($n);
                $client->send($data);

                $n->delete();
            }
        }

        // do not sleep too long, this impacts new incoming connections
        usleep(50 * 1000);
    }
    // }}}
    // {{{ onData
    public function onData(string $data, \Wrench\Connection $client):void
    {
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :