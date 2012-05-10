<?php
/**
 * @file    framework/html/link.php
 *
 * depage html module
 *
 *
 * copyright (c) 2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
namespace depage\html;

class link {
    protected $link = null;
    protected $protocol = null;
    protected $locale = null;

    // {{{ constructor
    /**
     * builds a localized link
     *
     * @param   $link (string) page to link to 
     * @param   $protocol (string) protocol to use for the link
     * @param   $locale (string) locale to link to 
     */
    public function __construct($link, $protocol = null, $locale = null) {
        $this->link = $link;
        $this->protocol = $protocol;
        $this->locale = $locale;
    }
    // }}}
    // {{{ __toString
    /**
     * builds a localized link
     *
     * @param   $link (string) page to link to 
     * @param   $protocol (string) protocol to use for the link
     * @param   $locale (string) locale to link to 
     */
    public function __toString() {
        if (is_null($this->locale)) {
            $lang = DEPAGE_LANG;
        } else {
            $lang = \Locale::getPrimaryLanguage($this->locale);
        }
        if (!is_null($this->protocol)) {
            if ($this->protocol == "auto") {
                $base = DEPAGE_BASE;
            } else {
                $base = preg_replace("/.*:\/\//", $this->protocol . "://", DEPAGE_BASE);
            }
        } else {
            $base = "";
        }
        if (DEPAGE_URL_HAS_LOCALE) {
            return $base . $lang . '/' . $this->link;
        } else {
            return $base . $this->link;
        }
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
