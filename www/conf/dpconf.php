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
            //'method' => 'http_cookie',
            'method' => 'http_basic',
            //'method' => 'http_digest',
            'digestCompat' => true,
        ),
        'timezone' => 'Europe/Berlin',
        //'env' => 'production',
    ),
    // }}}

    // {{{ */depage-cms/
    '*/depage-cms/' => array(
        //'handler' => 'DepageLegacy\LegacyUI',
        'handler' => 'depage\CMS\UI\Main',
        //'env' => 'production',
    ),
    '*/depage-cms-dev/' => array(
        'handler' => 'depage\CMS\UI\Main',
    ),
    // }}}
    // {{{ localhost/depage_1.5/live/
    'localhost/depage_1.5/live/' => array(
        'handler' => 'depage\cms\live',
        'env' => 'production',
        'cms' => array(
            'project' => 'depagecms',
        ),
    ),
    // }}}
    // {{{ cms.depagecms.net
    'cms.depagecms.net/' => array(
        'handler' => 'cms_ui',
    ),
    // }}}
    // {{{ graphics
    '*/depage-cms/**.(gif|jpg|jpeg|png).*.(gif|jpg|jpeg|png)$' => array(
        'handler' => 'depage\graphics\graphics_ui',
        //'env' => 'production',
        'extension' => "gm",
        'executable' => "/opt/local/bin/gm",
        'background' => "#CCC8C4",
        'base' => 'inherit',
    ),
    // }}}
);

return $conf;

/* vim:set ft=php sts=4 fdm=marker et : */
