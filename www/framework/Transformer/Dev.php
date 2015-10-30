<?php

namespace Depage\Transformer;

class Dev extends Transformer
{
    protected $previewType = "dev";
    protected $profiling = true;

    // {{{ getXsltEntities()
    protected function getXsltEntities()
    {
        return "";
    }
    // }}}
    // {{{ getXsltIncludes()
    protected function getXsltIncludes($files)
    {
        $xslt = "";

        foreach ($files as $file) {
            $xslt .= "\n<xsl:include href=\"" . htmlentities(realpath($file)) . "\" />";
        }

        return $xslt;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
