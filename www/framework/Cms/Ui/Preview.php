<?php
/**
 * @file    framework/cms/UI/Preview.php
 *
 * preview ui handler
 *
 *
 * copyright (c) 2013-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace depage\cms\UI;

use \Depage\Html\Html;

class Preview extends \Depage\Depage\Ui\Base
{
    protected $htmlOptions = array();
    protected $basetitle = "";
    protected $previewType = "dev";
    protected $projectName = "";
    protected $template = "";
    protected $lang = "";
    protected $urlsByPageId = array();
    protected $pageIdByUrl = array();
    public $routeThroughIndex = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \Depage\Db\Pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        // get auth object
        $this->auth = \depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->htmlOptions = array(
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );
        $this->basetitle = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion();
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        return $output;
    }
    // }}}
    // {{{ _send_time
    protected function _send_time($time, $content = null) {
        echo("<!-- $time sec -->");
    }
    // }}}

    // {{{ index
    /**
     * function to route all previews through
     *
     * @return  null
     */
    public function index()
    {
        $args = func_get_args();

        // get parameters
        $this->projectName = $this->urlSubArgs[0];
        $this->template = array_shift($args);
        $this->previewType = array_shift($args);

        $lang = array_shift($args);

        $urlPath = implode("/", $args);

        return $this->preview($urlPath, $lang);
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

        $h = new Html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'content' => new Html(array(
                'content' => $content,
            )),
        ), $this->htmlOptions);

        return $this->_package($h);
    }
    // }}}

    // {{{ preview
    /**
     * @return  null
     */
    protected function preview($urlPath, $lang)
    {
        $transformer = \depage\Transformer\Transformer::factory($this->previewType, $this->pdo, $this->projectName, $this->template);
        $html = $transformer->display("/" . $urlPath, $lang);

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
