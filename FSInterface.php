<?php

namespace depage\FS;

interface FSInterface {
    public function __construct($params);

    public function ls($path);
    public function cd($path);
    public function mkdir($path);
    public function chmod($path, $mod);
    public function rm($path);

    public function mv($source, $target);
    public function cp($source, $target);

    public function exists($path);
    public function stat($path);

    public function append($path, $string);
    public function write($path, $file);
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
