<?php

namespace Depage\WebSocket\Healthcheck;

class Application implements \Wrench\Application\ConnectionHandlerInterface
{
    // {{{ onConnect
    public function onConnect(\Wrench\Connection $client): void
    {
        $client->send("OK\n");
        $client->getSocket()->disconnect();
    }
    // }}}
    // {{{ onDisconnect
    public function onDisconnect(\Wrench\Connection $client): void
    {
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
