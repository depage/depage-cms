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
        'handler' => 'depage\cms\ui_main',
        //'env' => 'production',
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
    // {{{ localhost/depage-cms/test/
    'localhost/depage-cms/test/' => array(
        'handler' => 'test',
        'env' => 'development',
        'cms' => array(
            'project' => 'test',
        ),
        'db' => array(
            'dsn' => 'mysql:dbname=depage_2_0;host=localhost',
            'user' => 'root',
            'password' => '',
            'prefix' => 'dp',
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

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
