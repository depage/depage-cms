<?php

namespace Depage\Transformer;

class Live extends Preview
{
    protected $previewType = "live";
    protected $isLive = true;

    // {{{ initXmlGetter()
    public function initXmlGetter()
    {
        $this->xmlGetter = new \Depage\XmlDb\XmlDbHistory($this->prefix, $this->pdo);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
