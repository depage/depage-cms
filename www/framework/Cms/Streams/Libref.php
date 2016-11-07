<?php

namespace depage\Cms\Streams;

class Libref extends Base {
    protected static $parameters;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);

        if (!empty($url['path'])) {
            $path = "lib/" . $url['host'] . $url['path'];
        } else {
            $path = "lib/" . $url['host'];
        }

        if (!$this->transformer->useAbsolutePaths && $absolute != "absolute") {
            $url = new \Depage\Http\Url($this->transformer->currentPath);
            $path = $url->getRelativePathTo($path);
        } else {
            $path = $this->transformer->baseUrl . $path;
        }

        $this->data = '<return>' . htmlspecialchars($path) . '</return>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
