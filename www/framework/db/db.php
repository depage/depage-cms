<?php
/**
 * @file    framework/db/db.php
 *
 * depage database module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class db {
    // {{{ getPDO
    /**
     * gets a PDO object based on database config parameters
     *
     * @param   $options (array) named options for base class
     *
     * @return  (PDO) pdo object
     */
    static function getPDO($values = array()) {
        $this->setConfig($values);
        $options = $conf->toOptions($this->defaults);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
