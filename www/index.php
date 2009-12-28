<pre>
<?php
/**
 * @file    index.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
 
    require_once('framework/depage/depage.php');

    $dp = new depage();

    $log = new log($dp->conf->log);

    $log->log($log);

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
?></pre>
