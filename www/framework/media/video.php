<?php

namespace depage\media;

class video
{
    const FFMPEG = '/opt/local/bin/ffmpeg';
    
    public static function toMp4($file, $width, $height) {
        $out = self::stripExt($file) . '.mp4';
        
        $codec = 'libxvid';
        $maxrate = '1000';
        $qmin = 3;
        $qmax = 5;
        $bufsize = 4096;
        
        $cmd = self::FFMPEG . " -i \"{$file}\" -f mp4 -vcodec {$codec} -maxrate {$maxrate} -qmin {$qmin} -qmax {$qmin} -bufsize {$bufsize} -g 300 -acodec aac -strict experimental -mbd 2 -s {$width}x{$height} -ab 256 -b:v 400 \"{$out}\"";
        
        self::call($cmd);
        
        return self::getInfo($out);
    }
    
    public static function getDuration($file) {
        $info = self::getInfo($file);
        return $info['duration'];
    }
    
    private static function getInfo($file) {
        $cmd = self::FFMPEG . " -i {$file}";
        $info = self::call($cmd);
        
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
    public static function getThumbnails($file, $width, $height) {
        $duration = self::getDuration($file);
        $thumbnails = array();
        $path = self::stripExt($file);
        
        $basename = basename($path);
        
        for ($i = 1; $i <= 5; $i++ ) {
            $out = $path . $i.  '.jpg';
            $interval = $duration * $i / 6;
            $cmd = '"' . self::FFMPEG . "\" -i \"{$file}\" -f mjpeg -an -y -ss {$interval} -s {$width}x{$height} \"{$out}\"";
            self::call($cmd);
            $thumbnails[$basename . $i . '.jpg'] = $out;
        }
        return $thumbnails;
    }
    
    private static function call($cmd)
    {
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
    
    private static function stripExt($path) {
        $info = pathinfo($path);
        return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'];
    }
}

?>
