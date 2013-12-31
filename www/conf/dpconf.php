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
            'method' => 'http_digest',
            //'method' => 'http_cookie',
        ),
        'timezone' => 'Europe/Berlin',
        //'env' => 'production',
    ),
    // }}}
    
    // {{{ */depage-cms/
    '*/depage-cms/' => array(
        'handler' => 'DepageLegacy\LegacyUI',
        //'env' => 'production',
    ),
    '*/depage-cms-dev/' => array(
        'handler' => 'depage\cms\ui_main',
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
    '*/depage-cms/*.(gif|jpg|jpeg|png)$' => array(
        'handler' => 'depage\graphics\graphics_ui',
        //'env' => 'production',
        'base' => 'inherit',
    ),
    // }}}
);

return $conf;

/* vim:set ft=php sts=4 fdm=marker et : */
