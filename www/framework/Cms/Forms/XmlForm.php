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

        if (isset($params['dataNode'])) {
            foreach ($this->getElements() as $element) {
                if (!empty($element->dataInfo)) {
                    $nodes = $this->dataNodeXpath->evaluate($element->dataInfo);
                    if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                        $element->setDefaultValue($nodes->item(0)->value == "true" ? true : false);
                    } else {
                        $element->setDefaultValue($nodes->item(0)->value);
                    }
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
                    $nodes = $this->dataNodeXpath->evaluate($element->dataInfo);
                    if ($element instanceof \Depage\HtmlForm\Elements\Boolean) {
                        $nodes->item(0)->value = $element->getValue() === true ? "true" : "false";
                    } else {
                        $nodes->item(0)->value = $element->getValue();
                    }
                }
            }
        }

        return $this->dataNode;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
