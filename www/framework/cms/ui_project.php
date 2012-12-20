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

class ui_project extends ui_base {
    protected $autoEnforceAuth = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->project = $this->urlSubArgs[0];
    }
    // }}}
    
    // {{{ index()
    function index() {
        // cms tree
        $tree = new \cms_jstree($this->options);

        // get data
        $cp = new project($this->pdo);
        $projects = $cp->getProjects();

        // construct template
        $hProject = new html("projectmain.tpl", array(
            'tree_pages' => $tree->index("pages"),
            'tree_document' => $tree->index("testpage"),
        ), $this->html_options);

        $h = new html(array(
            'content' => array(
                $this->toolbar(),
                $hProject,
            ),
        ));

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
