<?php

namespace Depage\Cms\Streams;

class XmlDb extends Base {
    protected static $parameters = [];
    protected $xmldb = null;
    protected $transformer = null;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $docName = $url['host'];
        $xpath = isset($url['path']) ? substr($url['path'], 1) : '';

        if (!empty($docName) && $docId = $this->xmldb->docExists($docName)) {
            if (!empty($xpath)) {
                $data = $this->xmldb->getDocXmlXpath($docName, $xpath);
            } else {
                $data = $this->xmldb->getDocXml($docName);
            }

            if (!$data) {
                return false;
            }

            if (isset($this->transformer)) {
                $this->transformer->addToUsedDocuments($docId);
            }

            // proj:pages_struct
            if ($data->documentElement->nodeName == "proj:pages_struct" && isset($this->transformer)) {
                // add status attributes for page tree
                $xmlnav = new \Depage\Cms\XmlNav();
                $xmlnav->setPageXml($data);
                $xmlnav->addStatusAttributes($xmlnav->getPageXml(), $this->transformer->currentPath);
            }
            $this->data = $data->saveXML();

            return true;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
