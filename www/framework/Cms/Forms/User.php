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

        $params['label'] = _("Save User");

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");
        $params['class'] = "edit-user";

        $projects = $params['projects'];

        parent::__construct($name, $params);

        if ($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null) {
            $f = $this->addFieldset("Basics", [
                'label' => _("Basics"),
            ]);
            $f->addText("name", [
                "label" => _("Username"),
                "required" => "true",
                "validator" => "/[-a-zA-Z0-9_]+/",
                "disabled" => $this->user->id !== null,
            ]);
            $f->addText("fullname", [
                "label" => _("Display Name"),
                "required" => "true",
            ]);
            $f->addEmail("email", [
                "label" => _("Email"),
            ]);
        }

        if ($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null) {
            $f = $this->addFieldset("Password", [
                'label' => _("Change Password"),
            ]);
            $f->addPassword("password1", [
                "label" => _("Password"),
                "autocomplete" => "new-password",
            ]);
            $f->addPassword("password2", [
                "label" => _("Repeat Password"),
                "autocomplete" => "new-password",
                "errorMessage" => _("Both passwords have to be equal"),
            ]);
        }

        if ($this->authUser->canEditAllUsers() && !$this->user->canEditAllUsers()) {
            $f = $this->addFieldset("Permission", [
                'label' => _("Permissions"),
            ]);
            $f->addSingle("type", [
                'label' => _("User type"),
                'skin' => "select",
                'defaultValue' => 'Depage\Cms\Auth\DefaultUser',
                'list' => [
                    'Depage\Cms\Auth\Admin' => _('Depage\Cms\Auth\Admin'),
                    'Depage\Cms\Auth\Developer' => _('Depage\Cms\Auth\Developer'),
                    'Depage\Cms\Auth\MainUser' => _('Depage\Cms\Auth\MainUser'),
                    'Depage\Cms\Auth\DefaultUser' => _('Depage\Cms\Auth\DefaultUser'),
                    'Depage\Cms\Auth\Editor' => _('Depage\Cms\Auth\Editor'),
                ],
            ]);
            $projectList = [];
            $projectSelected = [];
            foreach ($projects as $p) {
                $projectList[$p->id] = $p->fullname;
                if ($this->user->canEditProject($p->name)) {
                    $projectSelected[] = $p->id;
                }
            }
            $f->addMultiple("projects", [
                'label' => _("Projects"),
                'class' => "projects",
                //'skin' => "checkbox",
                'skin' => "select",
                'list' => $projectList,
                'defaultValue' => $projectSelected,
            ]);
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
