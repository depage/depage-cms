<?php

namespace Depage\Graphics;

class Imgurl
{
    protected $actions = array();

    public function __construct()
    {
    }

    public function render($graphics)
    {
        $scriptParts = explode("/", $_SERVER["SCRIPT_NAME"]);
        $uriParts = explode("/", $_SERVER["REQUEST_URI"]);

        for ($i = 0; $i < count($uriParts); $i++) {
            // find common parts of url up to lib parameter
            if ($scriptParts[$i] != $uriParts[$i] || $uriParts[$i] == "lib") {
                break;
            }
        }
        $baseUrl = implode("/", array_slice($uriParts, 0, $i));
        $imgUrl = substr($_SERVER["REQUEST_URI"], strlen($baseUrl) + 1);

        preg_match("/(.*\.(jpg|jpeg|gif|png))\.([^\\\]*)\.(jpg|jpeg|gif|png)/i", $imgUrl, $matches);

        $rel = str_repeat("../", $i - 1);
        $srcImg = $rel . $matches[1];
        $outImg = $rel . "lib/cache/graphics/" . $matches[0];
        $actions = explode(".", $matches[3]);
        
        // make cache diretories
        $cacheDir = dirname($outImg);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        foreach($actions as $action) {
            preg_match("/([a-z]+)(.*)/i", $action, $matches);
            $action = $matches[1];
            preg_match_all("/[-x]([^-x]*)/i", $matches[2], $matches);
            $params = $matches[1];
            // @todo evaluate parameters

            if (is_callable(array($graphics, "add$action"))) {
                if ($action != "background") {
                    foreach ($params as &$p) {
                        $p = intval($p);
                        if ($p == 0) {
                            $p = null;
                        }
                    }
                }
                call_user_func_array(array($graphics, "add$action"), $params);
            }
        }

        $graphics->render($srcImg, $outImg);

        $info = pathinfo($outImg);
        $ext = $info['extension'];

        if (in_array($ext, array("jpg", "jpeg", "JPG", "JPEG"))) {
            header("Content-type: image/jpeg");
        } elseif (in_array($ext, array("png", "PNG"))) {
            header("Content-type: image/png");
        } elseif (in_array($ext, array("gif", "GIF"))) {
            header("Content-type: image/gif");
        }
        readfile($outImg);
        // @todo disable deleting when finished
        unlink($outImg);
    }

    public function getUrl($img)
    {
        $info = pathinfo($img);
        $ext = $info['extension'];

        if (count($this->actions) > 0) {
            return $img . "." . implode(".", $this->actions) . "." . $ext;
        } else {
            return $img;
        }
    }
    
    // {{{ addBackground()
    public function addBackground($background)
    {
        $this->actions[] = "bg-{$background}";
    }
    // }}}
    // {{{ addCrop()
    public function addCrop($width, $height, $x = 0, $y = 0)
    {
        $this->actions[] = "crop-{$width}x{$height}-{$x}x{$y}";
    }
    // }}}
    // {{{ addResize()
    public function addResize($width, $height)
    {
        $this->actions[] = "resize-{$width}x{$height}";
    }
    // }}}
    // {{{ addThumb()
    public function addThumb($width, $height)
    {
        $this->actions[] = "thumb-{$width}x{$height}";
    }
    // }}}
    // {{{ addThumbfill()
    public function addThumbfill($width, $height)
    {
        $this->actions[] = "thumbfill-{$width}x{$height}";
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
