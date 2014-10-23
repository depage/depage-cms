<?php

namespace Depage\FS;

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
    public function fileInfo($path);

    public function readString($path);
    public function writeString($path, $string);
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
