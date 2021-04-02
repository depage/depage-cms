<?php
/**
 * @file    FileInfo.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

/**
 * @brief FileInfo
 * Class FileInfo
 */
class FileInfo
{
    public $id = null;
    public $folder = null;
    public $filename = null;
    public $filenamehash = null;
    public $mime = null;
    public $hash = null;
    public $filesize = null;
    public $lastmod = null;
    public $width = null;
    public $height = null;
    public $displayAspectRatio = null;
    public $duration = null;
    public $artist = "";
    public $title = "";
    public $album = "";
    public $copyright = "";
    public $description = "";
    public $keywords = "";
    public $ext = "";
    public $fullname = "";
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
