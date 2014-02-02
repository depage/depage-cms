<?php 
/**
 * @file    FileSize.php
 * @brief   Formatter for filesizes
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace Depage\Formatters;

class FileSize
{
    // {{{ format()
    public function format($size)
    {
        $kb = 1024;         // Kilobyte
        $mb = 1024 * $kb;   // Megabyte
        $gb = 1024 * $mb;   // Gigabyte
        $tb = 1024 * $gb;   // Terabyte
           
        if ($size < $kb) {
            return $size . 'B';
        } elseif ($size < $mb) {
            return round($size / $kb, 0) . 'KB';
        } elseif ($size < $gb) {
            return round($size / $mb, 1) . 'MB';
        } elseif ($size < $tb) {
            return round($size / $gb, 1) . 'GB';
        } else {
            return round($size / $tb, 1) . 'TB';
        }
    }
    // }}}
}
