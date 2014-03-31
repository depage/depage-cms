<?php

namespace depage\Transformer;

class Live extends Preview
{
    protected $previewType = "live";
    protected $isLive = true;

    // {{{ constructor
    public function __construct($pdo, $projectName, $template, $cacheOptions = array())
    {
        parent::__construct($pdo, $projectName, $template, $cacheOptions);
        
        // get cache instance for transforms
        $this->transformCache = \depage\cache\cache::factory("transform", $cacheOptions);
    }
    // }}}
    
    // {{{ initXmlGetter()
    public function initXmlGetter()
    {
        $this->xmlGetter = new \depage\xmldb\XmldbHistory($this->prefix, $this->pdo);
    }
    // }}}
    // {{{ transformXml()
    protected function transformXml($pageId, $pagedataId)
    {
        // @todo add publishing id/domain/baseurl to cachePath
        $cachePath = $this->projectName . "/" . $this->template . "/" . $this->lang . $this->currentPath;

        if ($this->transformCache->exist($cachePath)) {
            $html = $this->transformCache->getFile($cachePath);
        } else {
            $html = parent::transformXml($pageId, $pagedataId);
            $this->transformCache->setFile($cachePath, $html);
        }

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
