<?php
/**
 * @file    lib_media.php
 *
 * Mediainfo Framework Library
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

// {{{ define and includes
if (!function_exists('die_error')) require_once('lib_global.php');

require_once('lib_files.php');
// }}}

/**
 * class to get infos from mediafiles
 */
class mediainfo {
    // {{{ get_file_info_xml()
    function get_file_info_xml($file) {
        $info = mediainfo::get_file_info($file);
        $xml = "<file";
        foreach ($info as $key => $value) {
            $xml .= " $key=\"" . htmlentities($value) . "\"";
        }
        $xml .= " />";

        return $xml;
    }
    // }}}
    // {{{ get_file_info()
    function get_file_info($file) {
        global $conf;
        $info = array();
        if (file_exists($file)) {
            $fileinfo = pathinfo($file);
            $fs_access = new fs_local();
            
            $info['exists'] = "true";
            //$info['name'] = $file;
            $info['name'] = $fileinfo['basename'];
            $info['dirname'] = $fileinfo['dirname'];
            $info['basename'] = $fileinfo['basename'];
            $info['extension'] = $fileinfo['extension'];
            $info['size'] = $fs_access->f_size_format($file);
            $info['date'] = $conf->dateUTC($conf->date_format_UTC, filemtime($file));
            $info = array_merge($info, mediainfo::get_img_info($file));
            if (strtolower($info['extension']) == "flv") {
                $info = array_merge($info, mediainfo::get_flv_info($file));
            }
        } else {
            $info['exists'] = "false";
        }

        return $info;
    }
    // }}}
    // {{{ get_img_info()
    function get_img_info($file) {
        $info = array();
        if (file_exists($file)) {
            $imageinfo = @getimagesize($file);
            if ($imageinfo[2] > 0) {
                $info['width'] = $imageinfo[0];
                $info['height'] = $imageinfo[1];
                $info['mime'] = $imageinfo['mime'];
            }
        }

        return $info;
    }
    // }}}
    // {{{ get_flv_info()
    function get_flv_info($file) {
        $info = array();
        if (file_exists($file)) {
            $filesize = filesize($file);
            $handle = fopen($file, "r");

            // read FLV-file header (3 first bytes)
            $header = fread($handle, 3);
            if ($header == "FLV") {
                // get tag-length (last 3 bytes)
                fseek($handle, $filesize - 3);
                $tag = fread($handle, 3);
                $taglen = hexdec(bin2hex($tag));

                if ($filesize > $taglen) {
                    // get first duration from tag-info (first 3 bytes from tagdata)
                    fseek($handle, $filesize - $taglen);
                    $dur = fread($handle, 3);
                    $info['duration'] = hexdec(bin2hex($dur)) / 1000;
                    $info['mime'] = "video/x-flv";

                    return $info;
                }
            }
            fclose($handle);
        }

        return $info;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
