<?php

/**
 * @file    framework/websocket/jstree/jstree_fallback.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\websocket\jstree;

class jstree_fallback extends \Depage\Depage\Ui\Base
{
    // {{{ constructor
    public function __construct($options = NULL) {
        parent::__construct($options);

        // get database instance
        $this->pdo = new \Depage\Db\Pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        // TODO: set project correctly
        $proj = "proj";
        $proj = "depage";
        $this->prefix = "{$this->pdo->prefix}_{$proj}";
        $this->xmldb = new \Depage\XmlDb\XmlDb ($this->prefix, $this->pdo, \Depage\Cache\Cache::factory($this->prefix));

        // get auth object
        $this->auth = \Depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );
    }
    // }}}

    // {{{ updates
    public function updates() {
        $this->auth->enforce();

        // TODO: cleanup old recorded changes based on logged in users
        $delta_updates = new jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $_REQUEST["doc_id"], $_REQUEST["seq_nr"]);
        return $delta_updates->encodedDeltaUpdate();
    }
    // }}}

    // {{{ _send_headers
    protected function send_headers($content) {
        header("HTTP/1.0 200 OK");
        header('Content-type: text/json; charset=utf-8');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Pragma: no-cache");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
