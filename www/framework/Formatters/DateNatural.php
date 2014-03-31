<?php 
/**
 * @file    DateNatural.php
 * @brief   Formatter for natural Dates
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 */
namespace Depage\Formatters;

class DateNatural
{
    // {{{ format()
    public function format($date, $addTime = false)
    {
        $time = "";

        if (is_integer($date)) {
            $datetime = new \DateTime();
            $date = $datetime->setTimestamp($date);
        }
        if ($date instanceof \DateTime) {
            $now = new \DateTime("now");
            $yesterday = new \DateTime("yesterday");

            $date_ti = $date->getTimestamp();
            $now_ti = $now->getTimestamp();
            $diff = $now_ti - $date_ti;
        } else {
            throw new InvalidArgumentException("date must be an instance of DateTime. Input was " . $date);
        }

        if ($addTime) {
            $time = ", " . $date->format("H:i");
        }

        if ($diff < 1) {
            return _("now"); // date diff: now
        } elseif ($diff < 60) {
            return _("just now"); // date diff: just now
        } elseif ($diff < 3600) {
            $diff = round($diff / 60);
            return sprintf(ngettext("%d minute ago", "%d minutes ago", $diff), $diff); // date diff: in minutes
        } elseif ($diff < 86400) {
            $diff = round($diff / 60 / 60);
            return sprintf(ngettext("%d hour ago", "%d hours ago", $diff), $diff); // date diff: in hours
        } elseif ($date->format("Ymd") == $yesterday->format("Ymd")) {
            return _("Yesterday") . $time;
        } elseif ($diff < 604800) {
            return _($date->format('l')) . $time;

            // Keep to have names localized by gettext script
            if (false) {
                _("Monday");
                _("Tuesday");
                _("Wednesday");
                _("Thursday");
                _("Friday");
                _("Saturday");
                _("Sunday");
            }
        } else if ($diff < 691200) {
            $diff = round($diff / 60 / 60 / 24 / 7);
            return ($diff == 1 ? sprintf(_("%d week ago"), $diff) : sprintf(_("%d weeks ago"), $diff)) . $time; // date diff: in weeks
        } else if ($date->format('Y') == $now->format('Y')) {
            return \html::format_date($date, 'd. MMM') . $time;
        } else {
            // Full Date dd. MMM YY
            return \html::format_date($date, \IntlDateFormatter::SHORT);
        }
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
