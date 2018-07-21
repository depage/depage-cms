<?php
/**
 * @file    framework/Cms/Ui/Project.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class ColorSchemes extends Base
{
    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = $this->getProject($this->projectName);
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->manager();
    }
    // }}}
    // {{{ manager()
    function manager() {
        $path = rawurldecode($path);

        // construct template
        $hLib = new Html("colorschemes.tpl", [
            'projectName' => $this->project->name,
            'tree' => $this->tree(),
        ], $this->htmlOptions);

        $h = new Html([
            'content' => [
                $hLib,
            ],
        ]);

        return $h;
    }
    // }}}
    // {{{ tree()
    /**
     * @brief tree
     *
     * @param mixed
     * @return void
     **/
    public function tree()
    {
        $treeUrl = "project/{$this->projectName}/tree/colors/";
        $uiTree = Tree::_factoryAndInit($this->conf, [
            'urlSubArgs' => [
                $this->projectName,
                "colors",
            ],
            'urlPath' => $treeUrl,
            'pdo' => $this->pdo,
            'auth' => $this->auth,
            'xmldbCache' => $this->xmldbCache,
            'htmlOptions' => $this->htmlOptions,
        ]);

        return $uiTree->tree();
    }
    // }}}
    // {{{ edit()
    /**
     * @brief colors
     *
     * @param mixed $path = "/"
     * @return void
     **/
    public function edit($nodeId)
    {
        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("colors");
        $xml = $doc->getSubdocByNodeId($nodeId);

        return new Html("colorListing.tpl", [
            'colorNodes' => $xml->documentElement->getElementsByTagName("color"),
        ], $this->htmlOptions);
    }
    // }}}

    // {{{ renameColor()
    /**
     * @brief renameColor
     *
     * @param mixed $nodeId, $name
     * @return void
     **/
    public function renameColor()
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("colors");
        $xml = $doc->getXML();

        $doc->beginTransactionAltering();
        $ids = $this->getColorIds($xml, $id);

        foreach ($ids as $id) {
            $doc->setAttribute($id, "name", $name);
        }
        $doc->endTransaction();

        return new \Depage\Json\Json(array("status" => true));
    }
    // }}}
    // {{{ getColorIdsByNode()
    /**
     * @brief getColorIdsByNode
     *
     * @param mixed $colorNode
     * @return void
     **/
    protected function getColorIds($xml, $id)
    {
        $ids = [];

        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("//color[@db:id = '$id']");
        if ($nodelist->length == 0) {
            return $ids;
        }
        $colorNode = $nodelist->item(0);

        if ($colorNode->parentNode->getAttribute("db:name") == 'tree_name_color_global') {
            $ids[] = $colorNode->getAttribute("db:id");
        } else {
            $name = $colorNode->getAttribute("name");
            $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[@name != 'tree_name_color_global']/color[@name = '$name']/@db:id");
        }
        foreach ($nodelist as $colorNodeId) {
            $ids[] = $colorNodeId->nodeValue;
        }

        return $ids;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
