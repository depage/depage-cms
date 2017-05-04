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

    if (!empty($replacementScript)) {
        unset($dp);

        chdir(dirname($replacementScript));
        include(basename($replacementScript));
    }

    /* vim:set ft=php sw=4 sts=4 fdm=marker et : */
