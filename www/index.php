<?php
/**
 * @file    index.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2017 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

    require_once('framework/Depage/Runner.php');

    $dp = new \Depage\Depage\Runner();
    $dp->handleRequest();

    if (!empty($GLOBALS['replacementScript'])) {
        unset($dp);

        chdir(dirname($GLOBALS['replacementScript']));
        include(basename($GLOBALS['replacementScript']));
    }

    /* vim:set ft=php sw=4 sts=4 fdm=marker et : */
