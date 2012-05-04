<?php 
/**
 * @file    video.php
 * @brief   video conversion class which uses ffmpeg
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 * @author  Ben Wallis [benedict_wallis@yahoo.co.uk]
 **/

/*
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
 */

namespace depage\media;

class video {
    var $defaults = array(
        'ffmpeg' => "ffmpeg",
        'ffprobe' => "ffprobe",
        'qtfaststart' => "qt-faststart",
        'width' => 640,
        'height' => 360,
        'vrate' => "1000k",
        'arate' => "128k",
        'qmin' => 3,
        'qmax' => 5,
        'bufsize' => 4096,
    );

    var $ffmpeg;
    var $ffprobe;

    // {{{ constructor
    public function __construct($options) {
        $options = array_change_key_case($options);
        foreach ($this->defaults as $option => $default) {
            $this->$option = isset($options[strtolower($option)]) ? $options[strtolower($option)] : $default;
        }
    }
    // }}}
    // {{{ toMP4
    public function toMp4($file) {
        $out = $this->stripExt($file) . '.mp4';
        
        $vcodec = 'libxvid';
        $acodec = 'aac';
        $extra = '-strict experimental -f mp4';
        
        $this->convert($file, $out, $vcodec, $acodec, $extra);
        $this->mp4faststart($out);
        
        return $this->getInfo($out);
    }
    // }}}
    // {{{ toWebM
    public function toWebM($file) {
        $out = $this->stripExt($file) . '.webm';
        
        $vcodec = 'libvpx';
        $acodec = 'libvorbis';
        $extra = '-g 30 -f webm';
        
        return $this->convert($file, $out, $vcodec, $acodec, $extra);
    }
    // }}}
    // {{{ mp4faststart()
    public function mp4faststart($file) {
        $in = $file . ".tmp.mp4";
        $out = $file;

        @unlink($in);
        rename($out, $in);

        $inArg = escapeshellarg($in);
        $outArg = escapeshellarg($out);

        $cmd = "{$this->qtfaststart} $inArg $outArg";
        $this->call($cmd);

        unlink($in);
    }
    // }}}
    
    // {{{ convert
    public function convert($infile, $outfile, $vcodec, $acodec, $extra) {
        $infileArg = escapeshellarg($infile);
        $outfileArg = escapeshellarg($outfile);
        
        $cmd = "{$this->ffmpeg} -i {$infileArg} -vcodec {$vcodec} -qmin {$this->qmin} -qmax {$this->qmin} -bufsize {$this->bufsize} -acodec {$acodec} {$extra} -s {$this->width}x{$this->height} -ab {$this->arate} -b:v {$this->vrate} -y {$outfileArg}";
        $this->call($cmd);
        
        return $this->getInfo($outfile);
    }
    // }}}
    
    // {{{ getDuration
    public function getDuration($file) {
        $info = $this->getInfo($file);
        return $info['duration'];
    }
    // }}}
    // {{{ getInfo
    public function getInfo($file) {
        $fileArg = escapeshellarg($file);
        $cmd = $this->ffprobe . " {$fileArg}";
        $info = $this->call($cmd);
        echo($info);
        
        $duration = 0;
        $filesize = 0;
        $bitrate = 0;
        $format = '';
        
        if (preg_match('/Input #0, (.\w+)/s', $info, $matches)) {
            $format = $matches[1];
        }
        
        $matches = null;
        if (preg_match('/Duration: ((\d+):(\d+):(\d+(\.\d+))?)/s', $info, $matches)) {
            $duration = ($matches[2] * 3600) + ($matches[3] * 60) + $matches[4];
        } else {
            throw new \exception("Could not read ffmpeg info.");
        }
        
        if (preg_match('/bitrate: (.\d+)/s', $info, $matches)) {
            $bitrate = $matches[1];
            $filesize = $bitrate * $duration * 1000; // TODO verify bitrate is kbs
        }
        
        return array(
            'duration'=>$duration,
            'filesize'=>$filesize,
            'format'=>$format,
            'filename'=>basename($file)
        );
    }
    // }}}
    // {{{ getThumbnails
    /*
    -i = input file
    -deinterlace = deinterlace pictures
    -an = disable audio recording
    -ss = start time in the video (seconds)
    -t = duration of the recording (seconds)
    -r = set frame rate
    -y = overwrite existing file
    -s = resolution size
    -f = force format
    */    
    public function getThumbnails($file, $width, $height) {
        $duration = $this->getDuration($file);
        $thumbnails = array();
        $path = $this->stripExt($file);
        $fileArg = escapeshellarg($file);
        
        $basename = basename($path);
        
        for ($i = 1; $i <= 5; $i++ ) {
            $out = $path . $i.  '.jpg';
            $interval = $duration * $i / 6;
            $cmd = '"' . $this->ffmpeg . "\" -i {$fileArg} -f mjpeg -an -y -ss {$interval} -s {$width}x{$height} \"{$out}\"";
            $this->call($cmd);
            $thumbnails[$basename . $i . '.jpg'] = $out;
        }
        return $thumbnails;
    }
    // }}}
    
    // {{{ call()
    private function call($cmd) {
        $cmd = escapeshellcmd($cmd) . ' 2>&1';
        
        exec($cmd, $output, $var);
        
        if (is_array($output)) {
            $output = implode('', $output);
        }
        
        /*
        if ($var) {
            var_dump($cmd);
            var_dump($output);
            var_dump($var);
            
            //throw new \Exception('Error executing ffmpeg');
        }
         */
        
        return $output;
    }
    // }}}
    // {{{ stripExt()
    private function stripExt($path) {
        $info = pathinfo($path);
        return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'];
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
