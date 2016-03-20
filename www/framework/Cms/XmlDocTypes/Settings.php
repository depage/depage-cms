<?php

namespace Depage\Cms\XmlDocTypes;

class Settings extends Base {
    use Traits\MultipleLanguages;

    // @todo clean cache after updating languages
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */

