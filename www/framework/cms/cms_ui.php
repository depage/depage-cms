<?php
/**
 * @file    framework/cms/cms_ui.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class cms_ui extends depage_ui {
    protected $html_options = array();
    protected $basetitle = "";
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        "env" => "development",
    );

    // {{{ constructor
    public function __construct($options = NULL) {
        parent::__construct($options);

        // get database instance
        $this->pdo = new db_pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );
        // enable exceptions for sql-queries
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // get auth object
        $this->auth = auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->html_options = array(
            'template_path' => __DIR__ . "/tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );
        $this->basetitle = depage::getName() . " " . depage::getVersion();
    }
    // }}}
    // {{{ package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "html")) {
            // pack into body html
            $output = new html("html.tpl", array(
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ), $this->html_options);
        }

        return $output;
    }
    // }}}
    
    // {{{ toolbar
    protected function toolbar() {
        if ($user = $this->auth->enforce_lazy()) {
            $h = new html("toolbar_main.tpl", array(
                'title' => $this->basetitle,
                'username' => $user->name,
            ), $this->html_options);
        } else {
            $h = new html("toolbar_plain.tpl", array(
                'title' => $this->basetitle,
            ), $this->html_options);
        }

        return $h;
    }
    // }}}
    
    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        if ($this->auth->enforce_lazy()) {
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
    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound() {
        $h = new html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'title' => "Error",
            'content' => new html(array(
                'content' => 'url not found',
            )),
        ), $this->html_options);

        return $h;
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env) {
        $content = parent::error($error, $env);

        $h = new html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'content' => new html(array(
                'content' => $content,
            )),
        ), $this->html_options);

        return $h;
    }
    // }}}
    
    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            $this->redirect(DEPAGE_BASE);
        } else {
            // not logged in
            $form = new depage\htmlform\htmlform("login", array(
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
    public function validate_login($values) {
        return (bool) $this->auth->login($values['name'], $values['pass']);
    }
    // }}}
    // {{{ logout
    public function logout($action) {
        //if ($action[0] == "now") {
            $this->auth->enforce_logout();
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
        $cp = new cms_project($this->pdo);
        $projects = $cp->get_projects();

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

        $users = $this->auth->get_active_users();

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
    public function user($username) {
        if ($user = $this->auth->enforce()) {
            $h = new html(array(
                'content' => array(
                    $this->toolbar(),
                    new html("box.tpl", array(
                        'id' => "userprofile",
                        'class' => "first",
                        'icon' => "framework/cms/images/icon_users.gif",
                        'title' => "Profile: {$user->fullname}",
                        'content' => new html("userprofile_edit.tpl", array(
                            'title' => $this->basetitle,
                            'user' => $user,
                        )),
                    )),
                )
            ), $this->html_options);
        }

        return $h;
    }
    // }}}
    
    // {{{ blub
    public function blub($param) {
        $h = new html("html.tpl", array(
            'title' => $this->basetitle,
            'content' => "blub" . $param[0],
        ), $this->html_options);

        echo($h);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
