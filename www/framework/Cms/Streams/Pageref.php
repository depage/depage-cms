<?php

namespace Depage\Cms\Streams;

class Pageref extends Base {
    protected static $parameters;
    protected $transformer = null;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $path = "";
        $parts = explode("/", $url['path']);

        $pageId = $url['host'];
        $lang = isset($parts[1]) ? $parts[1] : $this->transformer->lang;
        $absolute = isset($parts[2]) ? $parts[2] : "";

        $this->data = '<return>' . htmlspecialchars($this->transformer->xsltGetPageRef($pageId, $lang, $absolute)) . '</return>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
