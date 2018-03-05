<?php

namespace depage\Cms\Streams;

class Pageref extends Base {
    protected static $parameters;

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

        $urlsByPageId = $this->transformer->getUrlsByPageId();
        if (isset($urlsByPageId[$pageId])) {
            $path = $lang . $urlsByPageId[$pageId];
        }

        if ($this->transformer->useBaseUrl) {
            $path = $path;
        } else if ($absolute == "absolute" || $this->transformer->useAbsolutePaths) {
            $path = $this->transformer->baseUrl . $path;
        } else {
            $url = new \Depage\Http\Url($this->transformer->currentPath);
            $path = $url->getRelativePathTo($path);
        }
        if ($this->transformer->routeHtmlThroughPhp) {
            $path = preg_replace("/\.php$/", ".html", $path);
        }

        $this->data = '<return>' . htmlspecialchars($path) . '</return>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
