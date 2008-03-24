<?php
    // {{{ get_language_by_browser()
    function get_language_by_browser($available_languages) {
        $language = $available_languages[0];

        $browser_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);    
        foreach ($browser_languages as $lang) {
            $actual_language_array = explode(';', $lang);
            $actual_language_array = explode('-', $actual_language_array[0]);
            $actual_language = trim($actual_language_array[0]);
            if (in_array($actual_language, $available_languages)) {
                $language = $actual_language;
                break;
            }    
        }
        return $language;
    }
    // }}}

    /* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
