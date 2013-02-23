<?php
/**
 * @file    request.php
 * @brief   http request class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\http;

/**
 * @brief Main request class
 **/
class request {
    // {{{ __construct()
    /**
     * @brief jsmin class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($url, $postData = array()) {
        $this->url = $url;
        $this->postData = $postData;
    }
    // }}}
    // {{{ execute()
    /**
     * @brief executes query
     **/
    public function execute() {
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $this->url);

        if (count($this->postData) > 0) {
            $postStr = http_build_query($this->postData, '', '&');
            curl_setopt($ch, CURLOPT_POST, count($this->postData));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        //execute request
        return curl_exec($ch);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
