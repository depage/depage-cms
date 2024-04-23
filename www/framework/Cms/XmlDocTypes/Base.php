<?php

namespace Depage\Cms\XmlDocTypes;

class Base extends \Depage\XmlDb\XmlDoctypes\Base
{
    /**
     * @brief project
     **/
    protected $project = null;

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $this->project = $this->xmlDb->options['project'];
    }
    // }}}

    // {{{ onDocumentChange()
    /**
     * @brief onDocumentChange
     *
     * @param mixed
     * @return void
     **/
    public function onDocumentChange()
    {
        parent::onDocumentChange();

        $templates = $this->project->getXslTemplates();
        $previewTypes = ["pre"];

        foreach ($templates as $template) {
            foreach ($previewTypes as $type) {
                $transformCache = new \Depage\Transformer\TransformCache($this->xmlDb->pdo, $this->project->name, "$template-$type");
                $transformCache->clearFor($this->document->getDocId());
            }
        }
    }
    // }}}
    // {{{ onHistorySave
    public function onHistorySave() {
        parent::onHistorySave();

        $templates = $this->project->getXslTemplates();
        $publishingTargets = $this->project->getPublishingTargets();
        $previewTypes = ["live"];

        foreach ($publishingTargets as $id => $settings) {
            array_push($previewTypes, "live-$id");
        }

        foreach ($templates as $template) {
            foreach ($previewTypes as $type) {
                $transformCache = new \Depage\Transformer\TransformCache($this->xmlDb->pdo, $this->project->name, "$template-$type");
                $transformCache->clearFor($this->document->getDocId());
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
