<?php 
/**
 * @file    mediainfo.php
 * @brief   file-/mediainfo class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace depage\media;

class mediainfo {
    // defaults {{{
    /**
     * Defaults array for ffmpeg conversion
     * 
     * @var array
     */
    var $defaults = array(
        'cache'       => null,
        'ffprobe'     => "ffprobe",
        'mplayer'     => "mplayer",
    );
    // }}}
    
    // properties {{{
    protected $ffprobe;
    protected $mplayer;
    protected $cache;
    protected $info = array(
        'exists' => false,
        'isImage' => false,
        'isVideo' => false,
        'isAudio' => false,
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
    public function __construct($filename, $options) {
        $this->filename = $filename;
        $options = array_change_key_case($options);
        foreach ($this->defaults as $option => $default) {
            $this->$option = isset($options[$option]) ? $options[$option] : $default;
        }
    }
    // }}}
    
    // {{{ getInfo()
    /**
     * get information about the file
     * 
     * @return array
     */
    public function getInfo() {
        $this->info = $this->getBasicInfo();

        if ($this->info['exists']) {
            // @todo add caching
            if ($this->hasImageExtension()) {
                $this->getImageInfo();
            } else if ($this->hasMediaExtension()) {
                $this->getMediaInfo();
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
    public function getBasicInfo() {
        if (file_exists($this->filename)) {
            $this->info['exists'] = true;

            $fileinfo = pathinfo($this->filename);

            $this->info['name'] = $fileinfo['basename'];
            $this->info['path'] = $fileinfo['dirname'];
            $this->info['basename'] = $fileinfo['basename'];
            $this->info['extension'] = $fileinfo['extension'];
            $this->info['fullpath'] = $this->filename;
            $this->info['realpath'] = realpath($this->filename);
            $this->info['filesize'] = filesize($this->filename);
            $this->info['date'] = filemtime($this->filename);
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
        $info = array();

        $imageinfo = @getimagesize($this->filename);
        if ($imageinfo[2] > 0) {
            $info['isImage'] = true;
            $info['width'] = $imageinfo[0];
            $info['height'] = $imageinfo[1];
            $info['mime'] = $imageinfo['mime'];
            $info['displayAspectRatio'] = $info['width'] / $info['height'];

            $this->info = array_merge($this->info, $info);
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
            'streams'     => array(
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
            } else {
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
            list($aspectW, $aspectH) = implode(":", $info['streams']['video'][0]['display_aspect_ratio']);
            $info['displayAspectRatio'] = $aspectW / $aspectH;
        }

        $this->info = array_merge($this->info, $info);

        return $this->info;
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
