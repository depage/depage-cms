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
        'identify'     => "identify",
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
            $info['extension'] = $fileinfo['extension'] ?? "";
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
        $imageinfo = $this->getImageSize($this->filename, $extras);
        if (isset($imageinfo[1]) && $imageinfo[1] > 0) {
            $info = array();

            $info['isImage'] = true;
            $info['width'] = $imageinfo[0];
            $info['height'] = $imageinfo[1];
            $info['mime'] = $imageinfo['mime'];
            $info['displayAspectRatio'] = $info['width'] / $info['height'];
            if (is_nan($info['displayAspectRatio'])) {
                $info['displayAspectRatio'] = null;
            }

            $this->info = array_merge($this->info, $info);
        }
        if (isset($extras['APP13'])) {
            $info = array();
            $iptc = iptcparse($extras['APP13']);

            if (is_array($iptc)) {
                foreach ($iptc as $key => $value) {
                    if (isset($this->iptcHeaders[$key])) {
                        $info['iptc' . $this->iptcHeaders[$key]] = $this->forceUTF8String($value);
                    }
                }
            }

            $this->info = array_merge($this->info, $info);
        }
        if (function_exists("exif_read_data") && isset($info['mime']) && ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/tif')) {
            $exif = @exif_read_data($this->filename);
            if ($exif) {
                $info = array();
                foreach ($exif as $key => $value) {
                    if (is_string($value)) {
                        $info['exif' . $key] = $this->forceUTF8String($value);
                    }
                    if ($key == "COMPUTED") {
                        foreach ($value as $keySub => $valueSub) {
                            $info['exifComputed' . $keySub] = $this->forceUTF8String($value);
                        }
                    }
                }

                $this->info = array_merge($this->info, $info);
            }
        }

        // copyright
        $this->info['copyright'] = self::nonEmpty(
            $this->info['iptcCopyright'] ?? '',
            $this->info['exifCopyright'] ?? '',
            $this->info['exifComputedCopyright'] ?? ''
        );

        // description
        $this->info['description'] = self::nonEmpty(
            $this->info['iptcCaption'] ?? '',
            $this->info['exifImageDescription'] ?? ''
        );

        // keywords
        $this->info['descrikeywordsption'] = self::nonEmpty(
            $this->info['iptcKeywords'] ?? ''
        );

        return $this->info;
    }
    // }}}
    // {{{ nonEmtpy()
    /**
     * @brief nonEmtpy
     *
     * @param mixed ...$param
     * @return void
     **/
    public static function nonEmpty(...$params)
    {
        foreach ($params as $p) {
            if (!empty($p)) {
                return $p;
            }
        }

        return "";
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

        foreach ($format as $key => $value) {
            if (strpos($key, "TAG:") === 0) {
                $key = str_replace(":", "_", strtolower($key));
                $info[$key] = $value;
            }
        }

        foreach ($streams as $stream) {
            if ($stream['codec_type'] == "video") {
                $info['streams']['video'][] = $stream;
            } elseif ($stream['codec_type'] == "audio") {
                $info['streams']['audio'][] = $stream;
            }
        }

        $vStream = $info['streams']['video'][0] ?? false;
        if ($vStream) {
            $info['width'] = $vStream['width'];
            $info['height'] = $vStream['height'];

            if ($vStream['display_aspect_ratio'] == "0:1" ||
                $vStream['display_aspect_ratio'] == "N/A"
            ) {
                $info['displayAspectRatio'] = $info['width'] / $info['height'];
            } else {
                list($aspectW, $aspectH) = explode(":", $vStream['display_aspect_ratio']);
                $info['displayAspectRatio'] = (int) $aspectW / (int) $aspectH;
                if (is_nan($info['displayAspectRatio'])) {
                    $info['displayAspectRatio'] = null;
                }
            }
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
        $extensions = array("png", "jpg", "jpeg", "gif", "webp", "tif", "tiff", "pdf", "eps");

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

    // {{{ forceUTF8String()
    /**
     * @brief forceUTF8String
     *
     * @param mixed $
     * @return void
     **/
    protected function forceUTF8String($value)
    {
        if (is_array($value)) {
            $str = [];
            foreach ($value as $v) {
                $str[] = str_replace("\\n", " ", $this->forceUTF8String($v));
            }

            return implode(", ", $str);
        }

        return mb_convert_encoding($value, "UTF-8", "UTF-8");
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
        $cmd = escapeshellcmd($cmd);

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
    // {{{ getImageSize()
    /**
     * @brief getImageSize
     *
     * @param mixed $file
     * @return void
     **/
    protected function getImageSize($filename, &$extras)
    {
        $info = @getimagesize($filename, $extras);

        if (!$info) {
            $fileArg = escapeshellarg($filename);
            if (substr($filename, -4) === ".pdf") {
                $fileArg .= "[0]";
            }
            $cmd = "{$this->identify} -ping -format \"%wx%h\" {$fileArg}";
            $result = $this->call($cmd);
            $info = explode('x', $result[0] ?? '');

            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $info['mime'] = $finfo->file($filename);
        }

        return $info;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
