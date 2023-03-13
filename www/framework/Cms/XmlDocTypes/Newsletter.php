<?php

namespace Depage\Cms\XmlDocTypes;

class Newsletter extends Base
{
    use Traits\MultipleLanguages;
    use Traits\XmlTemplates;

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $this->initAvailableNodes();
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

        return true;

    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testNodeLanguages($node);
        $changed = $this->testForAutoNewsNode($node) || $changed;

        $this->addReleaseStatusAttributes($node->firstChild);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xml->firstChild->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
    }
    // }}}
    // {{{ testForAutoNewsNode()
    /**
     * @brief testForAutoNewsNode
     *
     * @param mixed $
     * @return void
     **/
    public function testForAutoNewsNode($node)
    {
        $changed = false;

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        if ($node->nodeName != "pg:newsletter") {
            return false;
        }

        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("/pg:newsletter/sec:autoNewsList");

        if ($nodelist->length == 0) {
            $parentNode = $xml->createElementNS("http://cms.depagecms.net/ns/section", "sec:autoNewsList");
            $parentNode->setAttributeNS("http://cms.depagecms.net/ns/database", "db:name", "tree_name_autonews");
            $node->appendChild($parentNode);

            $nodelist = $xpath->query("/pg:newsletter/sec:news", $node);
            foreach ($nodelist as $newsNode) {
                $parentNode->appendChild($newsNode);
            }

            $changed = true;
        }

        return $changed;
    }
    // }}}
    // {{{ addReleaseStatusAttributes()
    /**
     * @brief addReleaseStatusAttributes
     *
     * @param mixed $
     * @return void
     **/
    public function addReleaseStatusAttributes($node)
    {
        $info = $this->document->getDocInfo();
        $versions = array_values($this->document->getHistory()->getVersions(true, 1));

        // @todo fix not to get from timestamp when sha1 did no change
        if (count($versions) > 0 && $info->lastchange->getTimestamp() < $versions[0]->lastsaved->getTimestamp()) {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
        } else {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "false");
        }
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
