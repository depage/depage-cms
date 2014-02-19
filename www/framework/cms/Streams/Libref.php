<?php

namespace depage\cms\Streams;

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
        $path = $this->preview->getRelativePathTo($path);

        $this->data = '<return>' . htmlspecialchars($path) . '</return>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
