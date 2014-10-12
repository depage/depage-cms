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
    protected $htmlOptions = array();
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
        $this->htmlOptions = array(
            'template_path' => __DIR__ . "/tpl/",
            //'template_path' => "framework/cms/tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );

        $this->basetitle = \depage::getName() . " " . \depage::getVersion();

        // establish if the user is logged in
        if (empty($this->authUser)) {
            if ($this->autoEnforceAuth) {
                $this->authUser = $this->auth->enforce();
            } else {
                $this->authUser = $this->auth->enforceLazy();
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
            ), $this->htmlOptions);
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
            ), $this->htmlOptions);
        } else {
            $h = new html("toolbar_plain.tpl", array(
                'title' => $this->basetitle,
            ), $this->htmlOptions);
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
        ), $this->htmlOptions);

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
        ), $this->htmlOptions);

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

        return new html("box.tpl", array(
            'title' => _("Welcome"),
            'content' => $form,
        ), $this->htmlOptions);
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
            ), $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ users
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function users() {
        $h = "";
        if ($user = $this->auth->enforce()) {
            $users = \depage\Auth\User::loadActive($this->pdo);
            foreach ($users as $user) {
                $h .= $user->fullname . "<br>";
            }

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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
