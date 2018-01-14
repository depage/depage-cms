<?php

namespace Depage\Http;

/**
 * brief Url
 * Class Url
 */
class Url
{
    /**
     * @brief url
     **/
    protected $url = "";

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($url)
    {
        $this->url = $url;
    }
    // }}}

    // {{{ getRelativePathTo()
    /**
     * @brief getRelativePathTo
     *
     * @param mixed $targetPath
     * @return void
     **/
    public function getRelativePathTo($targetPath)
    {
        // link to self by default
        $path = '';
        if ($targetPath != '' && $targetPath != $this->url) {
            $currentPath = explode('/', $this->url);
            $targetPath = explode('/', $targetPath);

            $i = 0;
            while ((isset($currentPath[$i]) && $targetPath[$i]) && $currentPath[$i] == $targetPath[$i]) {
                $i++;
            }

            if (count($currentPath) - $i >= 1) {
                $path = str_repeat('../', count($currentPath) - $i - 1) . implode('/', array_slice($targetPath, $i));
            }
        }
        return $path;
    }
    // }}}
    // {{{ getAbsolutePathTo()
    /**
     * @brief getAbsolutePathTo
     *
     * @param mixed $targetPath
     * @return void
     **/
    public function getAbsolutePathTo($targetPath)
    {
        if (substr($targetPath, 0, 1) == "/") {
            return $targetPath;
        } else if (parse_url($targetPath, PHP_URL_HOST) != "") {
            return $targetPath;
        }
        // @todo throw exception when out of bounds
        $currentPath = explode('/', $this->url);
        array_pop($currentPath);

        $targetPath = explode('/', $targetPath);

        while (count($targetPath) > 0) {
            $part = array_shift($targetPath);
            if ($part == "." || $part == "") {
                // stay on same level
            } else if ($part == "..") {
                array_pop($currentPath);
            } else {
                $currentPath[] = $part;
            }
        }

        return implode('/', $currentPath);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
