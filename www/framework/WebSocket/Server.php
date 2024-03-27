<?php

if (php_sapi_name() != 'cli') die();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../Depage/Runner.php");

$server = new \Wrench\BasicServer('ws://0.0.0.0:8000/', [
    'allowed_origins' => [
        'https://127.0.0.1',
        'https://localhost',
        'https://edit.depage.net',
        'https://editbeta.depage.net',
        'https://' . parse_url(\DEPAGE_BASE, \PHP_URL_HOST),
    ],
]);
$server->registerApplication('jstree', new \Depage\WebSocket\JsTree\Application());
$server->registerApplication('notifications', new \Depage\WebSocket\Notifications\Application());
$server->run();
