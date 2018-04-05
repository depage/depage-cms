<?php

namespace Depage\Cms;

class JsTreeXmlToHtml
{
    /**
      * @param $nodes associative array of id => DOMDocument instances
      * @returns associative array of id => HTML code
      */
    public static function toHTML($nodes, $projectName) {
        $xsl = new \DOMDocument();
        $xsl->load(DEPAGE_FM_PATH . "xslt/nodes_to_html.xsl", LIBXML_NOCDATA);

        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);
        $xslt->setParameter("", "projectName", $projectName);

        $html = [];
        foreach ($nodes as $id => &$subdoc) {
            $html[$id] = $xslt->transformToXML($subdoc);
        }

        return $html;
    }
}

?>
