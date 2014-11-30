<?php
/**
 * @file    framework/Cms/Ui/Main.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Main extends Base {
    protected $autoEnforceAuth = false;

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return array(
            'project/*' => '\Depage\Cms\Ui\Project',
            'project/*/preview' => '\Depage\Cms\Ui\Preview',
            'project/*/flash' => '\Depage\Cms\Ui\Flash',
            //'project/*/tree/*' => '\Depage\Cms\Ui\Tree',
            //'project/*/tree/*/fallback' => '\Depage\Cms\Ui\SocketFallback',
            //'project/*/edit/*' => '\Depage\Cms\Ui\Edit',
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
            $h = new Html(array(
                'content' => array(
                    $this->toolbar(),
                    $this->projects(),
                    $this->users(),
                ),
            ));
        } else {
            // not logged in
            $h = new Html(array(
                'content' => array(
                    $this->toolbar(),
                    'content' => new Html("welcome.tpl", array(
                        'title' => "Welcome to\n depage::cms ",
                        'login' => "Login",
                        'login_link' => "login/",
                    )),
                )
            ), $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        } else {
            // not logged in
            $form = new \Depage\HtmlForm\HtmlForm("login", array(
                'label' => _("Login"),
                'validator' => function($form, $values) {
                    return (bool) $this->auth->login($values['name'], $values['pass']);
                },
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

                $h = new Html("box.tpl", array(
                    'id' => "login",
                    'icon' => "framework/cms/images/icon_login.gif",
                    'class' => "first",
                    'title' => "Login",
                    'content' => array(
                        $error,
                        $form,
                    ),
                ), $this->htmlOptions);

                return $h;
            }
        }
    }
    // }}}
    // {{{ logout
    public function logout($action = null) {
        //if ($action[0] == "now") {
            $this->auth->enforceLogout();
        //}

        $h = new Html("box.tpl", array(
            'id' => "logout",
            'class' => "first",
            'title' => "Bye bye!",
            'content' => new Html("logout.tpl", array(
                'content' => "Thank you for using depage::cms. ",
                'relogin1' => "You can relogin ",
                'relogin2' => "here",
                'relogin_link' => "login/",
            )),
        ), $this->htmlOptions);

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
        $cp = new \Depage\Cms\Project($this->pdo);
        $projects = $cp->getProjects();

        // construct template
        $h = new Html("box.tpl", array(
            'id' => "projects",
            'icon' => "framework/Cms/images/icon_projects.gif",
            'class' => "first",
            'title' => "Projects",
            'content' => new Html("projectlist.tpl", array(
                'projects' => $projects,
            )),
        ), $this->htmlOptions);

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

        $h = new Html("box.tpl", array(
            'id' => "users",
            'icon' => "framework/cms/images/icon_users.gif",
            'title' => "Users",
            'content' => new Html("userlist.tpl", array(
                'title' => $this->basetitle,
                'users' => $users,
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ add tables
    public function add_tables()
    {
        if ($this->authUser = $this->auth->enforce()) {
            $schema = new \depage\DB\Schema($this->pdo);

            $schema->load("framework/Auth/Sql/*.sql");
            $schema->setReplace(function ($tableName) {
                return "test_" . $tableName;
            });

            $schema->update();
        }

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
