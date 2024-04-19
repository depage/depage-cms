<?php

namespace Depage\Transformer;

class Dev extends Transformer
{
    protected $previewType = "dev";
    protected $profiling = true;

    // {{{ addXsltIncludes()
    protected function addXsltIncludes($doc, $files)
    {
        $root = $doc->documentElement;

        foreach ($files as $file) {
            $n = $doc->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:include");
            $n->setAttribute("href", rawurlencode(realpath($file)));
            $root->appendChild($n);
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
