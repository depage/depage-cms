<?php
/**
 * @file    response.php
 * @brief   http response class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\http;

/**
 * @brief Main response class
 **/
class response {
    // {{{ __construct()
    public function __construct($headers, $body, $info = array()) {
        $this->headers = array_filter(explode("\r\n", $headers), function($val) {
            return !empty($val);
        });
        
        $this->body = $body;
        $this->info = $info;
    }
    // }}}
    
    // {{{ __toString()
    public function __toString() {
        return (string) $this->body;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
