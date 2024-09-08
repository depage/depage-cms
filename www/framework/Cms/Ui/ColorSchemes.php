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
    // {{{ variables
    protected $project;
    protected $projectName;
    // }}}

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        }
        $this->project = $this->getProject($this->projectName);

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
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

        // sort colors by name
        $colorNodes = iterator_to_array($xml->documentElement->getElementsByTagName("color"));
        usort($colorNodes, function($a, $b) {
            return strcmp($a->getAttribute("name"), $b->getAttribute("name"));
        });

        return new Html("colorListing.tpl", [
            'colorNodes' => $colorNodes,
            'colorschemeId' => $nodeId,
            'type' => $xml->documentElement->getAttribute("db:name") == "tree_name_color_global" ? "global" : "scheme",
            'palette' => $this->project->getColorPalette(),
        ], $this->htmlOptions);
    }
    // }}}

    // {{{ addColor()
    /**
     * @brief addColor
     *
     * @param mixed
     * @return void
     **/
    public function addColor()
    {
        $colorType = filter_input(INPUT_POST, 'colorType', FILTER_SANITIZE_STRING);

        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("colors");
        $xml = $doc->getXML();
        $xpath = new \DOMXPath($xml);

        if ($colorType == "global") {
            $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[@db:name = 'tree_name_color_global']/@db:id");
        } else if ($colorType == "scheme") {
            $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[not(@db:name = 'tree_name_color_global')]/@db:id");
        }
        $doc->beginTransactionAltering();
        $colorNode = $xml->createElement("color");
        $colorNode->setAttribute("name", _("unnamed_color"));
        $colorNode->setAttribute("value", "#000000");

        foreach ($nodelist as $schemeId) {
            $success = $doc->addNode($colorNode, $schemeId->nodeValue);
        }
        $doc->endTransaction();

        return new \Depage\Json\Json(array("status" => true));
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

        if (empty($id) || empty($name)) {
            return new \Depage\Json\Json(array("status" => false));
        }

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
    // {{{ deleteColor()
    /**
     * @brief deleteColor
     *
     * @param mixed $nodeId, $name
     * @return void
     **/
    public function deleteColor()
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

        if (empty($id)) {
            return new \Depage\Json\Json(array("status" => false));
        }

        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("colors");
        $xml = $doc->getXML();

        $doc->beginTransactionAltering();
        $ids = $this->getColorIds($xml, $id);

        foreach ($ids as $id) {
            $doc->deleteNode($id);
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
            $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[not(@db:name = 'tree_name_color_global')]/color[@name = '$name']/@db:id");
        }
        foreach ($nodelist as $colorNodeId) {
            $ids[] = $colorNodeId->nodeValue;
        }

        return $ids;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
