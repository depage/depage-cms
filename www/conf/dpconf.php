<?php

/**
 * depage config file
 */

$conf = array(
    // {{{ global
    '*' => array(
        'db' => array(
            'dsn' => "mysql:dbname=depage_depagecms;host=192.168.1.22",
            'user' => "root",
            'password' => "",
            'prefix' => "tt",
        ),
        'auth' => array(
            'realm' => "depage::cms",
            //'method' => "http_digest",
            'method' => "http_cookie",
        ),
        'timezone' => "Europe/Berlin",
    ),
    // }}}
    
    // {{{ */depage-cms/
    '*/depage-cms/' => array(
        'handler' => "cms_ui",
        //'env' => "production",
    ),
    // }}}
    // {{{ localhost/depage_1.5/live/
    'localhost/depage_1.5/live/' => array(
        'handler' => "cms_live",
        'env' => "production",
        'cms' => array(
            'project' => "depagecms",
        ),
    ),
    // }}}
    // {{{ localhost/depage_1.5/test/
    'localhost/depage_1.5/test/' => array(
        'handler' => "test",
        'env' => "development",
        'cms' => array(
            'project' => "depagecms",
        ),
    ),
    // }}}
    // {{{ cms.depagecms.net
    'cms.depagecms.net/' => array(
        'handler' => "cms_ui",
    ),
    // }}}
);

return $conf;

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
