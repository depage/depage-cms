<?php

namespace Depage\Cms\XmlDocTypes;

abstract class UniqueNames extends Base {
    // {{{ testDocument
    public function testDocument($node) {
        $changed = false;

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("pg", "http://cms.depagecms.net/ns/page");
        $pages = $xpath->query("//pg:*");

        foreach ($pages as $page) {
            $changed = $changed || $this->testChildNodeNames($page);
        }

        return $changed;
    }
    // }}}
    // {{{ testChildNodeNames()
    /*
     * Test childnames of node so that every node on the same level has a unique name
     *
     * @param $node
     */
    public function testChildNodeNames($node) {
        $changed = false;
        $names = array();

        foreach ($node->childNodes as $child) {
            if ($child->nodeType == \XML_ELEMENT_NODE) {
                $nodeId = $child->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
                $nodeName = $child->getAttribute("name");
                $found = false;

                while (in_array($nodeName, $names)) {
                    // @todo updated to take _("(copy)") into account when renaming
                    preg_match('/([\D]*)([\d]*)/', $nodeName, $matches);
                    $baseName = $matches[1];

                    if ($matches[2] !== "") {
                        $number = $matches[2] + 1;
                    } else {
                        $baseName .= " ";
                        $number = 2;
                    }

                    $nodeName  = $baseName . $number;

                    $found = true;
                }
                if ($found) {
                    $child->setAttribute("name", $nodeName);

                    $changed = true;
                }

                $names[$nodeId] = $nodeName;
            }
        }

        return $changed;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
