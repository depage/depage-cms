<?php

namespace Depage\Cms\XmlDocTypes\Traits;

trait UniqueNames
{
    // {{{ variables
    protected $nodes;
    // }}}

    // {{{ testUniqueNames
    public function testUniqueNames($node, $xpathQuery = "//*") {
        $changed = false;

        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($xml);
        $pages = $xpath->query($xpathQuery);
        $this->nodes = new \SplObjectStorage();

        foreach ($pages as $page) {
            $this->nodes->attach($page);
        }
        foreach ($this->nodes as $node) {
            $changed = $this->testChildNodeNames($node) || $changed;
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
        $names = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType == \XML_ELEMENT_NODE && !empty($child->getAttribute("name")) && $this->nodes->contains($child)) {
                $nodeId = $child->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
                $nodeName = $child->getAttribute("name");
                $found = false;

                while (in_array($nodeName, $names)) {
                    // @todo updated to take _("(copy)") into account when renaming
                    preg_match('/^(.*?)([\\d]*)$/', $nodeName, $matches);
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
