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
    public function format($date)
    {
        if (!$date instanceof \DateTime) {
            $datetime = new \DateTime();
            $date = $datetime->setTimestamp($date);
        }
        if ($date instanceof \DateTime) {
            $now_date = new \DateTime();
            $now_ti = $now_date->getTimestamp();
            $date_ti = $date->getTimestamp();
            $ti = $now_ti - $date_ti;
        } else {
            throw new InvalidArgumentException("date must be an instance of DateTime. Input was " . $date);
        }

        if ($ti < 1) {
            return _("now"); // date diff: now
        } elseif ($ti < 60) {
            return _("just now"); // date diff: just now
        } elseif ($ti < 3600) {
            $diff = round($ti / 60);
            return sprintf(ngettext("%d minute ago", "%d minutes ago", $diff), $diff); // date diff: in minutes
        } elseif ($ti < 86400) {
            $diff = round($ti / 60 / 60);
            return sprintf(ngettext("%d hour ago", "%d hours ago", $diff), $diff); // date diff: in hours
        //} elseif ($ti < 172800) {
            //return _("yesterday");
        } elseif ($ti < 604800) {
            if (false) {
                _("Monday");
                _("Tuesday");
                _("Wednesday");
                _("Thursday");
                _("Friday");
                _("Saturday");
                _("Sunday");
            }
            return _($date->format('l'));
        } else if ($ti < 691200) {
            $diff = round($ti / 60 / 60 / 24 / 7);
            return $diff == 1 ? sprintf(_("%d week ago"), $diff) : sprintf(_("%d weeks ago"), $diff); // date diff: in weeks
        } else if ($date->format('Y') == $now_date->format('Y')) {
            return html::format_date($date, 'd. MMM');
        } else {
            // Full Date dd. MMM YY
            return html::format_date($date, IntlDateFormatter::SHORT);
        }
    }
    // }}}
}
