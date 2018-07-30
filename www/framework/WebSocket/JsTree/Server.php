<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../../Depage/Runner.php");
require_once("Application.php");

$logger = new class extends \Psr\Log\AbstractLogger implements Psr\Log\LoggerInterface
{
    public function log($level, $message, array $context = [])
    {
        echo(sprintf('[%s] %s - %s', $level, $message, json_encode($context)));
    }
};

$server = new \Wrench\BasicServer('ws://0.0.0.0:8000/', [
    'allowed_origins' => [
        '127.0.0.1',
        'localhost',
        'edit.depage.net',
        'editbeta.depage.net',
        parse_url(DEPAGE_BASE, \PHP_URL_HOST),
    ],
]);
$server->registerApplication('jstree', new JsTreeApplication());
$server->setLogger($logger);
$server->run();
