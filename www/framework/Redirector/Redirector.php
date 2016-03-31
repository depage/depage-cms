<?php

namespace Depage\Redirector;

/**
 * brief Redirector
 * Class Redirector
 */
class Redirector
{
    /**
     * @brief langugaes
     **/
    protected $langugaes = array();

    /**
     * @brief pages
     **/
    protected $pages = array();

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct()
    {

    }
    // }}}

    // {{{ setLanguages()
    /**
     * @brief setLanguages
     *
     * @param mixed $
     * @return void
     **/
    public function setLanguages($languages)
    {
        $this->languages = $languages;

        return $this;
    }
    // }}}
    // {{{ setPages()
    /**
     * @brief setPages
     *
     * @param mixed $
     * @return void
     **/
    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
    }
    // }}}

    // {{{ getLanguageByBrowser()
    /**
     * @brief getLanguageByBrowser
     *
     * @param mixed $param
     * @return void
     **/
    public function getLanguageByBrowser($acceptString)
    {
        $language = $this->languages[0];

        $browserLanguages = explode(',', $acceptString);

        foreach ($browserLanguages as $lang) {
            $currentLanguages = explode(';', $lang);
            $currentLanguages = explode('-', $currentLanguages[0]);
            $currentLanguage = trim($currentLanguages[0]);
            if (in_array($currentLanguage, $this->languages)) {
                $language = $currentLanguage;
                break;
            }
        }

        return $language;
    }
    // }}}
    // {{{ getAlternativePage()
    /**
     * @brief getAlternativePage
     *
     * @param mixed
     * @return void
     **/
    public function getAlternativePage($request)
    {
        $page = "";
        $request = explode("/", $request);

        //search for pages
        while ($page == "" && count($request) > 1) {
            $tempurl = implode("/", $request) . "/";
            foreach ($this->pages as $apage) {
                if (substr($apage, 0, strlen($tempurl)) == $tempurl) {
                    $page = $apage;

                    break;
                }
            }
            array_pop($request);
        }

        if ($page == "") {
            $page = $availablePages[0];
        }

        return $page;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
