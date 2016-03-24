<?php

namespace Depage\Cms\XmlDocTypes;

class Settings extends Base {
    use Traits\MultipleLanguages;

    /**
     * @brief project
     **/
    protected $project = null;

    // {{{ constructor
    public function __construct($xmldb, $document) {
        parent::__construct($xmldb, $document);

        $this->project = $this->xmldb->options['project'];
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        return $this->testNodeLanguages($node);
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

        $oldLanguages = $this->xmldb->cache->get("dp_proj_{$this->project->name}_settings/languages.ser");
        $this->xmldb->cache->delete("dp_proj_{$this->project->name}_settings/*");

        $newLanguages = $this->project->getLanguages();

        if ($oldLanguages != $newLanguages) {
            $this->xmldb->cache->delete("dp_proj_{$this->project->name}_*");
            // @todo clean cache after updating languages?
            // @todo add task to update all documents with new languages?
        }

        return true;

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */

