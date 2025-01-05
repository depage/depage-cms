<?php

namespace Depage\Cms\Forms;

class Login extends \Depage\HtmlForm\HtmlForm
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $param
     * @return void
     **/
    public function __construct($name, $parameters = array(), $form = null)
    {
        $submitUrl = DEPAGE_BASE . "login/";
        if (!empty($_GET['redirectTo'])) {
            $submitUrl .= "?redirectTo=" . rawurlencode($_GET['redirectTo']);
        }

        $parameters['submitUrl'] = $submitUrl;
        $parameters['label'] = _("Login");
        $parameters['class'] = "labels-on-top";

        parent::__construct($name, $parameters, $form);
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @param mixed $param
     * @return void
     **/
    public function addChildElements(): void
    {
        $this->addHtml("<h2>" . _("Login") . "</h2>");
        $this->addText("name", [
            'label' => 'Name',
            'required' => true,
            'placeholder' => _("Username or email@domain.com"),
            //'autofocus' => true,
        ]);
        $this->addPassword("pass", [
            'label' => 'Passwort',
            'placeholder' => _("Your Password"),
            'required' => true,
        ]);
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
