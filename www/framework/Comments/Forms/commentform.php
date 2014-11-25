<?php

namespace Depage\Comments\Forms;

class CommentForm extends \depage\htmlform\htmlform {
    protected $mustbeempty;

    // {{{ constructor()
    /**
     * Constructor
     *
     * @param unknown_type $name
     * @param unknown_type $parameters
     *
     * @return void
     */
    public function __construct($name, $parameters = array()) {
        $parameters['label'] = _("Send");
        $parameters['submitURL'] = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (!empty($_GET['successURL'])) {
            $parameters['submitURL'] = $_GET['successURL'];
            $parameters['successURL'] = $_GET['successURL'];
        }

        parent::__construct($name, $parameters);

        $this->addTextarea("text", array(
            'label' => _('Text'),
            'required' => true,
            'autogrow' => true,
            'placeholder' => _('Leave a message...'),
        ));

        $this->addHtml("<div class=\"sender-data\">");
            $this->addText("name", array(
                'label' => _('Name'),
                'defaultValue' => !empty($_COOKIE['depage-comment-name']) ? $_COOKIE['depage-comment-name'] : '',
                'placeholder' => _('Your name'),
                'required' => true,
                'helpMessage' => _("Please fill in your name."),
            ));

            $this->addEmail("email", array(
                'label' => _('Email'),
                'defaultValue' => !empty($_COOKIE['depage-comment-email']) ? $_COOKIE['depage-comment-email'] : '',
                'placeholder' => _('email@domain.com'),
                'required' => true,
                'helpMessage' => _("Enter your email address."),
            ));

            $this->addUrl("website", array(
                'label' => _('Website') . ' ' . _('(public)'),
                'defaultValue' => !empty($_COOKIE['depage-comment-website']) ? $_COOKIE['depage-comment-website'] : '',
                'placeholder' => _('http://domain.com'),
                'helpMessage' => _("If you don't have a website of your own or don't want to link to it, leave it blank."),
            ));
        $this->addHtml("</div>");

        $this->mustbeempty = $this->addText("mustbeempty", array(
            'label' => _("must be left empty"),
            'class' => "mustbeempty",
        ));
        $this->addHtml("<p class=\"hint\"><small>* " . htmlspecialchars(_("mandatory field")) . "</small></p>");


    }
    // }}}

    // {{{ onValidate()
    /**
     * @brief   sets cookies for some fields to keep longer than session
     *
     * @return  true
     **/
    public function onValidate() {
        $values = $this->getValues();
        $expire = time() + (20 * 365 * 24 * 60 * 60);
        $path = "/";

        foreach (array("name", "email", "website") as $field) {
            if (!empty($values[$field])) setcookie("depage-comment-$field", $values[$field], $expire, $path);
        }

        return true;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
