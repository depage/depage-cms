<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Variables Settings
 * Form for editing project variables
 */
class Variables extends Base
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
        $params['label'] = _("Save Variables");

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
        $nodelist = $this->dataNodeXpath->query("//proj:variable");

        $this->addHtml("<div class=\"sortable-fieldsets\">");
        foreach ($nodelist as $node) {
            $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

            $fs = $this->addFieldset("variable-$nodeId", array(
                "label" => $node->getAttribute("name"),
            ));

            $fs->addHtml("<div class=\"detail\">");
                $fs->addText("name-$nodeId", array(
                    "label" => _("Name"),
                    "placeholder" => _("Language name"),
                    "dataInfo" => "//proj:variable[@db:id = '$nodeId']/@name",
                    "validator" => "/[-_a-zA-Z0-9]+/",
                ));

                $fs->addText("localized-$nodeId-$lang", array(
                    "label" => _("Value"),
                    "placeholder" => _("Value"),
                    "dataInfo" => "//proj:variable[@db:id = '$nodeId']/@value",
                ));
            $fs->addHtml("</div>");
        }
        $this->addHtml("</div>");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
