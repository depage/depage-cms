<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Tag Settings
 * Form for editing project tags
 */
class Tags extends Base
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
        $params['label'] = _("Save Tags");

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
        $nodelist = $this->dataNodeXpath->query("//proj:tag");

        $this->addHtml("<div class=\"sortable-fieldsets\">");
        foreach ($nodelist as $node) {
            $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

            $fs = $this->addFieldset("tag-$nodeId", array(
                "label" => $node->getAttribute("name"),
            ));

            $fs->addHtml("<div class=\"detail\">");
                $fs->addText("name-$nodeId", array(
                    "label" => _("Name"),
                    "placeholder" => _("Language name"),
                    "dataInfo" => "//proj:tag[@db:id = '$nodeId']/@name",
                    "validator" => "/[-_a-zA-Z0-9]+/",
                ));

                foreach ($node->childNodes as $localized) {
                    //$langId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");
                    $lang = $localized->getAttribute("lang");

                    $fs->addText("localized-$nodeId-$lang", array(
                        "label" => $lang,
                        "placeholder" => _("Localized name"),
                        "dataInfo" => "//proj:tag[@db:id = '$nodeId']/localized[@lang = '$lang']",
                    ));
                }
            $fs->addHtml("</div>");
        }
        $this->addHtml("</div>");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
