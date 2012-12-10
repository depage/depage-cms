<?php
/**
 * @file    ajaxproxy.php
 * @brief   ajaxproxy class to circumvent same origin policy for cross domain ajax calls
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\http;

require_once(__DIR__ . "/request.php");
require_once(__DIR__ . "/response.php");

/**
 * @brief ajaxproxy class
 **/
class ajaxproxy {
    // {{{ variables()
    protected $headerBlacklist = array(
        'Accept-Encoding',
        'Connection',
        'Content-Length',
        'Content-Type',
        'Transfer-Encoding',
        'Host',
    );
    // }}}

    // {{{ constructor()
    public function __construct($destinationURL) {
        $this->destinationURL = $destinationURL;
    }
    // }}}
    
    // {{{ getHeaders()
    public function getHeaders($asKeyVal = true) {
        // Function is from: http://www.electrictoolbox.com/php-get-headers-sent-from-browser/
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                if ($asKeyVal) {
                    $headers[$name] = $value;
                } else {
                    $headers[] = "$name: $value";
                }
            }
        }
        return $headers;
    }
    // }}}
    
    // {{{ filterHeaders()
    public function filterHeaders($headers) {
        $filtered = array();

        foreach($headers as $header) {
            $add = true;
            foreach ($this->headerBlacklist as $a) {
                if (stripos($header, $a) === 0) {
                    $add = false;
                }
            }
            if ($add) {
                $filtered[] = $header;
            }
        }

        return $filtered;
    }
    // }}}
    
    // {{{ forwardRequest()
    public function forwardRequest() {
        $headers = $this->getHeaders(false);
        $headers[] = 'X-Forwarded-For: ' . request::getRequestIp();
        $headers = $this->filterHeaders($headers);

        $request = new request($this->destinationURL);
        $request->setPostData($_POST);
        $request->setHeaders($headers);
        $response = $request->execute();

        // send headers
        $headers = $this->filterHeaders($response->headers);
        foreach($headers as $header) {
            error_log($header . "<br>\n");
            header($header, stripos($header, "Set-Cookie") !== 0);
        }
        // send body
        echo($response);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
