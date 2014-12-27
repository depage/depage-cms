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
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->userName = $this->urlSubArgs[0];

        if (empty($this->userName)) {
            throw new \Depage\Auth\Exceptions\User("no user given");
        } else if ($this->userName == "+") {
            $this->user = new \Depage\Auth\User($this->pdo);
        } else {
            $this->user = \Depage\Auth\User::loadByUsername($this->pdo, $this->userName);
        }

    }
    // }}}

    // {{{ index()
    function index() {
        if ($this->projectName == "+") {
            return $this->edit();
        } else {
            return $this->edit();
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
        $form = new \Depage\Cms\Forms\User("edit-user-" . $this->user->id, array(
            "user" => $this->user,
        ));
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->user->$key = $val;
            }

            $this->user->save();
            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        $h = new Html("box.tpl", array(
            'id' => "user",
            'icon' => "framework/Cms/images/icon_users.gif",
            'class' => "first",
            'title' => sprintf(_("Edit User '%s'"), $this->user->fullname),
            'content' => array(
                $this->toolbar(),
                $form,
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
