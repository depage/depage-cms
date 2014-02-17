<?php

namespace depage\cms\Streams;

class Pageref extends Base {
    protected static $parameters;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $pageId = $url['host'];
        list($nothing, $lang, $absolute) = explode("/", $url['path']);

        $path = $lang . $this->urls[$pageId];

        if ($absolute != "absolute") {
            $path = $this->preview->getRelativePathTo($path);
        } 

        $this->data = '<page_ref>' . htmlspecialchars($path) . '</page_ref>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
