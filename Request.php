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
    protected $password = "";
    public $allowUnsafeSSL = false;

    // {{{ __construct()
    /**
     * @brief jsmin class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($url = "") {
        $this->url = $url;
    }
    // }}}

    // {{{ setUrl()
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }
    // }}}
    // {{{ setPostData()
    public function setPostData($postData) {
        $this->postData = $postData;

        return $this;
    }
    // }}}
    // {{{ setJson()
    public function setJson($postData) {
        $this->setPostData(json_encode($postData));

        return $this;
    }
    // }}}
    // {{{ setCookie()
    public function setCookie($cookie) {
        if (is_array($cookie)) {
            $cookies = array();
            foreach ($cookie as $key => $val) {
                $cookies[] = $key . "=" . rawurlencode($val);
            }
            if( count($cookies) > 0 ) {
                $this->cookie = trim(implode('; ', $cookies));
            }
        } else {
            $this->cookie = $cookie;
        }

        return $this;
    }
    // }}}
    // {{{ setHeader()
    public function setHeaders($headers) {
        $this->headers = $headers;

        return $this;
    }
    // }}}
    // {{{ setPassword()
    public function setPassword($password) {
        $this->password = $password;

        return $this;
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

        if (!empty($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        if (!empty($this->cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }
        if (!empty($this->password)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->password);
        }
        if (!empty($this->postData)) {
            // array for automatically encoding post data
            $postStr = http_build_query($this->postData, '', '&');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        } else if (is_string($this->postData) && strlen($this->postData) > 0) {
            // string for already encoded post data
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postData);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        if ($this->allowUnsafeSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //execute request
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        $header = substr($response, 0, $info['header_size']);
        $body = substr($response, $info['header_size']);

        curl_close($ch);

        return new Response($body, $header, $info);
    }
    // }}}

    // {{{ getRequestIp()
    static function getRequestIp() {
        // get ip of request
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        } else if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        return $ip;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
