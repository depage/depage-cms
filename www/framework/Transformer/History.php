<?php

namespace Depage\Transformer;

class History extends Transformer
{
    protected $previewType = "history";
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
            $xslt .= "\n<xsl:include href=\"" . htmlentities(rawurlencode(realpath($file))) . "\" />";
        }

        return $xslt;
    }
    // }}}

    // {{{ display()
    /**
     * @brief display
     *
     * @param mixed $urlPath, $lang
     * @return void
     **/
    public function display($urlPath, $lang)
    {
        try {
            return parent::display($urlPath, $lang);
        } catch (\Exception $e) {
            throw new \Exception("Could not display old version\n");
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
