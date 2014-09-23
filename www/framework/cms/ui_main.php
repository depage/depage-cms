<?php
/**
 * @file    framework/cms/ui_main.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\cms;

use \html;

class ui_main extends ui_base {
    protected $autoEnforceAuth = false;

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return array(
            'project/*' => '\depage\cms\ui_project',
            'project/*/tree/*' => '\depage\cms\ui_tree',
            'project/*/tree/*/fallback' => '\depage\cms\ui_socketfallback',
            'project/*/edit/*' => '\depage\cms\ui_edit',
        );
    }
    // }}}

    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        if ($this->auth->enforceLazy()) {
            // logged in
            $h = new html(array(
                'content' => array(
                    $this->toolbar(),
                    $this->projects(),
                    $this->users(),
                ),
            ));
        } else {
            // not logged in
            $h = new html(array(
                'content' => array(
                    $this->toolbar(),
                    'content' => new html("welcome.tpl", array(
                        'title' => "Welcome to\n depage::cms ",
                        'login' => "Login",
                        'login_link' => "login/",
                    )),
                )
            ), $this->html_options);
        }

        return $h;
    }
    // }}}

    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            \depage::redirect(DEPAGE_BASE);
        } else {
            // not logged in
            $form = new \depage\htmlform\htmlform("login", array(
                'submitLabel' => "Anmelden",
                'validator' => array($this, 'validate_login'),
            ));

            // define formdata
            $form->addText("name", array(
                'label' => 'Name',
                'required' => true,
            ));

            $form->addPassword("pass", array(
                'label' => 'Passwort',
                'required' => true,
            ));

            $form->process();

            if ($form->isValid()) {
                $form->clearSession();
            } else {
                $error = "";
                if (!$form->isEmpty()) {
                    $error = "<p class=\"error\">false/unknown username password combination</p>";
                }

                $h = new html("box.tpl", array(
                    'id' => "login",
                    'icon' => "framework/cms/images/icon_login.gif",
                    'class' => "first",
                    'title' => "Login",
                    'content' => array(
                        $error,
                        $form,
                    ),
                ), $this->html_options);

                return $h;
            }
        }
    }
    // }}}
    // {{{ validate_login
    public function validate_login($form, $values) {
        return (bool) $this->auth->login($values['name'], $values['pass']);
    }
    // }}}
    // {{{ logout
    public function logout($action = null) {
        //if ($action[0] == "now") {
            $this->auth->enforceLogout();
        //}

        $h = new html("box.tpl", array(
            'id' => "logout",
            'class' => "first",
            'title' => "Bye bye!",
            'content' => new html("logout.tpl", array(
                'content' => "Thank you for using depage::cms. ",
                'relogin1' => "You can relogin ",
                'relogin2' => "here",
                'relogin_link' => "login/",
            )),
        ), $this->html_options);

        return $h;
    }
    // }}}

    // {{{ projects
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function projects() {
        $this->auth->enforce();

        // get data
        $cp = new project($this->pdo);
        $projects = $cp->getProjects();

        // construct template
        $h = new html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/cms/images/icon_projects.gif",
            'class' => "first",
            'title' => "Projects",
            'content' => new html("projectlist.tpl", array(
                'projects' => $projects,
            )),
        ), $this->html_options);

        return $h;
    }
    // }}}

    // {{{ users
    /**
     * gets a list of loggedin users
     *
     * @return  null
     */
    public function users() {
        $this->auth->enforce();

        $users = \depage\Auth\User::loadActive($this->pdo);

        $h = new html("box.tpl", array(
            'id' => "users",
            'icon' => "framework/cms/images/icon_users.gif",
            'title' => "Users",
            'content' => new html("userlist.tpl", array(
                'title' => $this->basetitle,
                'users' => $users,
            )),
        ), $this->html_options);

        return $h;
    }
    // }}}
    // {{{ user
    /**
     * gets profile of user
     *
     * @return  null
     */
    public function user($username = "") {
        if ($user = $this->auth->enforce()) {
            $puser = \depage\Auth\User::loadByUsername($this->pdo, $username);

            if ($puser !== false) {
                $title = _("User Profile") . ": {$puser->fullname}";
                $content = new html("userprofile_edit.tpl", array(
                    'title' => $this->basetitle,
                    'user' => $puser,
                ));
            } else {
                $title = _("User Profile");
                $content = _("unknown user profile");
            }

            $h = new html(array(
                'content' => array(
                    $this->toolbar(),
                    new html("box.tpl", array(
                        'id' => "userprofile",
                        'class' => "first",
                        'icon' => "framework/cms/images/icon_users.gif",
                        'title' => $title,
                        'content' => $content,
                    )),
                )
            ), $this->html_options);
        }

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
