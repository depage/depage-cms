<?php
/**
 * @file    framework/Cms/Ui/Edit.php
 *
 * depage cms edit module
 *
 *
 * copyright (c) 2011 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\UI;

use \Depage\Html\Html;

class DocProperties extends Base
{
    // {{{ _init()
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->nodeId = $this->urlSubArgs[1];
        }

        // get xmldb instance
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
        $this->xmldb = new \Depage\XmlDb\XmlDb($this->prefix, $this->pdo, $this->xmldbCache, [
            "edit:text_headline",
            "edit:text_formatted",
        ]);
    }
    // }}}
    // {{{ package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "Depage\Html\Html")) {
            // pack into body html
            $output = new Html("html.tpl", [
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ], $this->htmlOptions);
        }

        return $output;
    }
    // }}}

    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        $this->auth->enforce();

        $h = "";
        $doc = $this->xmldb->getDocByNodeId($this->nodeId);
        $xml = $doc->getXml();

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        list($node) = $xpath->query("//*[@db:id = '{$this->nodeId}']");

        $form = new \Depage\Cms\Forms\XmlForm("xmldata_{$this->nodeId}", [
            'dataNode' => $node,
        ]);

        foreach($node->childNodes as $n) {
            $callback = $this->getCallbackForNode($n);

            $h .= $func . "<br>";
            if ($callback) {
                $this->$callback($form, $n);
            }
        }
        $form->setDefaultValuesXml();

        //$h .= htmlentities($xml->saveXML($node));
        $h .= $form;

        $output = new Html([
            'title' => "edit",
            'content' => $h,
        ], $this->htmlOptions);

        return $output;
    }
    // }}}

    // {{{ getCallbackForNode()
    /**
     * @brief getCallbackForNode
     *
     * @param mixed $node
     * @return void
     **/
    protected function getCallbackForNode($node)
    {
        $f = str_replace(":", "_", $node->nodeName);
        $parts = explode("_", $f);

        for ($i = 0; $i < count($parts); $i++) {
            $parts[$i] = ucfirst($parts[$i]);
        }
        $callback = "add" . implode($parts);

        if (is_callable([$this, $callback])) {
            return $callback;
        }

        echo $callback . "<br>";

        return false;
    }
    // }}}
    // {{{ getLabelForNode()
    /**
     * @brief getLabelForNode
     *
     * @param mixed $node
     * @return void
     **/
    protected function getLabelForNode($node)
    {
        $label = $node->getAttribute("name");

        $lang = $node->getAttribute("lang");
        if ($lang) {
            $label .= " " . $lang;
        }

        return $label;
    }
    // }}}

    // {{{ addEditTextSingleline()
    /**
     * @brief addEditTextSingleline
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditTextSingleline($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addText("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addEditTextHeadline()
    /**
     * @brief addEditTextHeadline
     *
     * @param mixed $
     * @return void
     **/
    protected function addEditTextHeadline($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addTextarea("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node),
            'dataInfo' => "//*[@db:id = '$nodeId']/*",
        ]);
    }
    // }}}
    // {{{ addEditTextFormatted()
    /**
     * @brief addEditTextFormatted
     *
     * @param mixed $
     * @return void
     **/
    protected function addEditTextFormatted($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addRichtext("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node),
            'dataInfo' => "//*[@db:id = '$nodeId']/*",
        ]);
    }
    // }}}
}
/* vim:set ft=php sts=4 fdm=marker et : */
