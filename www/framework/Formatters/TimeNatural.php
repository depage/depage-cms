<?php
/**
 * @file    TimeNatural.php
 * @brief   Formatter for natural time display
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace Depage\Formatters;

class TimeNatural
{
    // {{{ format()
    public function format($seconds)
    {
        if ($seconds < 60) {
            return sprintf(_("%d sec"), $seconds);
        } else if ($seconds < 90) {
            return "about a minute";
        } else if ($seconds < 120) {
            return "over a minute";
        } else if ($seconds < 3600) {
            return sprintf(_("%d min"), $seconds / 60);
        } else if ($seconds < 86400) {
            return sprintf(_("%.1f h"), $seconds / 3600);
        } else if ($seconds < 86400) {
            return sprintf(_("%.1f h"), $seconds / 3600);
        } else {
            return sprintf(_("%.1f days"), $seconds / 86400);
        }
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
