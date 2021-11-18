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
    public $centerX = null;
    public $centerY = null;
    public $displayAspectRatio = null;
    public $duration = null;
    public $artist = "";
    public $title = "";
    public $album = "";
    public $copyright = "";
    public $description = "";
    public $keywords = "";
    public $customKeywords = "";
    public $ext = "";
    public $fullname = "";
    public $libref = "";
    public $libid = "";

    // {{{ init()
    /**
     * @brief init
     *
     * @param mixed
     * @return void
     **/
    public function init($path)
    {
        if (is_string($this->lastmod)) {
            $date = new \DateTime($this->lastmod);
            $this->lastmod = $date;
        }
        if (!is_null($this->filename)) {
            $this->ext = pathinfo($this->filename, \PATHINFO_EXTENSION);
            $this->fullname = trim($path . $this->filename, '/');
        }
        $this->libid = "libid://{$this->id}/{$this->hash}";
        $this->libref = "libref://{$this->fullname}";
    }
    // }}}
    // {{{ toXml()
    /**
     * @brief toXml
     *
     * @param mixed
     * @return void
     **/
    public function toXml()
    {
        $fields = [
            'filename',
            'mime',
            'hash',
            'filesize',
            'width',
            'height',
            'centerX',
            'centerY',
            'artist',
            'title',
            'album',
            'copyright',
            'keywords',
            'customKeywords',
            'ext',
            'libref',
            'libid',
        ];


        $doc = new \DOMDocument();
        $node = $doc->createElement("file");
        $node->setAttribute("exists", "true");
        $node->setAttribute("name", $this->filename);
        $node->setAttribute("basename", $this->filename);
        $node->setAttribute("dirname", 'lib/' . dirname($this->fullname));
        $node->setAttribute("fullpath", 'lib/' . $this->fullname);
        $node->setAttribute("extension", $this->ext);
        $node->setAttribute("isImage", strpos($this->mime, "image/") === 0);
        $node->setAttribute("isAudio", strpos($this->mime, "audio/") === 0);
        $node->setAttribute("isVideo", strpos($this->mime, "video/") === 0);
        $node->setAttribute("tag_title", $this->title);
        $node->setAttribute("tag_album", $this->album);
        $node->setAttribute("tag_artist", $this->artist);
        $node->setAttribute("filemtime", $this->lastmod->getTimestamp());
        $node->setAttribute("date", $this->lastmod->format("Y-m-d H:i:s"));
        $node->setAttribute("duration", number_format($this->duration, 5));
        $node->setAttribute("displayAspectRatio", number_format($this->displayAspectRatio, 5));

        foreach ($fields as $key) {
            $node->setAttribute($key, $this->$key);
        }

        $doc->appendChild($node);

        return $doc;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
