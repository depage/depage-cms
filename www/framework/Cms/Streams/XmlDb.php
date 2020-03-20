<?php

namespace Depage\Cms\Streams;

class XmlDb extends Base {
    protected static $parameters = [];
    protected $xmldb = null;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $docName = $url['host'];
        $xpath = isset($url['path']) ? substr($url['path'], 1) : '';

        if (!empty($docName) && $docId = $this->xmldb->docExists($docName)) {
            if (!empty($xpath)) {
                $this->data = $this->xmldb->getDocXmlXpath($docName, $xpath);
            } else {
                $this->data = $this->xmldb->getDocXml($docName);
            }

            if (isset($this->transformer)) {
                $this->transformer->addToUsedDocuments($docId);
            }

            // proj:pages_struct
            if ($this->data->documentElement->nodeName == "proj:pages_struct" && isset($this->transformer)) {
                // add status attributes for page tree
                $xmlnav = new \Depage\Cms\XmlNav();
                $xmlnav->setPageXml($this->data);
                $xmlnav->addStatusAttributes($xmlnav->getPageXml(), $this->transformer->currentPath);
            }

            return true;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
