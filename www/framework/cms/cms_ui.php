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

    // {{{ constructor
    public function __construct() {
        $this->html_options = array(
            'template_path' => __DIR__ . "/tpl/",
            'clean' => "space",
        );
        $this->basetitle = depage::getName() . " " . depage::getVersion();
    }
    // }}}
    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        $cp = new cms_project();
        $projects = $cp->get_projects();

        $h = new html("html.tpl", array(
            'title' => $this->basetitle,
            'content' => array(
                new html("projectlist.tpl", array(
                    'projects' => $projects,
                )),
                //new html("userlist.tpl"),
            )
        ), $this->html_options);

        echo($h);
    }
    // }}}
    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound() {
        $h = new html("html.tpl", array(
            'title' => $this->basetitle,
            'content' => 'notfound',
        ), $this->html_options);

        echo($h);
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
