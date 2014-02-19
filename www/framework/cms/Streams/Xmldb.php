<?php

namespace depage\cms\Streams;

class Xmldb extends Base {
    protected static $parameters;
    protected $xmldb = null;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $docName = $url['host'];

        if ($this->xmldb->docExists($docName)) {
            $doc = $this->xmldb->getDoc($docName);
            $handler = $doc->getDoctypeHandler();

            $this->data = $doc->getXml($docName);

            if ($handler = "depage\xmldb\xmldoctypes\pages") {
                // add status attributes for page tree
                $xmlnav = new \depage\cms\xmlnav();

                $xmlnav->addStatusAttributes($this->data, $this->currentPath);
            }

            return true;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
