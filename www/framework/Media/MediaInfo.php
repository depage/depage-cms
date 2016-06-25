<?php
/**
 * @file    mediainfo.php
 * @brief   file-/mediainfo class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace Depage\Media;

class MediaInfo
{
    // defaults {{{
    /**
     * Defaults array for ffmpeg conversion
     *
     * @var array
     */
    protected $defaults = array(
        'cache'       => null,
        'ffprobe'     => "ffprobe",
        'mplayer'     => "mplayer",
    );
    // }}}

    // properties {{{
    protected $ffprobe;
    protected $mplayer;
    protected $cache;
    protected $filename = "";
    protected $info = array();
    protected $iptcHeaders = array(
        '2#005' => 'DocumentTitle',
        '2#010' => 'Urgency',
        '2#015' => 'Category',
        '2#020' => 'Subcategories',
        '2#025' => 'Keywords',
        '2#040' => 'SpecialInstructions',
        '2#055' => 'CreationDate',
        '2#080' => 'AuthorByline',
        '2#085' => 'AuthorTitle',
        '2#090' => 'City',
        '2#095' => 'State',
        '2#101' => 'Country',
        '2#103' => 'OTR',
        '2#105' => 'Headline',
        '2#110' => 'Source',
        '2#115' => 'PhotoSource',
        '2#116' => 'Copyright',
        '2#120' => 'Caption',
        '2#122' => 'CaptionWriter',
    );
    // }}}

    // {{{ constructor
    /**
     * Constructor
     *
     * Build the default options for ffmpeg conversion.
     *
     * @param array $options
     *
     * @return void
     */
    public function __construct($options = array()) {
        $options = array_change_key_case($options);
        foreach ($this->defaults as $option => $default) {
            $this->$option = isset($options[$option]) ? $options[$option] : $default;
        }
    }
    // }}}

    // {{{ setFilename()
    public function setFilename($filename) {
        $this->info = array(
            'exists' => false,
            'isImage' => false,
            'isVideo' => false,
            'isAudio' => false,
        );

        $this->filename = $filename;
    }
    // }}}

    // {{{ getInfo()
    /**
     * get information about the file
     *
     * @return array
     */
    public function getInfo($filename = null) {
        if (!is_null($filename)) {
            $this->setFilename($filename);
        }

        $this->info = $this->getBasicInfo();

        if ($this->info['exists']) {
            // add mimetype info
            $this->info['mime'] = "application/octet-stream";

            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $mime = $finfo->file($this->filename);
            if (is_string($mime) && !empty($mime)) {
                $this->info['mime'] = $mime;
            }

            if ($this->info['exists']) {
                if ($this->hasImageExtension()) {
                    $this->getImageInfo();
                } else if ($this->hasMediaExtension()) {
                    // we cache only mediainfo because only this takes a longer time
                    $identifier = $this->filename . ".ser";
                    if (!is_null($this->cache) && $this->cache->age($identifier) >= $this->info['filemtime']) {
                        $this->info = $this->cache->get($identifier);
                    } else {
                        $this->getMediaInfo();
                        if (!is_null($this->cache)) {
                            $this->cache->set($identifier, $this->info);
                        }
                    }
                }
            }
        }

        return $this->info;
    }
    // }}}
    // {{{ getBasicInfo()
    /**
     * get information about the file
     *
     * @return array
     */
    public function getBasicInfo($filename = null) {
        if (!is_null($filename)) {
            $this->setFilename($filename);
        }

        $info = array(
            'exists' => false,
        );

        if (file_exists($this->filename)) {
            $fileinfo = pathinfo($this->filename);

            $info['exists'] = true;
            $info['name'] = $fileinfo['basename'];
            $info['path'] = $fileinfo['dirname'];
            $info['basename'] = $fileinfo['basename'];
            $info['extension'] = $fileinfo['extension'];
            $info['fullpath'] = $this->filename;
            $info['realpath'] = realpath($this->filename);
            $info['filesize'] = filesize($this->filename);
            $info['filemtime'] = filemtime($this->filename);

            $date = new \DateTime();
            $date->setTimestamp($info['filemtime']);
            $info['date'] = $date;

            $this->info = array_merge($this->info, $info);
        }

        return $this->info;
    }
    // }}}
    // {{{ getImageInfo()
    /**
     * gets information about images
     *
     * @return array
     */
    protected function getImageInfo() {
        $imageinfo = @getimagesize($this->filename, $extras);
        if ($imageinfo[2] > 0) {
            $info = array();

            $info['isImage'] = true;
            $info['width'] = $imageinfo[0];
            $info['height'] = $imageinfo[1];
            $info['mime'] = $imageinfo['mime'];
            $info['displayAspectRatio'] = $info['width'] / $info['height'];

            $this->info = array_merge($this->info, $info);
        }
        if(isset($extras['APP13'])) {
            $info = array();
            $iptc = iptcparse($extras['APP13']);

            foreach ($iptc as $key => $value) {
                if (isset($this->iptcHeaders[$key])) {
                    $info['iptc' . $this->iptcHeaders[$key]] = implode(", ", str_replace("\\n", " ", $value));
                } else {
                    $info['iptc' . $key] = implode(",", $value);
                }
            }

            $this->info = array_merge($this->info, $info);
        }
        if (function_exists("exif_read_data") && ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/tif')) {
            $exif = exif_read_data($this->filename);
            if ($exif) {
                $info = array();
                foreach ($exif as $key => $value) {
                    if (is_string($value)) {
                        $info['exif' . $key] = $value;
                    }
                    if ($key == "COMPUTED") {
                        foreach ($value as $keySub => $valueSub) {
                            $info['exifComputed' . $keySub] = $valueSub;
                        }
                    }
                }

                $this->info = array_merge($this->info, $info);
            }
        }

        // copyright
        if (!empty($this->info['iptcCopyright'])) {
            $this->info['copyright'] = $this->info['iptcCopyright'];
        } else if (!empty($this->info['exifComputedCopyright'])) {
            $this->info['copyright'] = $this->info['exifComputedCopyright'];
        } else {
            $this->info['copyright'] = "";
        }

        // description
        if (!empty($this->info['iptcCaption'])) {
            $this->info['description'] = $this->info['iptcCaption'];
        } else if (!empty($this->info['exifImageDescription'])) {
            $this->info['description'] = $this->info['exifImageDescription'];
        } else {
            $this->info['description'] = "";
        }

        // keywords
        if (!empty($this->info['iptcKeywords'])) {
            $this->info['keywords'] = $this->info['iptcKeywords'];
        } else {
            $this->info['keywords'] = "";
        }

        return $this->info;
    }
    // }}}
    // {{{ getMediaInfo()
    /**
     * gets information about videos
     *
     * @return array
     */
    protected function getMediaInfo() {
        $info = array(
            'streams' => array(
                'video' => array(),
                'audio' => array(),
            ),
        );

        $fileArg = escapeshellarg($this->filename);
        $cmd = "{$this->ffprobe} -show_streams -show_format {$fileArg}";
        $result = $this->call($cmd);

        $streams = array();
        $format = array();
        $tmp = array();

        foreach ($result as $line) {
            if ($line == "[STREAM]" || $line == "[FORMAT]") {
                $tmp = array();
            } elseif ($line == "[/STREAM]") {
                $streams[] = $tmp;
            } elseif ($line == "[/FORMAT]") {
                $format = $tmp;
            } else if (strpos($line, "=") !== false){
                list($key, $value) = explode("=", $line, 2);
                $tmp[$key] = $value;
            }
        }

        $info['duration'] = $format['duration'];
        $info['bitrate'] = $format['bit_rate'];

        foreach ($streams as $stream) {
            if ($stream['codec_type'] == "video") {
                $info['streams']['video'][] = $stream;
            } elseif ($stream['codec_type'] == "audio") {
                $info['streams']['audio'][] = $stream;
            }
        }

        $info['width'] = $info['streams']['video'][0]['width'];
        $info['height'] = $info['streams']['video'][0]['height'];

        if ($info['streams']['video'][0]['display_aspect_ratio'] == "0:1") {
            $info['displayAspectRatio'] = $info['width'] / $info['height'];
        } else {
            list($aspectW, $aspectH) = explode(":", $info['streams']['video'][0]['display_aspect_ratio']);
            $info['displayAspectRatio'] = $aspectW / $aspectH;
        }
        if (count($info['streams']['video']) > 0 && $info['duration'] > 1) {
            $info['isVideo'] = true;
        }
        if (count($info['streams']['audio']) > 0) {
            $info['isAudio'] = true;
        }

        $this->info = array_merge($this->info, $info);

        return $this->info;
    }
    // }}}

    // {{{ clearInfo()
    public function clearInfo($filename = null) {
        if (!is_null($this->cache)) {
            $identifier = $filename . ".ser";
            $this->cache->clear($identifier);
        }
    }
    // }}}

    // {{{ isImage
    /**
     * checks if filename has a video extension
     *
     * @return bool
     */
    public function isImage() {
        return $this->info['isImage'];
    }
    // }}}
    // {{{ isVideo
    /**
     * checks if filename has a video extension
     *
     * @return bool
     */
    public function isVideo() {
        return $this->info['isVideo'];
    }
    // }}}
    // {{{ isAudio
    /**
     * checks if filename has a video extension
     *
     * @return bool
     */
    public function isAudio() {
        return $this->info['isAudio'];
    }
    // }}}

    // {{{ hasImageExtension
    /**
     * checks if filename has a video extension
     *
     * @return bool
     */
    protected function hasImageExtension() {
        $extensions = array("png", "jpg", "jpeg", "gif");

        return in_array(strtolower($this->info['extension']), $extensions);

    }
    // }}}
    // {{{ hasMediaExtension
    /**
     * checks if filename has a video extension
     *
     * @return bool
     */
    protected function hasMediaExtension()
    {
        $extensions = array(
            "avi",
            "flv",
            "m4v",
            "mov",
            "mp4",
            "mpg",
            "ogg",
            "webm",
            "wmv",

            "aac",
            "mp3",
        );

        return in_array(strtolower($this->info['extension']), $extensions);
    }
    // }}}

    // {{{ call()
    /**
     * Call
     *
     * Executes the shell command.
     *
     * @param string $cmd - command to execute
     *
     * @return string output
     */
    private function call($cmd) {
        $cmd = escapeshellcmd($cmd) . ' 2>&1';

        exec($cmd, $output, $var);

        if (is_array($output)) {
            //$output = implode("\n", $output);
        }

        if ($var == 0) {
            //throw new ffmpegException("Error executing ffmpeg\n{$cmd}:\n\n{$output}");
        }

        return $output;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
