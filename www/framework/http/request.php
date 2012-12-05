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
    public function __construct($url, $postData = array(), $headers = array()) {
        $this->url = $url;
        $this->postData = $postData;
        $this->headers = $headers;
    }
    // }}}
    // {{{ setUrl()
    public function setUrl($url) {
        $this->url = $url;
    }
    // }}}
    // {{{ setPostData()
    public function setPostData($postData) {
        $this->postData = $postData;
    }
    // }}}
    // {{{ setHeader()
    public function setHeaders($headers) {
        $this->headers = $headers;
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
        if (count($this->headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            if (isset($this->headers['Cookie'])) {
                curl_setopt($ch, CURLOPT_COOKIE, $this->headers['Cookie']); 
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        //execute request
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);

        return new response($header, $body, $info);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
