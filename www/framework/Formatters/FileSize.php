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
    protected $precision = 2;
    protected $base = 1024;

    // {{{ format()
    public function format($size)
    {
        $kb = $this->base;         // Kilobyte
        $mb = $this->base * $kb;   // Megabyte
        $gb = $this->base * $mb;   // Gigabyte
        $tb = $this->base * $gb;   // Terabyte
           
        if ($size < $kb) {
            return $size . 'B';
        } elseif ($size < $mb) {
            return round($size / $kb, $this->precision) . 'KB';
        } elseif ($size < $gb) {
            return round($size / $mb, $this->precision) . 'MB';
        } elseif ($size < $tb) {
            return round($size / $gb, $this->precision) . 'GB';
        } else {
            return round($size / $tb, $this->precision) . 'TB';
        }
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
