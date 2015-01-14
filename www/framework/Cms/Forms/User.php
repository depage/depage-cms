<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class User extends \Depage\HtmlForm\HtmlForm
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $this->user = $params['user'];
        $this->authUser = $params['authUser'];

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");

        parent::__construct($name, $params);

        if ($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null) {
            $f = $this->addFieldSet("Basics", array(
                'label' => _("Basics"),
            ));
            $f->addText("name", array(
                "label" => _("Username"),
                "required" => "true",
                "validator" => "/[-a-zA-Z0-9_]+/",
                "disabled" => $this->user->id !== null,
            ));
            $f->addText("fullname", array(
                "label" => _("Display Name"),
                "required" => "true",
            ));
            $f->addEmail("email", array(
                "label" => _("Email"),
            ));
        }

        if ($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null) {
            $f = $this->addFieldSet("Password", array(
                'label' => _("Change Password"),
            ));
            $f->addPassword("password1", array(
                "label" => _("Password"),
                "autocomplete" => false,
            ));
            $f->addPassword("password2", array(
                "label" => _("Repeat Password"),
                "autocomplete" => false,
                "errorMessage" => _("Both passwords have to be equal"),
            ));
        }

        if ($this->authUser->canEditAllUsers()) {
            $f = $this->addFieldSet("Permission", array(
                'label' => _("Permissions"),
            ));
            $f->addSingle("type", array(
                'label' => _("User type"),
                'skin' => "select",
                'defaultValue' => 'Depage\Cms\Auth\DefaultUser',
                'list' => array(
                    'Depage\Cms\Auth\Admin' => _("Administrator"),
                    'Depage\Cms\Auth\Developer' => _("Developer"),
                    'Depage\Cms\Auth\MainUser' => _("Main User"),
                    'Depage\Cms\Auth\DefaultUser' => _("Default User"),
                    'Depage\Cms\Auth\Editor' => _("Editor"),
                ),
            ));
        }

        $this->populate($this->user);
    }
    // }}}
    // {{{ onValidate()
    /**
     * @brief onValidate
     *
     * @param mixed $values
     * @return void
     **/
    protected function onValidate()
    {
        $values = $this->getValues();

        $valid = $values['password1'] == $values['password2'];
        if (!$valid) {
            $this->getElement('password2')->invalidate();
        }

        return $valid;

    }

    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
