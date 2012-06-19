<?php

/**
 * Natural date class.
 *
 * Note: won't work nicely with slavic language, as they have 3 different plural forms.
 *
 * @author Stefan
 */
namespace depage\datetime;

class DateTime extends \DateTime {

    static public function createFromFormat($format, $time, $timezone = null) {
        $dt = new static();
        return $dt->setTimestamp(parent::createFromFormat($format, $time)->getTimestamp());

    }
    
    public function getDiffNatural() {
        $now_date = new \DateTime();
        $now_ti = $now_date->getTimestamp();
        $date_ti = $this->getTimestamp();
        $ti = $now_ti - $date_ti;

        if ($ti < 1) {
            return _("now"); // date diff: now
        } else if ($ti < 60) {
            return _("just now"); // date diff: just now
        } else if ($ti < 3600) {
            $diff = round($ti / 60);
            return $diff == 1 ? sprintf(_("%d minute ago"), $diff) : sprintf(_("%d minutes ago"), $diff); // date diff: in minutes
        } else if ($ti < 86400) {
            $diff = round($ti / 60 / 60);
            return $diff == 1 ? sprintf(_("%d hour ago"), $diff) : sprintf(_("%d hours ago"), $diff); // date diff: in hours
//        } else if ($ti < 172800) {
//            return _("yesterday");
        } else if ($ti < 604800) {
            if (false) {
                _("Monday");
                _("Tuesday");
                _("Wednesday");
                _("Thursday");
                _("Friday");
                _("Saturday");
                _("Sunday");
            }
            return _($this->format('l'));
        } else if ($ti < 691200) {
            $diff = round($ti / 60 / 60 / 24 / 7);
            return $diff == 1 ? sprintf(_("%d week ago"), $diff) : sprintf(_("%d weeks ago"), $diff); // date diff: in weeks
        } else if ($this->format('Y') == $now_date->format('Y')) {
            return \html::format_date($this, 'd. MMM');
        } else {
            // Full Date dd. MMM YY
            return \html::format_date($this, \IntlDateFormatter::SHORT);
        }
    }
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
