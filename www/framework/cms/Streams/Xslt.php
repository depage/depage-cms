<?php

namespace depage\cms\Streams;

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
        $path = realpath("framework/cms/xslt/" . $path);

        //var_dump(stat($path));
        if (file_exists($path)) {
            $this->data = file_get_contents("file://$path");
            //var_dump($this->data);
            return true;
        } else {
            return false;
        }

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
