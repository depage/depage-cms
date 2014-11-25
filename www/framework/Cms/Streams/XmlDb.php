<?php

namespace depage\Cms\Streams;

class XmlDb extends Base {
    protected static $parameters;
    protected $xmldb = null;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $docName = $url['host'];

        if (!empty($docName) && $this->xmldb->docExists($docName)) {
            $this->data = $this->xmldb->getDocXml($docName);

            // proj:pages_struct
            if ($this->data->documentElement->nodeName == "proj:pages_struct" && isset($this->transformer)) {
                // add status attributes for page tree
                $xmlnav = new \Depage\Cms\XmlNav();

                $xmlnav->addStatusAttributes($this->data, $this->transformer->currentPath);
            }

            return true;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
