<?php
/**
 * @file    graphics_ui.php
 * @brief   Interface for accessing graphics via URI
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Sebastian Reinhold <sebastian@bitbernd.de>
 **/
namespace Depage\Graphics\Ui;
/**
 * @brief Interface for accessing graphics via URI
 *
 * Translates request to graphics actions.
 **/
class Graphics extends \Depage\Depage\Ui\Base
{
    /**
     * @brief Default options array for graphics factory
     **/
    public $defaults = array(
        'extension'     => 'gd',
        'executable'    => '',
        'background'    => 'transparent',
    );

    // }}}
    // {{{ notfound()
    public function notfound($function = "")
    {
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
    private function convert($request)
    {
        $request = rawurldecode($request);
        preg_match('/(.*(gif|jpg|jpeg|png))\.(resize|crop|thumb|thumbfill)-(.*)x(.*)\.(gif|jpg|jpeg|png)/', $request, $command);

        // escape everything
        $file       = escapeshellcmd($command[1]);
        $action     = $this->letters($command[3]);
        $width      = intval($command[4]);
        $height     = intval($command[5]);
        $extension  = $this->letters($command[6]);

        if ($width == 0) {
            $width = "X";
        }
        if ($height == 0) {
            $height = "X";
        }

        $cachedFile = (DEPAGE_CACHE_PATH . "graphics/{$file}.{$action}-{$width}x{$height}.{$extension}");

        $img = \Depage\Graphics\Graphics::factory(
            array(
                'extension'     => $this->options->extension,
                'executable'    => $this->options->executable,
                'background'    => $this->options->background,
                'optimize'      => $this->options->optimize,
            )
        );

        if (!$this->mkPathToFile($request)) {
            throw new Exceptions\Exception("Could not create cache directory");
        }

        try {
            if (is_callable(array($img, "add$action"))) {
                $img->{"add$action"}($width, $height)->render($file, $cachedFile);
            } else {
                header("HTTP/1.1 500 Internal Server Error");
                echo("unknown action");
                die();
            }
        } catch (Depage\Graphics\Exceptions\FileNotFound $expected) {
            header("HTTP/1.1 404 Not Found");
            echo("file not found");
            die();
        } catch (Depage\Graphics\Exceptions\Exception $expected) {
            header("HTTP/1.1 500 Internal Server Error");
            echo("an error occured");
            die();
        }
        $this->display($cachedFile, $extension);
    }
    // }}}
    // {{{ display()
    /**
     * @brief Displays image
     *
     * @param  string $fileName path to image
     * @param  string $format   image format
     * @return void
     **/
    protected function display($fileName, $format)
    {
        if ($format === 'jpg' || $format === 'jpeg') {
            header("Content-type: image/jpeg");
            readfile($fileName);
        } elseif ($format === 'png') {
            header("Content-type: image/png");
            readfile($fileName);
        } elseif ($format === 'gif') {
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
     * @return string cleaned up string
     **/
    protected function letters($string)
    {
        return strtolower(preg_replace("[^A-Za-z]", '', $string));
    }
    // }}}
    // {{{ mkPathToFile()
    /**
     * @brief Creates path to file
     *
     * @param  string $file path/file
     * @return void
     **/
    protected function mkPathToFile($file)
    {
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
     * @param       $time
     * @return void
     **/
    protected function send_time($time) {}
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
