<?php

namespace Depage\Cms;

class JsTreeXmlToHtml
{
    /**
      * @param $nodes associative array of id => DOMDocument instances
      * @returns associative array of id => HTML code
      */
    public static function toHTML($nodes, $project) {
        $xsl = new \DOMDocument();
        $xsl->load(DEPAGE_FM_PATH . "Cms/Xslt/nodeToJstree.xsl", LIBXML_NOCDATA);

        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);
        $xslt->setParameter("", "projectName", $project->name);

        $fl = new \Depage\Cms\FileLibrary($project->getPdo(), $project);

        \Depage\Cms\Xslt\FuncDelegate::registerFunctions($xslt, [
            "thumbnailSrc" => function($ref) use ($fl, $project) {
                $info = $fl->getFileInfoByRef($ref);

                if (!$info) return "";

                $path = "projects/{$project->name}/lib/{$info->fullname}";

                if ($info->ext == "svg") {
                    return $path;
                }

                return $path . ".tf-48x48.png";
            },
        ]);

        $html = [];
        foreach ($nodes as $id => &$subdoc) {
            $html[$id] = $xslt->transformToXML($subdoc);
        }

        return $html;
    }
}
