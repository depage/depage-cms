<?php
/**
 * @file    framework/Comments/Ui/Comments.php
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

class Comments extends \Depage\Depage\Ui\Base
{
    // {{{ default config
    public $defaults = array(
        'urlHasLocale' => true,
        'comments' => array(),
        'email' => array(),
        'jsmin' => array(),
    );
    protected $options = array();
    // }}}

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
        return array(
            '*/*'              => '\Depage\Comments\Ui\CommentsHandler',
        );
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

        $this->project = $this->urlSubArgs[0];
        $this->pageId = $this->urlSubArgs[1];

        // setup database instance
        if (!isset($this->pdo)) {
            $this->pdo = new \Depage\Db\Pdo(
                $this->options->db->dsn,
                $this->options->db->user,
                $this->options->db->password,
                array(
                    'prefix' => $this->options->db->prefix,
                )
            );
        }
        // get auth object
        $this->auth = \depage\Auth\Auth::factory(
            $this->pdo,
            $this->options->auth->realm,
            DEPAGE_BASE, // domain
            $this->options->auth->method
        );
        // set html-options
        $this->htmlOptions = array(
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
            'jsmin' => $this->options->jsmin,
        );

        $this->basetitle = "";

        // establish if the user is logged in
        //if ($this->auth_user === null) {
            //$this->auth_user = $this->auth->enforce_lazy();
        //}
    }
    // }}}

    // {{{ _package()
    /**
     * Package
     *
     * Package the output to HTML if non-AJAX request.
     *
     * @return void
     */
    public function _package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "Depage\Html\Html")) {
            // pack into body html
            $output = new Html("_html.tpl", array(
                'lang'            => DEPAGE_LANG,
                'title'           => $this->basetitle,
                'subtitle'        => $output->title,
                'content'         => $output,
            ), $this->htmlOptions);
        }
        return $output;
    }
    // }}}

    // {{{ _projectExists()
    public function _projectExists() {
        if (isset($this->options->comments->{$this->project})) {
            $this->notificationEmail = $this->options->comments->{$this->project}->notificationEmail;

            return true;
        } else {
            return false;
        }
    }
    // }}}

    protected function _sendCommentNotification($comment) {
        if (!empty($this->notificationEmail)) {
            $mail = new \depage\mail\mail($this->options->email->from);
            $mail->setSubject(sprintf(_("depage comments") . " . " . _("new comment for '%s'"), $this->project));
            $mail->setText(
                "{$comment->author_name} <{$comment->author_email}>\n" .
                "{$comment->author_url}\n" .
                "\n" .
                "{$comment->comment}\n"
            );
            $mail->send($this->notificationEmail);
        }
    }

    public function index() {
        return $this->notfound();
    }

    public function notfound($function = "") {
        parent::notfound($function);
        return "notfound: $function";
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
