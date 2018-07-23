<?php
/**
 * @file    TimeAbsolute.php
 * @brief   Formatter for natural time display
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace Depage\Formatters;

class TimeAbsolute
{
    // {{{ format()
    public function format($seconds)
    {
        $s = $seconds % 60;
        $seconds = ($seconds - $s);

        $m = $seconds / 60;
        $seconds = ($seconds - $m * 60);

        return sprintf("%'.02d:%'.02d", $m, $s);

        // @todo add support for hours
        //$h = $seconds / 60 / 60;
        //return sprintf("%'.02d:%'.02d:%'.02d", $h, $m, $s);
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
