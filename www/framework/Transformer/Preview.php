<?php

namespace Depage\Transformer;

class Preview extends Transformer
{
    protected $previewType = "pre";

    // {{{ getXsltEntities()
    protected function getXsltEntities()
    {
        return "<!DOCTYPE xsl:stylesheet [ <!ENTITY % htmlentities SYSTEM \"xslt://htmlentities.ent\"> %htmlentities; ]>";
    }
    // }}}
    // {{{ getXsltInclude()
    protected function getXsltIncludes($files)
    {
        $xslt = "";
        foreach ($files as $file) {
            $tpl = new \Depage\Xml\Document();
            $tpl->load($file);

            foreach ($tpl->documentElement->childNodes as $node) {
                $xslt .= $tpl->saveXML($node);
            }
        }

        return $xslt;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
