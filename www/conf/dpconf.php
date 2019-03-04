<?php
/**
 * depage config file
 */

$conf = array(
    // {{{ global
    '*' => array(
        'db' => array(
            'dsn' => 'mysql:dbname=depage_2_0;host=localhost',
            'user' => 'root',
            'password' => '',
            'prefix' => 'dp',
        ),
        'auth' => array(
            'realm' => 'depage::cms',
            'method' => 'http_cookie',
            //'method' => 'http_basic',
            //'method' => 'http_digest',
            //'digestCompat' => true,
        ),
        'timezone' => 'Europe/Berlin',
        //'env' => 'production',
        'phpcli' => "/usr/bin/php",
    ),
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
    'shirasu/depage-cms/' => array(
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
    '*/depage-cms/**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => array(
        'handler' => 'Depage\Graphics\Ui\Graphics',
        //'env' => 'production',
        'extension' => "gm",
        'executable' => "/opt/local/bin/gm",
        'base' => 'inherit',
    ),
    // }}}

    // {{{ edit.depage.net
    'edit.depage.net/' => array(
        'handler' => 'depage\Cms\Ui\Main',
        'phpcli' => "/usr/bin/php",
        'db' => array(
            'dsn' => 'mysql:dbname=depage-edit;host=mariadb',
            'user' => 'root',
            'password' => 'killroy',
            'prefix' => 'dp',
        ),
        'cache' => array(
            'xmldb' => array(
                'disposition' => "redis",
                'host' => "redis:6379",
            ),
        ),
        'graphics' => [
            'extension' => "gm",
            'executable' => "/usr/bin/gm",
            'optimize' => true,
        ],
        'env' => 'production',
    ),
    // }}}
// {{{ edit.depage.net graphics
    'edit.depage.net/**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => array(
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ),
    // }}}
    // {{{ editbeta.depage.net
    'editbeta.depage.net/' => array(
        'handler' => 'depage\Cms\Ui\Main',
        'phpcli' => "/usr/bin/php",
        'db' => array(
            'dsn' => 'mysql:dbname=depage-edit;host=mariadb',
            'user' => 'root',
            'password' => 'killroy',
            'prefix' => 'dp',
        ),
        'cache' => array(
            'xmldb' => array(
                'disposition' => "redis",
                'host' => "redis:6379",
            ),
        ),
        'graphics' => [
            'extension' => "gm",
            'executable' => "/usr/bin/gm",
            'optimize' => true,
        ],
        'env' => 'production',
    ),
    // }}}
    // {{{ editbeta.depage.net graphics
    'editbeta.depage.net/**.(gif|jpg|jpeg|png|webp|pdf|eps|svg|tif|tiff).*.(gif|jpg|jpeg|png|webp)$' => array(
        'handler' => 'Depage\Graphics\Ui\Graphics',
        'env' => 'production',
        'extension' => "gm",
        'executable' => "/usr/bin/gm",
        'optimize' => true,
        'base' => 'inherit',
        'env' => 'production',
    ),
    // }}}
);

return $conf;

/* vim:set ft=php sts=4 fdm=marker et : */
