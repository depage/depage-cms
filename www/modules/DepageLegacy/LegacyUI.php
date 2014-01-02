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
    protected $project = "depage";

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
        $import = new Import($this->project, $this->pdo, $this->cache);
        $value = $import->importProject("projects/{$this->project}/import/backup_full.xml");

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
                'project' => $this->project,
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
    public function test()
    {
        if ($user = $this->auth->enforce()) {
            $xmlInput = '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE ttdoc [ <!ENTITY nbsp "&amp;nbsp;"><!ENTITY auml "&amp;auml;"><!ENTITY ouml "&amp;ouml;"><!ENTITY uuml "&amp;uuml;"><!ENTITY Auml "&amp;Auml;"><!ENTITY Ouml "&amp;Ouml;"><!ENTITY Uuml "&amp;Uuml;"><!ENTITY mdash "&amp;mdash;"><!ENTITY ndash "&amp;ndash;"><!ENTITY copy "&amp;copy;"><!ENTITY euro "&amp;euro;"> ]><rpc:msg xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc"><rpc:func name="get_project"><rpc:param name="sid">0d85e60b91e4ccf301d705f605523d78</rpc:param><rpc:param name="wid" /><rpc:param name="project_name">depage</rpc:param></rpc:func></rpc:msg>';

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
        $this->log->log($xmlInput);

        $msgHandler = new RPC\Message(new RPC\CmsFuncs($this->project));

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
        if (count($pocket_updates) > 0) {
            //send_updates();
        }
        //$value = array_merge($value, $project->user->get_updates($project->user->sid));

        if (count($value) == 0) {
            $value[] = new RPC\Func('nothing', array('error' => 0));
        }

        return RPC\Message::create($value);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
