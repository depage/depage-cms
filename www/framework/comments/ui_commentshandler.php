<?php
/**
 * @file    framework/comments/comments.php
 *
 * comments module
 *
 *
 * copyright (c) 2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\comments;

use \html;

class ui_commentsHandler extends ui_comments {
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
        $this->pdo = new \db_pdo(
            $this->options->db->dsn,
            $this->options->db->user,
            $this->options->db->password,
            array(
                'prefix' => $this->options->db->prefix . "_proj_" . $this->project,
            )
        );
        header("Access-Control-Allow-Origin: http://cms.depagecms.net");
    }
    // }}}
    
    // {{{ index()
    public function index() {
        return $this->show();
    }
    // }}}
    
    // {{{ show()
    public function show() {
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
                    'author_ip' => $_SERVER["REMOTE_ADDR"],
                    'comment' => $values['text'],
                ));
                $result = $comment->save();
            }
            $form->clearSession();
        }

        $comments = models\comment::loadByPageId($this->pdo, $this->pageId);

        return new html("comments.tpl", array(
            'comments' => $comments,
            'commentForm' => $form,
        ), $this->html_options);
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

