<?php

namespace Depage\Cms\Forms;

/**
 * brief Language Settings
 * Form for editing project languages
 */
class XmlForm extends \Depage\HtmlForm\HtmlForm
{
    /**
     * @brief dataNode
     **/
    protected $dataNode = null;

    /**
     * @brief dataNodeXpath
     **/
    protected $dataNodeXpath = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        if (isset($params['dataNode'])) {
            list($this->dataDocument, $this->dataNode) = \Depage\Xml\Document::getDocAndNode($params['dataNode']);

            $this->dataNodeXpath = new \DOMXPath($this->dataDocument);
            $this->dataNodeXpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");
            $this->dataNodeXpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");
        }

        parent::__construct($name, $params);

        $this->setDefaultValuesXml();
    }
    // }}}
    // {{{ setDefaultValues()
    /**
     * @brief setDefaultValues
     *
     * @param mixed
     * @return void
     **/
    public function setDefaultValuesXml()
    {
        if (!isset($this->dataNode)) {
            return;
        }

        foreach ($this->getElements() as $element) {
            if (!empty($element->dataInfo)) {
                $nodes = $this->dataNodeXpath->query($element->dataInfo);
                $value = "";

                // @todo throw warning if nodelist is empty?
                if ($nodes->length > 0) {
                    $node = $nodes->item(0);

                    if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                        $value = $node->value == "true" ? true : false;
                    } else if ($element instanceof \Depage\HtmlForm\Elements\Richtext) {
                        $value = "";

                        foreach ($node->childNodes as $n) {
                            \Depage\XmlDb\Document::removeNodeAttr($n, new \Depage\XmlDb\XmlNs('db', 'http://cms.depagecms.net/ns/database'), "id");

                            $value .= $node->ownerDocument->saveHTML($n) . "\n";
                        }

                        if ($node->nodeName == "edit:table") {
                            $value = "<table>$value</table>";
                        }
                    } else {
                        $value = $node->nodeValue;
                    }
                }

                $element->setDefaultValue($value);
            }
        }
    }
    // }}}
    // {{{ getValuesXml()
    /**
     * @brief getValuesXml
     *
     * @return void
     **/
    public function getValuesXml()
    {
        if (!isset($this->dataNode)) {
            return;
        }
        foreach ($this->getElements() as $element) {
            if (empty($element->dataInfo)) {
                continue;
            }

            $node = $this->dataNodeXpath->query($element->dataInfo)->item(0);

            if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                $node->nodeValue = $element->getValue() === true ? "true" : "false";
            } elseif ($element instanceof \Depage\HtmlForm\Elements\Richtext) {
                $root = $element->getValue()->documentElement;

                while ($node->lastChild != null) {
                    $node->removeChild($node->lastChild);
                }
                if ($node->nodeName == "edit:table") {
                    // @todo check if this is stable in all browsers
                    $root = $root->getElementsByTagName("tbody")->item(0);
                }
                foreach ($root->childNodes as $n) {
                    $copy = $this->dataDocument->importNode($n, true);
                    $node->appendChild($copy);
                }
            } else {
                $node->nodeValue = $element->getValue();
            }
        }

        return $this->dataNode;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
