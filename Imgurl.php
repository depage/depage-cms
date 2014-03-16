<?php

namespace Depage\Graphics;

class Imgurl
{
    protected $options = array();
    protected $actions = array();
    protected $cachePath = '';

    // {{{ constructor
    /*
     * @param $options hold the same options as the graphics class
     */
    public function __construct($options = array())
    {
        $this->options = $options;
    }
    // }}}

    // {{{ analyze
    /*
     * Analyzes the image url and set the path for srcImg and outImg
     */
    protected function analyze()
    {
        if (defined('DEPAGE_PATH') && defined('DEPAGE_CACHE_PATH')) {
            $baseUrl = DEPAGE_PATH;
            $this->cachePath = DEPAGE_CACHE_PATH . "graphics/";
            $rel = "";
        } else {
            $scriptParts = explode("/", $_SERVER["SCRIPT_NAME"]);
            $uriParts = explode("/", $_SERVER["REQUEST_URI"]);

            for ($i = 0; $i < count($uriParts); $i++) {
                // find common parts of url up to lib parameter
                if ($scriptParts[$i] != $uriParts[$i] || $uriParts[$i] == "lib") {
                    break;
                }
            }
            $baseUrl = implode("/", array_slice($uriParts, 0, $i));
            $rel = str_repeat("../", $i - 1);
            $this->cachePath = $rel . "lib/cache/graphics/";
        }
        $imgUrl = substr($_SERVER["REQUEST_URI"], strlen($baseUrl) + 1);

        preg_match("/(.*\.(jpg|jpeg|gif|png))\.([^\\\]*)\.(jpg|jpeg|gif|png)/i", $imgUrl, $matches);

        $this->srcImg = $rel . $matches[1];
        $this->outImg = $this->cachePath . $matches[0];
        $this->actions = $this->analyzeActions($matches[3]);
        
    }
    // }}}
    // {{{ analyzeActions
    /*
     * Analyzes actions and replaces shortcuts with real actions
     */
    protected function analyzeActions($actionString)
    {
        $aliases = array(
            'r'          => "addResize",
            'resize'     => "addResize",
            't'          => "addThumb",
            'thumb'      => "addThumb",
            'tf'         => "addThumbfill",
            'thumbfill'  => "addThumbfill",
            'background' => "addBackground",
            'bg'         => "addBackground",
        );
        $actions = explode(".", $actionString);

        foreach ($actions as &$action) {
            preg_match("/([a-z]+)(.*)/i", $action, $matches);
            $func = $aliases[$matches[1]];

            if (!empty($func)) {
                preg_match_all("/[-x]([^-x]*)/", $action, $matches);
                $params = $matches[1];
                // @todo evaluate parameters

                $action = array($func, $params);
            }
        }

        return $actions;
    }
    // }}}
    // {{{ render
    public function render()
    {
        $graphics = Graphics::factory($this->options);

        $this->analyze();

        // make cache diretories
        $outDir = dirname($this->outImg);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // add actions to graphics class
        foreach ($this->actions as $action) {
            list($func, $params) = $action;
            if (is_callable(array($graphics, $func))) {
                if ($action != "background") {
                    foreach ($params as &$p) {
                        $p = intval($p);
                        if ($p == 0) {
                            $p = null;
                        }
                    }
                }
                call_user_func_array(array($graphics, $func), $params);
            }
        }

        // render image out
        $graphics->render($this->srcImg, $this->outImg);

        // send image to browser
        $this->sendImage();
    }
    // }}}

    // {{{ sendImage()
    protected function sendImage()
    {
        $info = pathinfo($this->outImg);
        $ext = $info['extension'];

        if (in_array($ext, array("jpg", "jpeg", "JPG", "JPEG"))) {
            header("Content-type: image/jpeg");
        } elseif (in_array($ext, array("png", "PNG"))) {
            header("Content-type: image/png");
        } elseif (in_array($ext, array("gif", "GIF"))) {
            header("Content-type: image/gif");
        }
        readfile($this->outImg);
        // @todo disable deleting when finished
        unlink($this->outImg);
    }
    // }}}

    // {{{ getUrl()
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
    // }}}
    
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
