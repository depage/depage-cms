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
            //'method' => 'http_basic',
            //'method' => 'http_digest',
            //'digestCompat' => true,
        ],
        'timezone' => 'UTC',
        //'env' => 'production',
        'phpcli' => "/usr/bin/php",
    ],
    // }}}

    // {{{ */depage-cms/
    '*/depage-cms/' => array(
        //'handler' => 'DepageLegacy\LegacyUI',
        'handler' => 'depage\Cms\Ui\Main',
        //'env' => 'production',
        'phpcli' => "/opt/local/bin/php",
    ),
    '*/depage-cms-dev/' => array(
        'handler' => 'Depage\Cms\Ui\Main',
        'phpcli' => "/opt/local/bin/php",
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
    ],
    // }}}
    // {{{ *.bella.local/depage-cms/
    '*.bella.local/depage-cms/' => array(
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
    ),
    // }}}
    // {{{ graphics
    '*/depage-cms/project**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => [
        'handler' => 'Depage\Graphics\Ui\Graphics',
        //'env' => 'production',
        'extension' => "gm",
        'executable' => "/opt/local/bin/gm",
        'base' => 'inherit',
    ],
    // }}}

    // {{{ edit.depage.net
    '*edit.depage.net/' => [
        'handler' => 'depage\Cms\Ui\Main',
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
        'env' => 'production',
    ],
    // }}}
    // {{{ edit.depage.net graphics
    '*edit.depage.net/project**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => [
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ],
    // }}}

    // {{{ editbeta.depage.net
    'editbeta.depage.net/' => [
        'handler' => 'depage\Cms\Ui\Main',
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
        'env' => 'production',
    ],
    // }}}
    // {{{ editbeta.depage.net graphics
    'editbeta.depage.net/project**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => [
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ],
    // }}}

    // {{{ office.depage.net
    'office.depage.net/depage-cms/' => [
        'handler' => 'depage\Cms\Ui\Main',
        'phpcli' => "/usr/bin/php",
        'db' => [
            'dsn' => 'mysql:dbname=depage-edit;host=mariadb',
            'user' => 'root',
            'password' => 'killroy',
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
        'env' => 'production',
    ],
    // }}}
    // {{{ office.depage.net graphics
    'office.depage.net/depage-cms/project**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => array(
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ),
    // }}}
];

if (gethostbyname("aaf.mariadb") === "aaf.mariadb") {
    $conf['editbeta.depage.net/']['db']['dsn'] = 'mysql:dbname=depage-edit;host=mariadb';
    $conf['*edit.depage.net/']['db']['dsn'] = 'mysql:dbname=depage-edit;host=mariadb';
}

return $conf;

/* vim:set ft=php sts=4 fdm=marker et : */
