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
    protected $autoEnforceAuth = true;
    protected $projectName = "depage";

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return array();
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
            //'disposition' => "memory",
            //'disposition' => "uncached",
            'host' => "twins.local",
        ));

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \db_pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        // get auth object
        $this->auth = \auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
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
                $this->auth_user = $this->auth->enforce_lazy();
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
            } elseif ($type == "tree-pagedata") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_tree"><rpc:param name="sid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="wid">bf71afc29a363d08c8b9d75664670392</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="id">71</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "save-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="save_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="data"><edit:text_headline lang="en" db:id="1950"><p>Design [dɪˈzaɪn]fg</p></edit:text_headline></rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "move-node-before") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="move_node_before"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">1956</rpc:param><rpc:param name="target_id">1949</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "delete-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="delete_node"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">6981</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func>,<rpc:func name="keepAlive"><rpc:param name="sid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="wid">cec525be1504a84bc86503de0d690780</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';
            } elseif ($type == "add-node") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="keepAlive"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param></rpc:func>,<rpc:func name="add_node"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="target_id">618</rpc:param><rpc:param name="type">page_data</rpc:param><rpc:param name="node_type">
    <sec:text name="Headline">
        <edit:text_headline lang=""><p>Headline</p></edit:text_headline>
    </sec:text>
        
    </rpc:param><rpc:param name="new_name" /></rpc:func></rpc:msg>';
            } elseif ($type == "set-page-colorscheme") {
                $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="set_page_colorscheme"><rpc:param name="sid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="wid">9adaf9361a3c631ae3ca9ccf27176bed</rpc:param><rpc:param name="project_name">depage</rpc:param><rpc:param name="id">210</rpc:param><rpc:param name="colorscheme">cyan</rpc:param><rpc:param name="type">page_data</rpc:param></rpc:func></rpc:msg>';
            }

            return $this->handleRpc($xmlInput);
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

        $xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, \depage\cache\cache::factory("xmldb"), array(
            'pathXMLtemplate' => $this->xmlPath,
        ));

        $funcHandler = new RPC\CmsFuncs($this->projectName, $this->pdo, $xmldb);
        $msgHandler = new RPC\Message($funcHandler);

        //call
        $funcs = $msgHandler->parse($xmlInput);

        $value = array();
        foreach ($funcs as $func) {
            $func->add_args(array('ip' => $_SERVER['REMOTE_ADDR']));
            $tempval = $func->call();
            if (is_a($tempval, 'DepageLegacy\\RPC\\Func')) {
                $value[] = $tempval;
            }
        }
        /*
        if (count($pocket_updates) > 0) {
            send_updates();
        }
         */
        //$value = array_merge($value, $project->user->get_updates($project->user->sid));

        if (count($value) == 0) {
            $value[] = new RPC\Func('nothing', array('error' => 0));
        }

        return RPC\Message::create($value);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
