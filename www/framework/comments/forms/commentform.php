<?php

namespace depage\comments\forms;

class commentForm extends \depage\htmlform\htmlform {
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
        ));

        $this->addText("name", array(
            'label' => _('Name'),
            'defaultValue' => !empty($_COOKIE['depage-comment-name']) ? $_COOKIE['depage-comment-name'] : '',
            'placeholder' => _('Your name'),
            'required' => true,
            'helpMessage' => _("Please fill in your full name, e.g. the name you established as a writer in the film market. First your first name, then your last name."),
        ));
        
        $this->addEmail("email", array(
            'label' => _('Email'),
            'defaultValue' => !empty($_COOKIE['depage-comment-email']) ? $_COOKIE['depage-comment-email'] : '',
            'placeholder' => _('email@domain.com'),
            'required' => true,
            'helpMessage' => _("Enter your email address. You will need it to confirm your account."),
        ));
        
        $this->addUrl("website", array(
            'label' => _('Website') . ' ' . _('(public)'),
            'defaultValue' => !empty($_COOKIE['depage-comment-website']) ? $_COOKIE['depage-comment-website'] : '',
            'placeholder' => _('http://domain.com'),
            'helpMessage' => _("If you don't have a website of your own, you can also link a meaningful profile, e.g. IMDB or Crew United."),
        ));

        $this->mustbeempty = $this->addText("mustbeempty", array(
            'label' => _("must be left empty"),
            'class' => "mustbeempty",
        ));
        
        
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
        $path = parse_url(DEPAGE_BASE, PHP_URL_PATH);

        foreach (array("name", "email", "website") as $field) {
            if ($values[$field] != "") setcookie("depage-comment-$field", $values[$field], $expire, $path);
        }

        return true;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
