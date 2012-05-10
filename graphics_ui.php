<?php
/**
 * @file    graphics_ui.php
 * @brief   Interface for accessing graphics via URI
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace depage\graphics;
/**
 * @brief Interface for accessing graphics via URI
 *
 * Translates request to graphics actions.
 **/
class graphics_ui extends \depage_ui {
    /**
     * @brief Default options array for graphics factory
     **/
    public $defaults = array(
        'extension'     => 'gd',
        'background'    => 'transparent',
    );

    // }}}
    // {{{ notfound()
    public function notfound($function = "") {
        $this->convert($function);
    }
    // }}}
    // {{{ convert()
    /**
     * @brief Translates request into graphics actions
     *
     * Createѕ graphics object and performs action on image. It saves the image
     * to the cache and displays it.
     *
     * @return void
     **/
    private function convert($request) {
        preg_match('/(.*(gif|jpg|jpeg|png))\.(resize|crop|thumb|thumbfill)-(.*)x(.*)\.(gif|jpg|jpeg|png)/', $request, $command);
        
        // escape everything
        $file       = escapeshellcmd($command[1]);
        $action     = $this->letters($command[3]);
        $width      = intval($command[4]);
        $height     = intval($command[5]);
        $extension  = $this->letters($command[6]);

        if ($width == 0) $width = null;
        if ($height == 0) $height = null;
        
        $cachedFile = (DEPAGE_CACHE_PATH . "graphics/{$file}.{$action}-{$width}x{$height}.{$extension}");
        
        $img = graphics::factory(
            array(
                'extension'     => $this->defaults['extension'],
                'background'    => $this->defaults['background'],
            )
        );
        
        if (!$this->mkPathToFile($request)) {
            throw new graphics_exception("Could not create cache directory");
        }
        
        try {
            if (is_callable(array($img, "add$action"))) {
                $img->{"add$action"}($width, $height)->render($file, $cachedFile);
            } else {
                header("HTTP/1.1 500 Internal Server Error");
                echo("unknown action");
            }
        } catch (depage\graphics\graphics_file_not_found_exception $expected) {
            header("HTTP/1.1 404 Not Found");
            echo("file not found");
        } catch (depage\graphics\graphics_exception $expected) {
            header("HTTP/1.1 500 Internal Server Error");
            echo("an error occured");
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
            readfile($fileName);
        } else if ($format === 'png') {
            header("Content-type: image/png");
            readfile($fileName);
        } else if ($format === 'gif') {
            header("Content-type: image/gif");
            readfile($fileName);
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
        $cachePath = DEPAGE_CACHE_PATH."graphics/".dirname($file);
        if (!is_dir($cachePath)) {
            return mkdir($cachePath, 0755, true);
        }
        return true;
    }
    // }}}
    // {{{ send_time()
    /**
     * @brief Override depage_ui method
     *
     * @param   $time
     * @return  void
     **/
    protected function send_time($time) {}
    // }}}
}
