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
        $parameters['validator'] = function($form, $values) {
            return $values['mustbeempty'] == "";
        };
        
        $parameters['label'] = _("Send");
        //$parameters['submitUrl'] = \html::link('login/', "auto");
        
        parent::__construct($name, $parameters);
        
        $this->addText("name", array(
            'label' => _('Name'),
            'placeholder' => _('Your name'),
            'required' => true,
            'helpMessage' => _("Please fill in your full name, e.g. the name you established as a writer in the film market. First your first name, then your last name."),
        ));
        
        $this->addEmail("email", array(
            'label' => _('Email'),
            'placeholder' => _('email@domain.com'),
            'defaultValue' => isset($user->email) ? $user->email : ($parameters['email'] ? $parameters['email'] : ''),
            'required' => true,
            'helpMessage' => _("Enter your email address. You will need it to confirm your account."),
        ));
        
        $this->addUrl("website", array(
            'label' => _('Website') . ' ' . _('(public)'),
            'placeholder' => _('http://domain.com'),
            'helpMessage' => _("If you don't have a website of your own, you can also link a meaningful profile, e.g. IMDB or Crew United."),
        ));

        $this->mustbeempty = $this->addText("mustbeempty", array(
            'label' => _("must be left empty"),
        ));
        
        $this->addTextarea("text", array(
            'label' => _('Text'),
            'required' => true,
            'autogrow' => true,
        ));
        
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
