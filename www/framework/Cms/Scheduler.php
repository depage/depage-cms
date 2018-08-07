<?php

if (php_sapi_name() != 'cli') die();

require_once(__DIR__ . "/../Depage/Runner.php");

$dp = new \Depage\Depage\Runner();
$options = $dp->conf;

$pdo = new \Depage\Db\Pdo (
    $options->db->dsn, // dsn
    $options->db->user, // user
    $options->db->password, // password
    [
        'prefix' => $options->db->prefix, // database prefix
    ]
);

$bgTasks = new Depage\Cms\BackgroundTasks($pdo, DEPAGE_BASE);
$bgTasks->schedule();

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
