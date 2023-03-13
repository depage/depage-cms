<?php
/**
 * @file    framework/Cms/Ui/User.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class User extends Base
{
    // {{{ _init
    public function _init(array $importVariables = [])
    {
        parent::_init($importVariables);

        $this->userName = $this->urlSubArgs[0];

        if (empty($this->userName)) {
            throw new \Depage\Auth\Exceptions\User("no user given");
        } else if ($this->userName == "+") {
            $this->user = new \Depage\Cms\Auth\DefaultUser($this->pdo);
        } else {
            $this->user = \Depage\Auth\User::loadByUsername($this->pdo, $this->userName);
        }

    }
    // }}}

    // {{{ index()
    function index()
    {
        if ($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null) {
            return $this->edit();
        } else {
            return $this->show();
        }
    }
    // }}}
    // {{{ edit()
    /**
     * @brief edit
     *
     * @param mixed
     * @return void
     **/
    protected function edit()
    {
        if (!($this->authUser->canEditAllUsers() || $this->authUser->id == $this->user->id || $this->user->id == null)) {
            throw new \Exception("you are not allowed to to this!");
        }
        $projects = \Depage\Cms\Project::loadAll($this->pdo, $this->xmldbCache);
        $form = new \Depage\Cms\Forms\User("edit-user-" . $this->user->id, [
            "user" => $this->user,
            "authUser" => $this->authUser,
            "projects" => $projects,
        ]);
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->user->$key = $val;
            }
            if ($values['password1'] !== "" && $values['password1'] == $values['password2']) {
                $pass = new \Depage\Auth\Password($this->auth->realm, $this->auth->digestCompat);
                $this->user->passwordhash = $pass->hash($user->name, $values['password1']);
            };

            $this->user->save();
            $this->user->saveProjectRights($values['projects']);
            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        if ($this->user->id != null) {
            $title = sprintf(_("Edit user '%s'"), $this->user->fullname);
        } else {
            $title = _("Add new User");
        }
        $h = new Html("scrollable.tpl", [
            'content' => new Html("box.tpl", [
                'id' => "user",
                'class' => "box-users",
                'title' => $title,
                'content' => [
                    $form,
                ],
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ show()
    /**
     * @brief show
     *
     * @param mixed
     * @return void
     **/
    protected function show()
    {
        $h = new Html("scrollable.tpl", [
            'content' => new Html("box.tpl", [
                'id' => "user",
                'class' => "box-users",
                'title' => $title,
                'content' => [
                    new Html("userprofile.tpl", [
                        'user' => $this->user,
                    ]),
                ],
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
