<?php
/**
 * @file    framework/Comments/Ui/CommentsHandler.php
 *
 * comments module
 *
 *
 * copyright (c) 2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace Depage\Comments\Ui;

use \Depage\Html\Html;

class CommentsHandler extends Comments {
    // {{{ _getSubHandler
    /**
     * Subhandler
     *
     * Defines the subhandler classes identified by given url routes.
     *
     * @return array
     *
     */
    public static function _getSubHandler() {
        return array();
    }
    // }}}

    // {{{ _init
    /**
     * Initializer
     *
     * Handles initialization of the user interface
     *
     * @return void
     */
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        // @todo test project name for availability
        $this->project = $this->urlSubArgs[0];
        $this->pageId = (int) $this->urlSubArgs[1];

        // setup database instance
        $this->pdo = new \Depage\Db\Pdo(
            $this->options->db->dsn,
            $this->options->db->user,
            $this->options->db->password,
            array(
                'prefix' => $this->options->db->prefix . "_proj_" . $this->project,
            )
        );
        $allowedDomains = array(
            "http://cms.depagecms.net",
            "http://dev.depage.net",
            "http://romanatiozzo.es",
        );
        /*
        if (($key = array_search($_SERVER['HTTP_ORIGIN'], $allowedDomains)) !== false) {
            header("Access-Control-Allow-Origin: {$allowedDomains[$key]}");
        }
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization");
        header("Access-Control-Allow-Credentials: true");
         */
    }
    // }}}

    // {{{ index()
    public function index() {
        return $this->show();
    }
    // }}}

    // {{{ show()
    public function show() {
        if (!$this->_projectExists()) {
            return $this->notfound();
        }

        $form = new forms\commentForm("comment_{$this->project}_{$this->pageId}", array());
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();

            if ($values['mustbeempty'] == "") {
                $comment = new models\comment($this->pdo, array(
                    'page_id' => $this->pageId,
                    'author_name' => $values['name'],
                    'author_email' => $values['email'],
                    'author_url' => $values['website'],
                    'author_ip' => inet_pton(\depage\http\request::getRequestIp()),
                    'comment' => $values['text'],
                ));
                $result = $comment->save();
                if ($result) {
                    $this->_sendCommentNotification($comment);
                }
            }
            $form->clearSession();
        }

        $comments = \Depage\Comments\Models\Comment::loadByPageId($this->pdo, $this->pageId);

        return new Html("comments.tpl", array(
            'comments' => $comments,
            'commentForm' => $form,
        ), $this->htmlOptions);
    }
    // }}}

    // {{{ count()
    public function count() {
        if (!$this->_projectExists()) {
            return $this->notfound();
        }

        $count = models\comment::countByPageId($this->pdo, $this->pageId);

        return new Html("commentcount.tpl", array(
            'count' => $count,
        ), $this->htmlOptions);
    }
    // }}}

    // {{{ notfound
    public function notfound($function = "") {
        parent::notfound($function);
        return "notfound: $function";
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */

