<?php
/**
 * @file    framework/Cms/Ui/Project.php
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

class Project extends Base
{
    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            // @todo test with Project class
            throw new \Exception("no project");
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->edit();
    }
    // }}}
    // {{{ edit()
    function edit() {
        // construct template
        $hProject = new Html("flashedit.tpl", array(
            'flashUrl' => "project/{$this->projectName}/flash/flash/false",
            'previewUrl' => "project/{$this->projectName}/preview/html/noncached/",
        ), $this->htmlOptions);

        $h = new Html(array(
            'content' => array(
                $this->toolbar(),
                $hProject,
            ),
        ));

        return $h;
    }
    // }}}
    // {{{ jsedit()
    function jsedit() {
        // cms tree
        $tree = Tree::_factoryAndInit($this->options, array(
            'pdo' => $this->pdo,
            'projectName' => $this->projectName,
        ));

        // construct template
        $hProject = new Html("projectmain.tpl", array(
            'tree_pages' => $tree->tree("pages"),
            'tree_document' => $tree->tree("testpage"),
        ), $this->htmlOptions);

        $h = new Html(array(
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
