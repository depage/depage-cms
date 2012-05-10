<?php 
/**
 * @file    video.php
 * @brief   video conversion class which uses ffmpeg
 * 
 * http://stackoverflow.com/questions/5487085/ffmpeg-covert-html-5-video-not-working
 *
 * MP4:
 * ffmpeg -i "INPUTFILE" -b 1500k -vcodec libx264 -vpre slow -vpre baseline -g 30 "OUTPUTFILE.mp4"
 * qt-faststart "INPUTFILE" "OUTPUTFILE"
 *
 * WebM:
 * ffmpeg -i "INPUTFILE"  -b 1500k -vcodec libvpx -acodec libvorbis -ab 160000 -f webm -g 30 "OUTPUTFILE.webm"
 *
 * OGV:
 * ffmpeg -i "INPUTFILE" -b 1500k -vcodec libtheora -acodec libvorbis -ab 160000 -g 30 "OUTPUTFILE.ogv"
 * 
 * 
 * FFMPEG OPTIONS:
 * 
 *   -i = input file
 *   -deinterlace = deinterlace pictures
 *   -an = disable audio recording
 *   -ss = start time in the video (seconds)
 *   -t = duration of the recording (seconds)
 *   -r = set frame rate
 *   -y = overwrite existing file
 *   -s = resolution size
 *   -f = force format
 * 
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Ben Wallis [benedict_wallis@yahoo.co.uk]
 *
 */
namespace depage\media;

// video () {{{
class video {
    // defaults {{{
    /**
     * Defaults array for ffmpeg conversion
     * 
     * @var array
     */
    var $defaults = array(
        'ffmpeg' => "ffmpeg",
        'ffprobe' => "ffprobe",
        'qtfaststart' => "qt-faststart",
        'width' => 640,
        'height' => 360,
        'vrate' => "1000k",
        'arate' => "64k",
        'qmin' => 3,
        'qmax' => 5,
        'bufsize' => 4096,
    );
    // }}}
    
    // properties {{{
    var $ffmpeg = 'ffmpeg';
    var $ffprobe = 'ffprobe';
    var $qtfaststart = 'qtfaststart';
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
    public function __construct($options) {
        $options = array_change_key_case($options);
        foreach ($this->defaults as $option => $default) {
            $this->$option = isset($options[strtolower($option)]) ? $options[strtolower($option)] : $default;
        }
    }
    // }}}
    
    // {{{ toMP4
    /**
     * toMP4
     * 
     * Attempts to convert the provided video file to .mp4 format.
     * 
     * @param string $file - file path
     * 
     * @return multitype:number string Ambigous <string, unknown>
     */
    public function toMp4($infile, $outfile = null) {
        if (empty($outfile)) {
            $outfile = $infile;
        }
        $outfile = $this->stripExt($outfile) . '.mp4';
        
        $vcodec = 'libxvid';
        $acodec = 'aac';
        $extra = '-strict experimental -f mp4';
        $this->convert($infile, $outfile, $vcodec, $acodec, $extra);
        $this->mp4faststart($outfile);
        
        return $this->getInfo($outfile);
    }
    // }}}
    
    // {{{ toWebM
    /**
     * toWebM
     *
     * Attempts to convert the provided video file to .webm format.
     *
     * @param string $infile - file path
     *
     * @return multitype:number string Ambigous <string, unknown>
     */
    public function toWebM($infile, $outfile = null) {
        if (empty($outfile)) {
            $outfile = $infile;
        }
        $outfile = $this->stripExt($outfile) . '.webm';
        
        $vcodec = 'libvpx';
        $acodec = 'libvorbis';
        $extra = '-g 30 -f webm';
        
        return $this->convert($infile, $outfile, $vcodec, $acodec, $extra);
    }
    // }}}
    
    // {{{ mp4faststart()
    /**
     * mp4faststart
     *
     * Attempts to convert the provided video file to .mp4 fast start format.
     *
     * @param string $file - file path
     *
     * @return multitype:number string Ambigous <string, unknown>
     */
    public function mp4faststart($file) {
        $in = $file . ".tmp.mp4";
        $out = $file;
        
        if (file_exists($in)) {
            unlink($in);
        }
        
        rename($out, $in);
        
        $inArg = escapeshellarg($in);
        $outArg = escapeshellarg($out);
        
        $cmd = "{$this->qtfaststart} $inArg $outArg";
        $this->call($cmd);
        
        unlink($in);
    }
    // }}}
    
    // {{{ convert
    /**
     * convert
     * 
     * @param string $infile
     * @param string $outfile
     * @param string $vcodec
     * @param string $acodec
     * @param string $extra
     * 
     * @return multitype:number string Ambigous <string, unknown>
     */
    public function convert($infile, $outfile, $vcodec, $acodec, $extra) {
        $infileArg = escapeshellarg($infile);
        $outfileArg = escapeshellarg($outfile);
        
        $cmd = "{$this->ffmpeg} -i {$infileArg} -vcodec {$vcodec} -qmin {$this->qmin} -qmax {$this->qmin} -bufsize {$this->bufsize} -acodec {$acodec} {$extra} -s {$this->width}x{$this->height} -ab {$this->arate} -b:v {$this->vrate} -y {$outfileArg}";
        $this->call($cmd);
        return $this->getInfo($outfile);
    }
    // }}}
    
    // {{{ getDuration
    /**
     * Get Duration
     * 
     * Wrapper to getInfo() - reads the file info and parses the duration.
     * 
     * @param string $file
     * 
     * @return decimal
     */
    public function getDuration($file) {
        $info = $this->getInfo($file);
        return $info['duration'];
    }
    // }}}
    
    // {{{ getInfo
    /**
     * Get Info
     * 
     * Gets the ffprobe file info and regex parses to return:
     * -  duration / filesize / format / filename
     * 
     * @param string $file
     * @throws \exception
     * 
     * @return array $info = ('filename'=>..., 'duration'=>...,'filesize'=>... ,'bitrate'=>...,'format'=>...,)
     */
    public function getInfo($file) {
        $info = array(
            'filename'=>basename($file),
            'duration'=>0,
            'filesize'=>0,
            'bitrate'=>0,
            'format'=>''
        );
        
        $fileArg = escapeshellarg($file);
        $cmd = "{$this->ffprobe} {$fileArg}";
        $result = $this->call($cmd);
        $matches = null;
        
        if (preg_match('/Input #0, (.\w+)/s', $result, $matches)) {
            $info['format'] = $matches[1];
        } else {
            throw new \exception("Could not read ffmpeg info.");
        }
        
        if (preg_match('/Duration: ((\d+):(\d+):(\d+(\.\d+))?)/s', $result, $matches)) {
            $info['duration'] = ($matches[2] * 3600) + ($matches[3] * 60) + $matches[4];
        } else {
            throw new \exception("Could not read ffmpeg duration.");
        }
        
        if (preg_match('/bitrate: (.\d+)/s', $result, $matches)) {
            $info['bitrate'] = $matches[1];
            $info['filesize'] = $info['bitrate'] * $info['duration'] * 1000; // TODO verify bitrate is kbs
        } else {
            throw new \exception("Could not read ffmpeg bitrate.");
        }
        
        return $info;
    }
    // }}}
    
    // {{{ getThumbnails
    /**
     * getThumbnails
     * 
     * Extracts thumbnails from input file at given number of intervals.
     * 
     * @param string $infile - input file path
     * @param string $outfile - output file path
     * @param int $width
     * @param int $height
     * @param int intervals - number of thumbnails to return
     * 
     * @return array("thumb1.jpg"=>"/path/thumb1.jpg", ...)
     */
    public function getThumbnails($infile, $outfile, $width = null, $height = null, $intervals = 5) {
        if (empty($width)){
            $width = $this->width;
        }
        if (empty($height)){
            $height = $this->height;
        }
        $duration = $this->getDuration($infile);
        $thumbnails = array();
        $path =  $this->stripExt($outfile);
        
        $fileArg = escapeshellarg($infile);
        
        $basename = basename($path);
        
        for ($i = 1; $i <= $intervals; $i++ ) {
            $out = $path . $i.  '.jpg';
            $interval = $duration * $i / ($intervals + 1);
            $cmd = '"' . $this->ffmpeg . "\" -i {$fileArg} -f mjpeg -an -y -ss {$interval} -s {$width}x{$height} \"{$out}\"";
            $this->call($cmd);
            $thumbnails[$basename . $i . '.jpg'] = $out;
        }
        return $thumbnails;
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
            $output = implode('', $output);
        }
        
        if ($var) {
            var_dump($cmd);
            var_dump($output);
            var_dump($var);
            
            //throw new \Exception('Error executing ffmpeg');
        }
        return $output;
    }
    // }}}
    
    // stripExt() {{{
    /**
     * Strip File Extension
     * 
     * @param string - file path
     * @return string
     */
    private function stripExt($file) {
        $pathinfo = pathinfo($file);
        return "{$pathinfo['dirname']}/{$pathinfo['filename']}";
    }
    // }}}
}
// }}}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */