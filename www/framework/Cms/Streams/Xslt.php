<?php

namespace depage\Cms\Streams;

class Xslt extends Base {
    protected static $parameters;

    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);

        if (!empty($url['path'])) {
            $path = $url['host'] . $url['path'];
        } else {
            $path = $url['host'];
        }
        $path = realpath(DEPAGE_FM_PATH . "/cms/xslt/" . $path);

        if (file_exists($path)) {
            $this->data = file_get_contents("file://$path");
            return true;
        } else {
            return false;
        }

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
