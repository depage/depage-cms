<?php

error_reporting(E_ALL);

require_once("Application.php");
require(__DIR__ . '/../lib/SplClassLoader.php');

// TODO: replace custom class loader with existing one
$classLoader = new SplClassLoader('WebSocket', __DIR__ . '/../lib');
$classLoader->register();

// TODO: add configuration options for port and application name
$server = new \WebSocket\Server('localhost', 8000);
$server->registerApplication('jstree', JsTreeApplication::getInstance());
$server->run();
