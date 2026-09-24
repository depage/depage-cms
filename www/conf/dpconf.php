<?php
/**
 * depage config file
 */

$conf = [
    // {{{ global
    '*' => [
        'db' => [
            'dsn' => 'mysql:dbname=depage_2_0;host=localhost',
            'user' => 'root',
            'password' => '',
            'prefix' => 'dp',
        ],
        'auth' => [
            'realm' => 'depage::cms',
            'method' => 'http_cookie',
        ],
        'timezone' => 'UTC',
        //'env' => 'production',
        'phpcli' => "/usr/bin/php",
    ],
    // }}}

    // {{{ */depage-cms/
    '*/depage-cms/' => array(
        'handler' => 'Depage\Cms\Ui\Main',
        //'env' => 'production',
        'phpcli' => "/opt/local/bin/php",
        'websocket' => "ws://localhost:8000",
    ),
    '*/depage-cms-dev/' => array(
        'handler' => 'Depage\Cms\Ui\Main',
        'phpcli' => "/opt/local/bin/php",
        'websocket' => "ws://localhost:8000",
    ),
    // }}}
    // {{{ localhost/depage-cms/
    'localhost/depage-cms/' => array(
        //'env' => 'production',
        'cache' => array(
            'xmldb' => array(
                'disposition' => "redis",
                'host' => "localhost:6379",
            ),
        ),
        'video' => array (
            'ffmpeg' => '/opt/local/bin/ffmpeg',
            'ffprobe' => '/opt/local/bin/ffprobe',
            'qtfaststart' => '/opt/local/bin/qt-faststart',
            'aaccodec' => 'aac',
        ),
        'graphics' => [
            'extension' => "gm",
            'executable' => "/opt/local/bin/gm",
        ],
        'websocket' => "ws://localhost:8000",
    ),
    // }}}
    // {{{ shirasu/depage-cms/
    'shirasu/depage-cms/' => [
        //'env' => 'production',
        'cache' => [
            'xmldb' => [
                'disposition' => "redis",
                'host' => "localhost:6379",
            ],
        ],
        'video' => [
            'ffmpeg' => '/opt/local/bin/ffmpeg',
            'ffprobe' => '/opt/local/bin/ffprobe',
            'qtfaststart' => '/opt/local/bin/qt-faststart',
            'aaccodec' => 'aac',
        ],
        'graphics' => [
            'extension' => "gm",
            'executable' => "/opt/local/bin/gm",
        ],
        'websocket' => "ws://localhost:8000",
    ],
    // }}}
    // {{{ *.bella.local/depage-cms/
    '*.bella.local/depage-cms/' => array(
        'env' => 'production',
        'cache' => array(
            'xmldb' => array(
                'disposition' => "redis",
                'host' => "localhost:6379",
            ),
        ),
        'video' => array (
            'ffmpeg' => '/opt/local/bin/ffmpeg',
            'ffprobe' => '/opt/local/bin/ffprobe',
            'qtfaststart' => '/opt/local/bin/qt-faststart',
            'aaccodec' => 'aac',
        ),
        'graphics' => [
            'extension' => "gm",
            'executable' => "/opt/local/bin/gm",
        ],
        'websocket' => "ws://localhost:8000",
    ),
    // }}}
    // {{{ graphics
    '*/depage-cms/**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => [
        'handler' => 'Depage\Graphics\Ui\Graphics',
        //'env' => 'production',
        'extension' => "gm",
        'executable' => "/opt/local/bin/gm",
        'base' => 'inherit',
    ],
    // }}}

    // {{{ edit.depage.net
    '*edit.depage.net/' => [
        'handler' => 'Depage\Cms\Ui\Main',
        'phpcli' => "/usr/bin/php",
        'db' => [
            'dsn' => 'mysql:dbname=depage-edit;host=aaf.mariadb',
            //'dsn' => 'mysql:dbname=depage-edit;host=mariadb',
            'user' => 'depagecms',
            'password' => 'YLBD49g.!ega-6Pd1F!di0xAHqf.AKuK',
            'prefix' => 'dp',
        ],
        'cache' => [
            'xmldb' => [
                'disposition' => "redis",
                'host' => "redis:6379",
            ],
        ],
        'graphics' => [
            'extension' => "gm",
            'executable' => "/usr/bin/gm",
            'optimize' => true,
        ],
        'websocket' => "ws://phpwebsocketserver:8000",
        'env' => 'production',
    ],
    // }}}
    // {{{ edit.depage.net graphics
    '*edit.depage.net/**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => [
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ],
    // }}}
];

if (
    ($_SERVER['HTTP_HOST'] ?? "") == "edit.depage.net" 
    && gethostbyname("aaf.mariadb") === "aaf.mariadb"
) {
    $conf['editbeta.depage.net/']['db']['dsn'] = 'mysql:dbname=depage-edit;host=mariadb';
    $conf['*edit.depage.net/']['db']['dsn'] = 'mysql:dbname=depage-edit;host=mariadb';
}

return $conf;

/* vim:set ft=php sts=4 fdm=marker et : */
