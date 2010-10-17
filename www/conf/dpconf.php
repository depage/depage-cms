<?php

/**
 * depage config file
 */

$conf = array(
    // {{{ global
    '*' => array(
        'db' => array(
            'dsn' => "mysql:dbname=depagecms;host=127.0.0.1",
            'user' => "root",
            'password' => "",
            'prefix' => "tt",
        ),
    ),
    // }}}
    
    // {{{ */depage_1.5/
    '*/depage_1.5/' => array(
        'handler' => "cms_ui",
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
