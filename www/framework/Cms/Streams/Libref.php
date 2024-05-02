<?php

namespace Depage\Cms\Streams;

class Libref extends Base {
    protected static $parameters;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $this->data = '<return>' . htmlspecialchars($this->funcClass->getLibRef($path)) . '</return>';

        return true;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
