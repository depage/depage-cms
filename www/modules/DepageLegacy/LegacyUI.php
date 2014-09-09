<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace DepageLegacy;

use \html;

class LegacyUI extends \depage_ui
{
    protected $html_options = array();
    protected $basetitle = "";
    protected $autoEnforceAuth = false;
    protected $projectName = "depage";
    //protected $projectName = "klassehesse";
    protected $user;

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return array(
            'project/*/preview' => '\depage\cms\UI\Preview',
        );
        return array(
            'project/*' => '\depage\cms\ui_project',
            'project/*/tree/*' => '\depage\cms\ui_tree',
            'project/*/tree/*/fallback' => '\depage\cms\ui_socketfallback',
            'project/*/edit/*' => '\depage\cms\ui_edit',
        );
    }
    // }}}

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        // get cache instance
        $this->cache = \depage\cache\cache::factory("xmldb", array(
            'host' => "localhost",
        ));

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \depage\DB\PDO (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        /*
        ini_set("session.gc_probability", 1);
        ini_set("session.gc_divisor", 1);
         */

        // register session handler
        \depage\Session\SessionHandler::register($this->pdo);

        // get auth object
        $this->auth = \depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method, // method
            $this->options->auth->digestCompat // should we digest compatibility
        );

        // set html-options
        $this->html_options = array(
            'template_path' => __DIR__ . "/tpl/",
            //'template_path' => "framework/cms/tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );

        $this->basetitle = \depage::getName() . " " . \depage::getVersion();

        // establish if the user is logged in
        if (empty($this->auth_user)) {
            if ($this->autoEnforceAuth) {
                $this->auth_user = $this->auth->enforce();
            } else {
                $this->auth_user = $this->auth->enforceLazy();
            }
        }
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
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
        if ($user = $this->auth->enforceLazy()) {
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

    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound($function = "") {
        parent::notfound();

        $h = new html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'title' => "Error",
            'content' => new html(array(
                'content' => 'url not found' . $function,
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

        return $this->_package($h);
    }
    // }}}

    // {{{ login()
    /**
     * Login
     *
     * Displays and handles the user login:
     * - Redirects to HTTPS if required.
     * - Displays login form.
     * - Validates input and redirects authenticated users.
     *
     * @return string HTML - login template
     */
    public function login() {
        // redirect to https when not on a secure connection
        if ($this->options->auth->https && !(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off")) {
            $redirectTo = "";
            if (!empty($_REQUEST['redirectTo'])) {
                $redirectTo = "?redirectTo=" . urlencode($_REQUEST['redirectTo']);
            }
            depage::redirect(html::link("login/{$redirectTo}", "https"));
        }

        if ($user = $this->auth->enforce()) {
            if (!empty($_REQUEST['redirectTo'])) {
                \depage::redirect($redirectTo);
            } else {
                \depage::redirect(html::link("", "auto"));
            }
        }

        // not logged in
        $form = new Forms\Login("login", array(
            'validator' => array($this, '_validateLogin'),
            'redirectTo' => isset($_REQUEST['redirectTo']) ? $_REQUEST['redirectTo'] : '',
            'check' => true,
        ));

        $form->process();
        $form->validate(); // calls the onvalidate function

        return new html("box.tpl", array(
            'title' => _("Welcome"),
            'content' => $form,
        ), $this->html_options);
    }
    // }}}
    // {{{ logout()
    /**
     * Logout
     *
     * This is the logout end point:
     * - Clears session and redirects user to home page
     *
     * @return void
     */
    public function logout() {
        $this->auth->enforceLogout();
        \depage::redirect(html::link("", "http"));
    }
    // }}}
    // {{{ _validateLogin
    /**
     * Validate Login
     *
     * This is the validation handler sent to the depage\htmlform validation
     * to enable user authentication.
     *
     * @param array $values - form inputs
     *
     * @return user object or false - authentication state
     */
    public function _validateLogin($form, array $values) {
        $user = $this->auth->login($values['username'], $values['password']);

        if ($user) {
            // authenticated
            return (empty($user->confirm_id)) ? $user : false;
        }
        $input = $form->getElement('password');
        $input->valid = false;

        $this->log->log("login: wrong credentials");

        return false;
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
                    //$this->projects(),
                    //$this->users(),
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

    // {{{ import
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function import()
    {
        $import = new Import($this->projectName, $this->pdo, $this->cache);
        $value = $import->importProject("projects/{$this->projectName}/import/backup_full.xml");

        return $value;
    }
    // }}}
    // {{{ import-task
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function import_task()
    {
        $import = new Import($this->projectName, $this->pdo, $this->cache);
        $task = $import->addImportTask("import {$this->projectName}", "projects/{$this->projectName}/import/backup_full.xml");

        return "task added";
    }
    // }}}
    // {{{ flash
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function flash($page = "", $standalone = "true")
    {
        if ($user = $this->auth->enforce()) {
            // logged in
            $h = new html("flash.tpl", array(
                'project' => $this->projectName,
                'page' => $page,
                'standalone' => $standalone,
                'sid' => $_COOKIE[session_name()],
            ), $this->html_options);

            return $h;
        }
    }
    // }}}
    // {{{ rpc
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function rpc()
    {
        if ($user = $this->auth->enforce()) {
            $xmlInput = file_get_contents("php://input");
            //$this->log->log($xmlInput);

            return $this->handleRpc($xmlInput);
        }
    }
    // }}}
    // {{{ test
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function test($type = "start-project")
    {
        if ($user = $this->auth->enforce()) {
            if ($type == "get-config") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_config" /></rpc:msg>';
            } elseif ($type == "register-window") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="register_window"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">main</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "start-project") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">settings</rpc:param></rpc:func>,<rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">colors</rpc:param></rpc:func>,<rpc:func name="get_tree"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">tpl_newnodes</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "tree-pages") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="wid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">pages</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "tree-files") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="wid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">files</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "tree-files-dir") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_prop"><rpc:param name="sid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="wid">7b9a38377edb659e54ed354b902fe9f9</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">/projects/alonylightsonoff/</rpc:param><rpc:param name="file_type" /><rpc:param name="type">files</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "tree-pagedata") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="wid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="id">10</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "save-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="save_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="data"><edit:text_headline lang="en" db:id="1950"><p>Design [dɪˈzaɪn]fg</p></edit:text_headline></rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "move-node-before") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="move_node_before"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">1956</rpc:param><rpc:param name="target_id">1949</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "delete-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="delete_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">6981</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "duplicate-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="duplicate_node"><rpc:param name="sid">eb3a565e6006cbd4a8ed677b6dd26452</rpc:param><rpc:param name="wid">eb3a565e6006cbd4a8ed677b6dd26452</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">14235</rpc:param><rpc:param name="new_name">Textblock (copy)</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "add-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="keepAlive"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func>,<rpc:func name="add_node"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="target_id">618</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="node_type">
    <sec:text name="Headline">
        <edit:text_headline lang=""><p>Headline</p></edit:text_headline>
    </sec:text>

    </rpc:param><rpc:param name="new_name" /></rpc:func></rpc:msg>';
            } elseif ($type == "set-page-colorscheme") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_colorscheme"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">210</rpc:param><rpc:param name="colorscheme">cyan</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "set-page-navigations") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_navigations"><rpc:param name="sid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="wid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">66</rpc:param><rpc:param name="navigations"><pg_navigation nav_featured="true" nav_tag_templates="false" nav_tag_cms="false" nav_tag_media="false" nav_tag_development="false" nav_tag_design="false" nav_tag_concept="false" nav_blog="false" nav_home="false" nav_hidden="false" nav_layout_include="false" nav_atom="false" nav_shortnews="false" /></rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "set-page-fileoptions") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_file_options"><rpc:param name="sid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="wid">1ee10f1808e8465248ea3653c7feda07</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">66</rpc:param><rpc:param name="multilang">true</rpc:param><rpc:param name="file_name" /><rpc:param name="file_type">php</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "rename-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="rename_node"><rpc:param name="sid">83b6cbfc1937d90652bbae89cdc1ac40</rpc:param><rpc:param name="wid">83b6cbfc1937d90652bbae89cdc1ac40</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">560</rpc:param><rpc:param name="new_name">Intro 1</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "get-imageprop") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_imageProp"><rpc:param name="sid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="wid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="filepath">/projects/depageforms/</rpc:param><rpc:param name="filename">icon-help-depageforms.png</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "get-videoprop") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_imageProp"><rpc:param name="sid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="wid">48d95d889856b61c5e4aa8cd4aa1b28d</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="filepath">/projects/alonylightsonoff/</rpc:param><rpc:param name="filename">lights_on_off.wmv</rpc:param></rpc:func></rpc:msg>';
            }

            return $this->handleRpc($xmlInput);
        }
    }
    // }}}
    // {{{ benchmark_cache():
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function benchmark_cache()
    {
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
        $caches = array();
        $xmldbs = array();
        $xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
        ));
        $docs = array_keys($xmldb->getDocuments());

        $caches['uncached'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "uncached",
        ));
        $caches['file'] = \depage\cache\cache::factory("xmldb", array(
        ));
        $caches['memcached'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "memcached",
            'host' => "localhost",
        ));
        $caches['memcache'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "memcache",
            'host' => "localhost",
        ));
        /*
        $caches['memcached-twins'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "memcached",
            'host' => "twins.local",
        ));
        $caches['memcache-twins'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "memcache",
            'host' => "twins.local",
        ));
         */
        $caches['redis'] = \depage\cache\cache::factory("xmldb", array(
            'disposition' => "redis",
            'host' => "localhost",
        ));

        foreach ($caches as $key => $cache) {
            $xmldbs[$key] = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, $cache, array(
                'pathXMLtemplate' => $this->xmlPath,
            ));
        }

        foreach ($xmldbs as $key => $xmldb) {
            echo($key . "\n<br>");

            foreach($docs as $doc) {
                // cache first
                $xmldb->getDocXml($doc);
            }
            $time_start = microtime(true);
            for ($i = 0; $i < 10; $i++) {
                foreach($docs as $doc) {
                    $xmldb->getDocXml($doc);
                }
            }
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            echo($time . "\n<br>");
        }

    }
    // }}}
    // {{{ benchmark_mediainfo():
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function benchmark_mediainfo()
    {
        $mediainfos = array(
            "uncached" => new \depage\media\mediainfo(),
            "cached" => new \depage\media\mediainfo(array(
                'cache' => \Depage\Cache\Cache::factory("mediainfo"),
            )),
        );
        $files = glob("projects/depage/lib/projects/*/*");

        foreach ($mediainfos as $key => $mediainfo) {
            echo($key . "\n<br>");

            foreach($files as $file) {
                // cache first
                $mediainfo->getInfo($file);
            }
            $time_start = microtime(true);
            for ($i = 0; $i < 10; $i++) {
                foreach($files as $file) {
                    $mediainfo->getInfo($file);
                }
            }
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            echo($time . "\n<br>");
        }

    }
    // }}}
    // {{{ handleRpc
    /**
     * function to show error messages
     *
     * @return  null
     */
    protected function handleRpc($xmlInput)
    {
        if (!preg_match("/keepAlive/", $xmlInput)) {
            $this->log->log($xmlInput);
        }

        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        $xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
            'userId' => $this->auth_user->id,
        ));

        $funcHandler = new RPC\CmsFuncs($this->projectName, $this->pdo, $xmldb);
        $msgHandler = new RPC\Message($funcHandler);

        //call
        $funcs = $msgHandler->parse($xmlInput);

        $results = array();
        foreach ($funcs as $func) {
            $func->add_args(array('ip' => $_SERVER['REMOTE_ADDR']));
            $tempval = $func->call();
            if (is_a($tempval, 'DepageLegacy\\RPC\\Func')) {
                $results[] = $tempval;
            }
        }
        /*
        if (count($pocket_updates) > 0) {
            send_updates();
        }
         */
        //$results = array_merge($results, $project->user->get_updates($project->user->sid));
        $results = array_merge($results, $funcHandler->getCallbacks());

        if (count($results) == 0) {
            $results[] = new RPC\Func('nothing', array('error' => 0));
        }

        return RPC\Message::create($results);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
