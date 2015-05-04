<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Publish Settings
 * Form for editing project publish settings
 */
class Publish extends Base
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
        $params['label'] = _("Save Publish Settings");

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
        $nodelist = $this->dataNodeXpath->query("//proj:publish_folder");

        $this->addHtml("<div class=\"sortable-fieldsets\">");
        foreach ($nodelist as $node) {
            $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

            $fs = $this->addFieldset("publish-$nodeId", array(
                "label" => $node->getAttribute("name"),
            ));

            $fs->addText("name-$nodeId", array(
                "label" => _("Name"),
                "placeholder" => _("Name of publishing target"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@name",
            ));
            $fs->addText("output_folder-$nodeId", array(
                "label" => _("Output folder"),
                "placeholder" => _("URL, where to publish project to"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@output_folder",
            ));
            $fs->addUrl("baseurl-$nodeId", array(
                "label" => _("Base Url"),
                "placeholder" => _("Base URL of publish target"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@baseurl",
            ));
            $fs->addText("output_user-$nodeId", array(
                "label" => _("Username"),
                "placeholder" => _("Username"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@output_user",
            ));
            $fs->addPassword("output_password-$nodeId", array(
                "label" => _("Password"),
                "placeholder" => _("Password"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@output_pass",
            ));
            $fs->addSingle("template_set-$nodeId", array(
                "label" => _("Template Set"),
                "list" => array(),
                "skin" => "select",
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@template_set",
            ));
            $fs->addBoolean("mod_rewrite-$nodeId", array(
                "label" => _("Server supports mod rewrite"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@mod_rewrite",
            ));
            $fs->addBoolean("default-$nodeId", array(
                "label" => _("Publish to this target as default"),
                "dataInfo" => "//proj:publish_folder[@db:id = '$nodeId']/@default",
            ));
        }
        $this->addHtml("</div>");
    }
    // }}}
    // {{{ onValidate()
    /**
     * @brief onValidate
     *
     * @param mixed
     * @return void
     **/
    public function onValidate()
    {
        // @todo validate output folders
        return true;

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
