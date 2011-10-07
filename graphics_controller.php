<?php
/**
 * @file    graphics_controller.php
 * @brief   Interface for accessing graphics via URI
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/

use depage\graphics\graphics;

/**
 * @brief Interface for accessing graphics via URI
 *
 * Translates GET data to graphics actions.
 **/
class graphics_controller {
    /**
     * @brief Default options array for graphics factory
     **/
    public $defaults = array(
        'extension'     => 'gm',
        'background'    => 'transparent',
    );

    // {{{ __construct()
    /**
     * @brief graphics_controller class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = NULL) {
        $conf = new config($options);
        $this->options = $conf->getDefaultsFromClass($this);
    }
    // }}}
    // {{{ convert()
    /**
     * @brief Translates GET data to graphics actions
     *
     * Createѕ graphics object and performs action on image. It saves the image
     * to the cache and displays it.
     *
     * @return void
     **/
    public function convert() {
        $command    = explode('-', $_GET['command']);
        $size       = explode('x', $command[1]);
        $root       = $_SERVER['DOCUMENT_ROOT'] . '/depage-cms/';

        // escape everything
        $action     = $this->letters($command[0]);
        $file       = escapeshellcmd($_GET['file']);
        $extension  = $this->letters($_GET['ext']);
        $width      = intval($size[0]);
        $height     = intval($size[1]);

        $cachedFile = ("{$root}cache/graphics/{$file}.{$action}-{$width}x{$height}.{$extension}");

        $img = graphics::factory(
            array(
                'extension'     => $this->defaults['extension'],
                'background'    => $this->defaults['background'],
            )
        );

        $this->mkPathToFile($cachedFile);

        try {
            $img->{"add$action"}($width, $height)->render($root . $file, $cachedFile);
        } catch (depage\graphics\graphics_file_not_found_exception $expected) {
            header("HTTP/1.1 404 Not Found");
        } catch (depage\graphics\graphics_exception $expected) {
            header("HTTP/1.1 500 Internal Server Error");
        }


        $this->display($cachedFile, $extension);
    }
    // }}}
    // {{{ display()
    /**
     * @brief Displays image
     *
     * @param   $fileName (string) path to image
     * @param   $format   (string) image format
     * @return  void
     **/
    protected function display($fileName, $format) {
        if ($format === 'jpg' || $format === 'jpeg') {
            header("Content-type: image/jpeg");
            imagejpeg(imagecreatefromjpeg($fileName));
        } else if ($format === 'png') {
            header("Content-type: image/png");
            imagejpeg(imagecreatefrompng($fileName));
        } else if ($format === 'gif') {
            header("Content-type: image/gif");
            imagejpeg(imagecreatefromgif($fileName));
        }
    }
    // }}}
    // {{{ letters()
    /**
     * @brief Cleans up strings
     *
     * Removes everything except letters from given string and returns it in
     * lowercase.
     *
     * @return (string) cleaned up string
     **/
    protected function letters($string) {
        return strtolower(preg_replace("[^A-Za-z]", '', $string));
    }
    // }}}
    // {{{ mkPathToFile()
    /**
     * @brief Creates path to file
     *
     * @param   $file (string) path/file
     * @return  void
     **/
    protected function mkPathToFile($file) {
        $cachePath = dirname($file);
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }
    // }}}
}
