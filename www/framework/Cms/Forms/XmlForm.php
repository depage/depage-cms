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
            list($document, $this->dataNode) = \Depage\Xml\Document::getDocAndNode($params['dataNode']);

            $this->dataNodeXpath = new \DOMXPath($document);
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
        if (isset($this->dataNode)) {
            foreach ($this->getElements() as $element) {
                if (!empty($element->dataInfo)) {
                    $nodes = $this->dataNodeXpath->query($element->dataInfo);
                    $value = "";

                    // @todo throw warning if nodelist is empty?
                    if ($nodes->length > 0) {
                        $node = $nodes->item(0);

                        // @todo add textarea
                        if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                            $value = $node->value == "true" ? true : false;
                        } elseif ($element instanceof \Depage\HtmlForm\Elements\Richtext) {
                            $value = "";

                            foreach ($node->childNodes as $n) {
                                $value .= $node->ownerDocument->saveHTML($n) . "\n";
                            }
                        } else {
                            $value = $node->nodeValue;
                        }
                    }

                    $element->setDefaultValue($value);
                }
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
        if (isset($this->dataNode)) {
            foreach ($this->getElements() as $element) {
                if (!empty($element->dataInfo)) {
                    $node = $this->dataNodeXpath->query($element->dataInfo)->item(0);

                    // @todo add textarea
                    if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                        $node->nodeValue = $element->getValue() === true ? "true" : "false";
                    } else {
                        $node->nodeValue = $element->getValue();
                    }
                }
            }
        }

        return $this->dataNode;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
