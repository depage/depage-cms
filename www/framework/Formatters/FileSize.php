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
    protected $suffix = "";

    // {{{ constructor()
    public function __construct($base = 1024, $precision = 2)
    {
        $this->base = $base;
        $this->precision = $precision;

        if ($this->base == "1024") {
            $this->suffix = "iB";
        } elseif ($this->base == "1000") {
            $this->suffix = "B";
        }
    }
    // }}}
    // {{{ format()
    public function format($size)
    {
        $exts = array(
            "",
            "K",
            "M",
            "G",
            "T",
            "P",
            "E",
            "Z",
            "Y",
        );
        $last = end($exts);

        foreach ($exts as $key => $ext) {
            $minsize = pow($this->base, $key);
            $maxsize = $minsize * $this->base;

            if ($size < $maxsize || $ext === $last) {
                if ($ext === "") {
                    $suffix = "B";
                } else {
                    $suffix = $this->suffix;
                }
                return round($size / $minsize, $this->precision) . $ext . $suffix;
            }
        }
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
