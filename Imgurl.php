<?php

namespace Depage\Graphics;

class Imgurl
{
    protected $options = array();
    protected $actions = array();
    protected $cachePath = '';
    /*
     * action aliases
     *
     * Note that the order is important so shoter action names should
     * come after larger ones
     */
    protected $aliases = array(
        'quality'    => "setQuality",
        'q'          => "setQuality",
        'crop'       => "addCrop",
        'c'          => "addCrop",
        'resize'     => "addResize",
        'r'          => "addResize",
        'thumbfill'  => "addThumbfill",
        'tf'         => "addThumbfill",
        'thumb'      => "addThumb",
        't'          => "addThumb",
        'background' => "addBackground",
        'bg'         => "addBackground",
    );

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
            // we are using depage-framework so use constants for paths
            $baseUrl = DEPAGE_PATH;
            $relativePath = "";
            $this->cachePath = DEPAGE_CACHE_PATH . "graphics/";
        } else {
            // we using the library plainly -> get path through url
            $scriptParts = explode("/", $_SERVER["SCRIPT_NAME"]);
            $uriParts = explode("/", $_SERVER["REQUEST_URI"]);

            for ($i = 0; $i < count($uriParts); $i++) {
                // find common parts of url up to lib parameter
                if ($scriptParts[$i] != $uriParts[$i] || $uriParts[$i] == "lib") {
                    break;
                }
            }
            $baseUrl = implode("/", array_slice($uriParts, 0, $i));
            $relativePath = str_repeat("../", count($scriptParts) - $i - 1);
            $this->cachePath = $relativePath . "lib/cache/graphics/";
        }

        // get image name
        $imgUrl = substr($_SERVER["REQUEST_URI"], strlen($baseUrl) + 1);

        // get action parameters
        preg_match("/(.*\.(jpg|jpeg|gif|png))\.([^\\\]*)\.(jpg|jpeg|gif|png)/i", $imgUrl, $matches);

        $this->srcImg = $relativePath . $matches[1];
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
        $actions = explode(".", $actionString);

        foreach ($actions as &$action) {
            $regex = implode("|", array_keys($this->aliases));
            preg_match("/^($regex)/i", $action, $matches);
            $func = $this->aliases[$matches[1]];
            $params = substr($action, strlen($matches[1]));

            if (!empty($func)) {
                $params = preg_split("/[-x,]+/", $params, null, PREG_SPLIT_NO_EMPTY);
                // @todo evaluate parameters

                if ($action != "addBackground") {
                    foreach ($params as &$p) {
                        $p = intval($p);
                        if ($p == 0) {
                            $p = null;
                        }
                    }
                }

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

        try {
            // add actions to graphics class
            foreach ($this->actions as $action) {
                list($func, $params) = $action;
                if (is_callable(array($graphics, $func))) {
                    call_user_func_array(array($graphics, $func), $params);
                }
            }

            // render image out
            $graphics->render($this->srcImg, $this->outImg);
        } catch (Exceptions\FileNotFound $e) {
            header("HTTP/1.1 404 Not Found");
            echo("file not found");
            die();
        } catch (Exceptions\Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo("an error occured");
            die();
        }

        return $this;
    }
    // }}}

    // {{{ display()
    public function display()
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
        //unlink($this->outImg);

        return $this;
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
        $this->actions[] = "crop{$width}x{$height}-{$x}x{$y}";
    }
    // }}}
    // {{{ addResize()
    public function addResize($width, $height)
    {
        $this->actions[] = "resize{$width}x{$height}";
    }
    // }}}
    // {{{ addThumb()
    public function addThumb($width, $height)
    {
        $this->actions[] = "thumb{$width}x{$height}";
    }
    // }}}
    // {{{ addThumbfill()
    public function addThumbfill($width, $height)
    {
        $this->actions[] = "thumbfill{$width}x{$height}";
    }
    // }}}
    // {{{ setQuality()
    public function setQuality($quality)
    {
        $this->actions[] = "q{$quality}";
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
