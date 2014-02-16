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
            $this->data = $this->xmldb->getDocXml($docName);

            return true;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
