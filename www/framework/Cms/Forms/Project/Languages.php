<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Language Settings
 * Form for editing project languages
 */
class Languages extends Base
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
        $params['label'] = _("Save Languages");

        list($document, $this->dataNode) = \Depage\Xml\Document::getDocAndNode($params['dataNode']);

        $this->dataNodeXpath = new \DOMXPath($document);
        $this->dataNodeXpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");
        $this->dataNodeXpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        parent::__construct($name, $params);

        foreach ($this->getElements() as $element) {
            if (!empty($element->dataInfo)) {
                $nodes = $this->dataNodeXpath->evaluate($element->dataInfo);
                $element->setDefaultValue($nodes->item(0)->value);
            }
        }
    }
    // }}}
    // {{{ addChildElements()
    /**
     * @brief addChildElements
     *
     * @param mixed
     * @return void
     **/
    public function addChildElements()
    {
        $nodelist = $this->dataNodeXpath->query("//proj:language");

        $this->addHtml("<div class=\"sortable-fieldsets\">");
        foreach ($nodelist as $node) {
            $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

            $fs = $this->addFieldset("language-$nodeId", array(
                "label" => $node->getAttribute("name"),
            ));

            $fs->addText("name-$nodeId", array(
                "label" => _("Name"),
                "placeholder" => _("Language name"),
                "dataInfo" => "//proj:language[@db:id = '$nodeId']/@name",
            ));
            $fs->addText("shortname-$nodeId", array(
                "label" => _("Short name"),
                "placeholder" => _("Langugage Identifier"),
                "dataInfo" => "//proj:language[@db:id = '$nodeId']/@shortname",
            ));
        }
        $this->addHtml("</div>");

        $fs->addSingle("default", array(
            "label" => _("Select default languages"),
            "list" => array(
                "de",
                "en",
            ),
        ));
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
        foreach ($this->getElements() as $element) {
            if (!empty($element->dataInfo)) {
                $nodes = $this->dataNodeXpath->evaluate($element->dataInfo);
                $nodes->item(0)->value = $element->getValue();
            }
        }

        return $this->dataNode;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
