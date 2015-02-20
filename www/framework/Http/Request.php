<?php
/**
 * @file    request.php
 * @brief   http request class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\Http;

/**
 * @brief Main request class
 **/
class Request {
    protected $url = "";
    protected $postData = array();
    protected $headers = array();
    protected $cookie = "";

    // {{{ __construct()
    /**
     * @brief jsmin class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($url) {
        $this->url = $url;
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
    // {{{ setCookie()
    public function setCookie($cookie) {
        $this->cookie = $cookie;
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

        if (count($this->headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        if (!empty($this->cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }
        if (count($this->postData) > 0) {
            $postStr = http_build_query($this->postData, '', '&');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        //execute request
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        $header = substr($response, 0, $info['header_size']);
        $body = substr($response, $info['header_size']);

        curl_close($ch);

        return new Response($header, $body, $info);
    }
    // }}}
    // {{{ getRequestIp()
    static function getRequestIp() {
        // get ip of request
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
