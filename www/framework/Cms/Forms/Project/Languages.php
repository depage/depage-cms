<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Language Settings
 * Form for editing project languages
 */
class Languages extends Base
{
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

        parent::__construct($name, $params);
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

        $this->addSingle("default", array(
            "label" => _("Select default languages"),
            "list" => array(
                "de",
                "en",
            ),
        ));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
