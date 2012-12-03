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
    // {{{ index()
    public function index() {
        var_dump($this->urlSubArgs);

        $comments = array();

        $form = new forms\commentForm("comment", array());
        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();
            $comment = new models\comment($this->pdo, array(
                'page_id' => $this->urlSubArgs[1],
                'author_name' => $values['name'],
                'author_email' => $values['email'],
                'author_url' => $values['website'],
                'author_ip' => $_SERVER["REMOTE_ADDR"],
                'comment' => $values['text'],
            ));
            $comments[] = $comment;

            //$form->clearSession();
        }

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

